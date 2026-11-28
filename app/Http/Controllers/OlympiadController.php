<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Olympiad;
use App\Models\OlympiadParticipant;
use App\Models\ReceptionStudent;
use App\Models\User;
use App\Services\OlympiadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PDF;

/**
 * Olimpiada orqali talaba saralash.
 *
 * Reception va admin: olimpiada, ishtirokchilar, ballar.
 * Faqat admin: tavsiya qoidalari, qaror (qabul/zaxira/o'tmadi), chegirma
 * foizi va guruhga biriktirish (marshrutlarda `role:admin`).
 */
class OlympiadController extends Controller
{
    public function __construct(private OlympiadService $service)
    {
    }

    /* ============================================================ CRUD */

    public function index()
    {
        $olympiads = Olympiad::withCount([
                'participants',
                'participants as accepted_count' => fn ($q) => $q->where('status', OlympiadParticipant::STATUS_ACCEPTED),
                'participants as enrolled_count' => fn ($q) => $q->whereNotNull('enrolled_at'),
            ])
            ->orderByDesc('held_on')
            ->orderByDesc('id')
            ->get();

        return view('olympiads.index', compact('olympiads'));
    }

    public function create()
    {
        return view('olympiads.form', ['olympiad' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedOlympiad($request);

        $olympiad = Olympiad::create($data + ['created_by' => auth()->id(), 'status' => Olympiad::STATUS_PLANNED]);

        return redirect()->route('olympiads.show', $olympiad)->with('success', 'Olimpiada yaratildi. Endi ishtirokchilarni qo‘shing.');
    }

    public function edit(Olympiad $olympiad)
    {
        return view('olympiads.form', compact('olympiad'));
    }

    public function update(Request $request, Olympiad $olympiad)
    {
        $data = $this->validatedOlympiad($request, true);

        $olympiad->update($data);
        // Bo'limlar o'zgargan bo'lishi mumkin — natija va o'rin qayta.
        $this->service->recalculate($olympiad);

        return redirect()->route('olympiads.show', $olympiad)->with('success', 'Olimpiada saqlandi.');
    }

    public function show(Olympiad $olympiad)
    {
        $participants = $olympiad->participants()
            ->with(['group:id,name', 'student:id,name'])
            ->get()
            ->sortBy(fn ($p) => [$p->rank === null ? 1 : 0, $p->rank ?? 0, mb_strtolower($p->name)])
            ->values();

        $isAdmin = auth()->user()->hasRole('admin');

        $receptionOptions = ReceptionStudent::query()
            ->whereNull('student_id')
            ->where('status', '!=', ReceptionStudent::STATUS_ARCHIVED)
            ->whereNotIn('id', $participants->pluck('reception_student_id')->filter())
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $studentOptions = User::role('student')
            ->inCurrentCentre()
            ->whereNotIn('users.id', $participants->pluck('user_id')->filter())
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.phone']);

        $groups = $isAdmin
            ? Group::query()->teaching()->active()->orderBy('name')->orderBy('id')->get(['id', 'name'])
            : collect();

        return view('olympiads.show', compact('olympiad', 'participants', 'isAdmin', 'receptionOptions', 'studentOptions', 'groups'));
    }

    public function destroy(Olympiad $olympiad)
    {
        if ($olympiad->participants()->whereNotNull('enrolled_at')->exists()) {
            return redirect()->back()->with('error', 'Guruhga biriktirilgan ishtirokchisi bor olimpiadani o‘chirib bo‘lmaydi.');
        }

        $olympiad->delete();

        return redirect()->route('olympiads.index')->with('success', 'Olimpiada o‘chirildi.');
    }

    /* ==================================================== ishtirokchilar */

    /** Yangi odamni qo'lda qo'shish. */
    public function addParticipant(Request $request, Olympiad $olympiad)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:32'],
            'parent_name'  => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:32'],
            'school'       => ['nullable', 'string', 'max:255'],
            'grade'        => ['nullable', 'string', 'max:32'],
        ], ['name.required' => 'Ishtirokchi ismini kiriting.']);

        $olympiad->participants()->create($data);

        return redirect()->back()->with('success', "{$data['name']} ishtirokchilarga qo‘shildi.");
    }

    /** Reception daftaridan tanlab qo'shish. */
    public function addFromReception(Request $request, Olympiad $olympiad)
    {
        $ids = (array) $request->validate([
            'reception_ids'   => ['required', 'array', 'min:1'],
            'reception_ids.*' => ['integer'],
        ], ['reception_ids.required' => 'Reception ro‘yxatidan kamida bittasini tanlang.'])['reception_ids'];

        $already = $olympiad->participants()->pluck('reception_student_id')->filter()->all();
        $added = 0;

        foreach (ReceptionStudent::whereIn('id', $ids)->whereNotIn('id', $already)->get() as $record) {
            $olympiad->participants()->create([
                'reception_student_id' => $record->id,
                'name'                 => $record->name,
                'phone'                => $record->phone,
                'parent_name'          => $record->parent_name,
                'parent_phone'         => $record->parent_phone,
            ]);
            $added++;
        }

        return redirect()->back()->with('success', "Reception’dan {$added} ta ishtirokchi qo‘shildi.");
    }

    /** Markazda o'qiyotgan talabalarni qo'shish. */
    public function addStudents(Request $request, Olympiad $olympiad)
    {
        $ids = (array) $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
        ], ['student_ids.required' => 'Kamida bitta talabani tanlang.'])['student_ids'];

        $already = $olympiad->participants()->pluck('user_id')->filter()->all();
        $added = 0;

        $students = User::role('student')->inCurrentCentre()
            ->whereIn('users.id', $ids)->whereNotIn('users.id', $already)->get();

        foreach ($students as $student) {
            $olympiad->participants()->create([
                'user_id'      => $student->id,
                'name'         => $student->name,
                'phone'        => $student->phone,
                'parent_name'  => $student->parents_name,
                'parent_phone' => $student->parents_tel,
            ]);
            $added++;
        }

        return redirect()->back()->with('success', "{$added} ta talaba ishtirokchilarga qo‘shildi.");
    }

    public function removeParticipant(Olympiad $olympiad, int $participant)
    {
        $model = $olympiad->participants()->findOrFail($participant);

        if ($model->isEnrolled()) {
            return redirect()->back()->with('error', 'Guruhga biriktirilgan ishtirokchini o‘chirib bo‘lmaydi.');
        }

        $model->delete();
        $this->service->recalculate($olympiad);

        return redirect()->back()->with('success', 'Ishtirokchi ro‘yxatdan olib tashlandi.');
    }

    /* ============================================================ ballar */

    public function saveScores(Request $request, Olympiad $olympiad)
    {
        $sections = collect($olympiad->sections ?? []);

        $rules = ['scores' => ['nullable', 'array']];
        $messages = [];
        foreach ($sections as $section) {
            $rules["scores.*.{$section['key']}"] = ['nullable', 'integer', 'min:0', 'max:' . (int) $section['max']];
            $messages["scores.*.{$section['key']}.max"] = "{$section['label']}: ball {$section['max']} dan oshmasligi kerak.";
        }
        $request->validate($rules, $messages);

        $input = (array) $request->input('scores', []);

        foreach ($olympiad->participants()->get() as $participant) {
            if (! array_key_exists($participant->id, $input)) {
                continue;
            }

            $scores = [];
            foreach ($sections as $section) {
                $value = $input[$participant->id][$section['key']] ?? null;
                $scores[$section['key']] = ($value === null || $value === '') ? null : (int) $value;
            }

            $participant->update(['scores' => $scores]);
        }

        $this->service->recalculate($olympiad);

        if ($olympiad->status === Olympiad::STATUS_PLANNED) {
            $olympiad->update(['status' => Olympiad::STATUS_HELD]);
        }

        return redirect()->back()->with('success', 'Ballar saqlandi, o‘rinlar qayta hisoblandi.');
    }

    /* ================================================== admin: saralash */

    public function saveRules(Request $request, Olympiad $olympiad)
    {
        $request->validate([
            'rules'           => ['nullable', 'array'],
            'rules.*.from'    => ['nullable', 'integer', 'min:1'],
            'rules.*.to'      => ['nullable', 'integer', 'min:1'],
            'rules.*.percent' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $rules = collect((array) $request->input('rules', []))
            ->filter(fn ($r) => filled($r['from'] ?? null) && filled($r['to'] ?? null) && filled($r['percent'] ?? null))
            ->map(fn ($r) => [
                'from'    => min((int) $r['from'], (int) $r['to']),
                'to'      => max((int) $r['from'], (int) $r['to']),
                'percent' => (int) $r['percent'],
            ])
            ->sortBy('from')
            ->values()
            ->all();

        $olympiad->update(['rules' => $rules]);

        $message = 'Qoidalar saqlandi.';
        if ($request->boolean('apply')) {
            $count = $this->service->applyRules($olympiad);
            $message = "Qoidalar saqlandi va qo‘llandi: {$count} ta ishtirokchiga tavsiya chegirma yozildi. Har birini pastda o‘zgartirish mumkin.";
        }

        return redirect()->back()->with('success', $message);
    }

    /** Har bir ishtirokchi uchun holat va chegirma foizi (qo'lda). */
    public function saveDecisions(Request $request, Olympiad $olympiad)
    {
        $request->validate([
            'decisions'            => ['nullable', 'array'],
            'decisions.*.status'   => ['nullable', Rule::in(array_keys(OlympiadParticipant::statuses()))],
            'decisions.*.discount' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], ['decisions.*.discount.max' => 'Chegirma 100% dan oshmasligi kerak.']);

        $input = (array) $request->input('decisions', []);
        $changed = 0;

        foreach ($olympiad->participants()->whereNull('enrolled_at')->get() as $participant) {
            if (! isset($input[$participant->id])) {
                continue;
            }

            $row = $input[$participant->id];
            $discount = filled($row['discount'] ?? null) ? (int) $row['discount'] : null;

            $participant->fill([
                'status'           => $row['status'] ?? $participant->status,
                'discount_percent' => $discount ?: null,
            ]);

            if ($participant->isDirty()) {
                $participant->save();
                $changed++;
            }
        }

        return redirect()->back()->with('success', "Qarorlar saqlandi ({$changed} ta o‘zgarish).");
    }

    public function enroll(Request $request, Olympiad $olympiad)
    {
        $data = $request->validate([
            'participant_ids'   => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer'],
            'group_id'          => ['required', 'integer'],
            'parent_account'    => ['nullable', 'boolean'],
        ], [
            'participant_ids.required' => 'Kamida bitta ishtirokchini belgilang.',
            'group_id.required'        => 'Guruhni tanlang.',
        ]);

        $group = Group::query()->teaching()->find($data['group_id']);

        if (! $group) {
            return redirect()->back()->with('error', 'Tanlangan guruh topilmadi.');
        }

        $participants = $olympiad->participants()->whereIn('id', $data['participant_ids'])->get();
        $result = $this->service->enroll($olympiad, $participants, $group, ! empty($data['parent_account']));

        $redirect = redirect()->back();

        if ($result['enrolled'] > 0) {
            $redirect = $redirect->with('success', "{$result['enrolled']} ta ishtirokchi «{$group->name}» guruhiga biriktirildi, chegirmalari berildi.");
        }

        if ($result['errors'] !== []) {
            $redirect = $redirect->with('error', 'Biriktirilmadi: ' . implode(' · ', $result['errors']));
        }

        return $redirect;
    }

    public function pdf(Olympiad $olympiad)
    {
        try {
            $participants = $olympiad->participants()->with('group:id,name')->get()
                ->sortBy(fn ($p) => [$p->rank === null ? 1 : 0, $p->rank ?? 0, mb_strtolower($p->name)])
                ->values();

            $pdf = PDF::loadView('olympiads.pdf', compact('olympiad', 'participants'));

            return $pdf->download('olimpiada_' . Str::slug($olympiad->name) . '.pdf');
        } catch (\Throwable $e) {
            Log::error('OlympiadController@pdf error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Hisobotni yuklashda xatolik.');
        }
    }

    /* ================================================================== */

    private function validatedOlympiad(Request $request, bool $editing = false): array
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'held_on'           => ['nullable', 'date'],
            'notes'             => ['nullable', 'string', 'max:5000'],
            'status'            => [$editing ? 'required' : 'nullable', Rule::in(array_keys(Olympiad::statuses()))],
            'sections'          => ['required', 'array', 'min:1'],
            'sections.*.key'    => ['nullable', 'string', 'max:40'],
            'sections.*.label'  => ['required', 'string', 'max:100'],
            'sections.*.max'    => ['required', 'integer', 'min:1', 'max:1000'],
        ], [
            'name.required'            => 'Olimpiada nomini kiriting.',
            'sections.required'        => 'Kamida bitta bo‘lim kiriting.',
            'sections.*.label.required' => 'Bo‘lim nomini kiriting.',
            'sections.*.max.required'  => 'Bo‘limning maksimal balini kiriting.',
        ]);

        // Kalit saqlanadi (tahrirda ballar yo'qolmasin); yangisiga yangi kalit.
        $data['sections'] = collect($data['sections'])
            ->map(fn ($s) => [
                'key'   => filled($s['key'] ?? null) ? Str::slug($s['key'], '_') : 's' . Str::lower(Str::random(6)),
                'label' => trim($s['label']),
                'max'   => (int) $s['max'],
            ])
            ->values()
            ->all();

        if (! $editing) {
            unset($data['status']);
        }

        return $data;
    }
}
