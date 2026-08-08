<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Http\Requests\Homework\GradeRequest;
use App\Http\Requests\Homework\StoreRequest;
use App\Http\Requests\Homework\SubmitRequest;
use App\Http\Requests\Homework\UpdateRequest;
use App\Models\Group;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Database\Eloquent\Builder;
use App\Tenancy\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Homework: a teacher creates an assignment for a group, students hand it in
 * from their own portal, the teacher checks and grades each submission.
 */
class HomeworkController extends Controller
{
    use AuthorizesGroupAccess;

    /**
     * Teacher's assignments across the groups they teach.
     *
     * NOTE: the base App\Http\Controllers\Controller doubles as the dashboard
     * controller and declares index() with no parameters, so every child
     * index() must match that signature. Read the query string via request().
     */
    public function index()
    {
        try {
            $groupIds = $this->accessibleGroupIds();

            $filters = [
                'group_id' => request('group_id'),
                'status'   => request('status'),
                'q'        => request('q'),
            ];

            $homeworks = Homework::query()
                ->whereIn('homeworks.group_id', $groupIds)
                ->with('group:id,name')
                ->withCount([
                    'submissions as submitted_count' => fn (Builder $q) => $q->whereNotNull('submitted_at'),
                    'submissions as graded_count'    => fn (Builder $q) => $q->whereNotNull('score'),
                ])
                ->withAvg(
                    ['submissions as average_score' => fn (Builder $q) => $q->whereNotNull('score')],
                    'score'
                )
                ->when(filled($filters['group_id']), fn (Builder $q) => $q->where('homeworks.group_id', (int) $filters['group_id']))
                ->when($filters['status'] === 'overdue', fn (Builder $q) => $q
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString()))
                ->when($filters['status'] === 'active', fn (Builder $q) => $q
                    ->where(fn (Builder $w) => $w
                        ->whereNull('due_date')
                        ->orWhereDate('due_date', '>=', now()->toDateString())))
                ->when(filled($filters['q']), fn (Builder $q) => $q->where('title', 'like', '%' . $filters['q'] . '%'))
                ->orderByDesc('homeworks.created_at')
                ->orderByDesc('homeworks.id')
                ->paginate(15)
                ->withQueryString();

            $groups = Group::whereIn('id', $groupIds)->orderBy('name')->get(['id', 'name']);

            return view('teacher.homework.index', [
                'homeworks'    => $homeworks,
                'groups'       => $groups,
                'memberCounts' => $this->memberCounts($homeworks->pluck('group_id')),
                'filters'      => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('HomeworkController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Uy vazifalari ro‘yxatini yuklashda xatolik.');
        }
    }

    public function create(Request $request)
    {
        try {
            $groups = Group::whereIn('id', $this->accessibleGroupIds())
                ->orderBy('name')
                ->get(['id', 'name']);

            if ($groups->isEmpty()) {
                return redirect()->route('homework.index')
                    ->with('error', 'Sizga biriktirilgan guruh yo‘q — avval guruh biriktirilishi kerak.');
            }

            return view('teacher.homework.create', [
                'groups'   => $groups,
                'homework' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('HomeworkController@create error: ' . $e->getMessage());

            return redirect()->route('homework.index')->with('error', 'Sahifani yuklashda xatolik.');
        }
    }

    public function store(StoreRequest $request)
    {
        $this->assertTeachesGroup((int) $request->input('group_id'));

        $attachment = null;

        try {
            $attachment = $this->storeUpload($request->file('attachment'), 'vazifa');
        } catch (\Exception $e) {
            Log::error('HomeworkController@store upload error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Ilova faylini yuklashda xatolik.');
        }

        DB::beginTransaction();

        try {
            $homework = Homework::create([
                'group_id'    => (int) $request->input('group_id'),
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'attachment'  => $attachment,
                'due_date'    => $request->input('due_date') ?: null,
                'max_score'   => (int) $request->input('max_score'),
                'allow_file'  => $request->boolean('allow_file'),
                'created_by'  => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('homework.show', $homework->id)
                ->with('success', 'Uy vazifasi yaratildi.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->deleteUpload($attachment);

            Log::error('HomeworkController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Uy vazifasini saqlashda xatolik yuz berdi.');
        }
    }

    public function show(Homework $homework)
    {
        $this->assertTeachesGroup((int) $homework->group_id);

        try {
            $homework->load(['group:id,name', 'author:id,name']);

            $submissions = $homework->submissions()
                ->with('student:id,name,phone')
                ->orderByDesc('submitted_at')
                ->get();

            $rosterCount = $homework->roster()->count();

            return view('teacher.homework.show', [
                'homework'    => $homework,
                'submissions' => $submissions,
                'rosterCount' => $rosterCount,
                'submitted'   => $submissions->whereNotNull('submitted_at')->count(),
                'graded'      => $submissions->whereNotNull('score')->count(),
                'averageScore' => $submissions->whereNotNull('score')->avg('score'),
            ]);
        } catch (\Exception $e) {
            Log::error('HomeworkController@show error: ' . $e->getMessage());

            return redirect()->route('homework.index')->with('error', 'Vazifa ma’lumotlarini yuklashda xatolik.');
        }
    }

    public function edit(Homework $homework)
    {
        $this->assertTeachesGroup((int) $homework->group_id);

        try {
            $groups = Group::whereIn('id', $this->accessibleGroupIds())
                ->orderBy('name')
                ->get(['id', 'name']);

            return view('teacher.homework.edit', [
                'groups'   => $groups,
                'homework' => $homework,
            ]);
        } catch (\Exception $e) {
            Log::error('HomeworkController@edit error: ' . $e->getMessage());

            return redirect()->route('homework.index')->with('error', 'Sahifani yuklashda xatolik.');
        }
    }

    public function update(UpdateRequest $request, Homework $homework)
    {
        $this->assertTeachesGroup((int) $homework->group_id);
        $this->assertTeachesGroup((int) $request->input('group_id'));

        $oldAttachment = $homework->attachment;
        $newAttachment = $oldAttachment;
        $uploaded      = null;

        try {
            if ($request->hasFile('attachment')) {
                $uploaded = $this->storeUpload($request->file('attachment'), 'vazifa');
                $newAttachment = $uploaded;
            } elseif ($request->boolean('remove_attachment')) {
                $newAttachment = null;
            }
        } catch (\Exception $e) {
            Log::error('HomeworkController@update upload error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Ilova faylini yuklashda xatolik.');
        }

        DB::beginTransaction();

        try {
            $homework->update([
                'group_id'    => (int) $request->input('group_id'),
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'attachment'  => $newAttachment,
                'due_date'    => $request->input('due_date') ?: null,
                'max_score'   => (int) $request->input('max_score'),
                'allow_file'  => $request->boolean('allow_file'),
            ]);

            DB::commit();

            if ($oldAttachment && $oldAttachment !== $newAttachment) {
                $this->deleteUpload($oldAttachment);
            }

            return redirect()->route('homework.show', $homework->id)
                ->with('success', 'Uy vazifasi yangilandi.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->deleteUpload($uploaded);

            Log::error('HomeworkController@update error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Uy vazifasini yangilashda xatolik yuz berdi.');
        }
    }

    public function destroy(Homework $homework)
    {
        $this->assertTeachesGroup((int) $homework->group_id);

        abort_unless(
            auth()->user()->hasRole('admin') || (int) $homework->created_by === (int) auth()->id(),
            403,
            'Bu vazifani faqat uni yaratgan o‘qituvchi o‘chira oladi.'
        );

        DB::beginTransaction();

        try {
            $files = $homework->submissions()
                ->whereNotNull('submission_file')
                ->pluck('submission_file')
                ->all();

            $files[] = $homework->attachment;

            $homework->submissions()->delete();
            $homework->delete();

            DB::commit();

            foreach (array_filter($files) as $path) {
                $this->deleteUpload($path);
            }

            return redirect()->route('homework.index')->with('success', 'Uy vazifasi o‘chirildi.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('HomeworkController@destroy error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Uy vazifasini o‘chirishda xatolik yuz berdi.');
        }
    }

    /** Grading sheet: the group roster with each student's submission. */
    public function grade(Homework $homework)
    {
        $this->assertTeachesGroup((int) $homework->group_id);

        try {
            $homework->load('group:id,name');

            $students = $homework->roster()->get(['users.id', 'users.name', 'users.phone', 'users.photo']);

            $submissions = $homework->submissions()->get()->keyBy('user_id');

            return view('teacher.homework.grade', [
                'homework'    => $homework,
                'students'    => $students,
                'submissions' => $submissions,
            ]);
        } catch (\Exception $e) {
            Log::error('HomeworkController@grade error: ' . $e->getMessage());

            return redirect()->route('homework.index')->with('error', 'Baholash sahifasini yuklashda xatolik.');
        }
    }

    /** Persist the grades (upsert, so re-grading is idempotent). */
    public function storeGrades(GradeRequest $request, Homework $homework)
    {
        $this->assertTeachesGroup((int) $homework->group_id);

        DB::beginTransaction();

        try {
            $rosterIds = $homework->roster()->pluck('users.id');

            $existing = $homework->submissions()->pluck('user_id')->flip();

            $statuses = (array) $request->input('status', []);
            $scores   = (array) $request->input('score', []);
            $comments = (array) $request->input('comment', []);

            $teacherId = auth()->id();
            $now       = now();
            $rows      = [];

            foreach ($rosterIds as $studentId) {
                $studentId = (int) $studentId;

                $rawScore   = $scores[$studentId] ?? null;
                $rawComment = $comments[$studentId] ?? null;

                $hasScore   = $rawScore !== null && $rawScore !== '';
                $hasComment = filled($rawComment);
                $status     = isset($statuses[$studentId]) ? (int) $statuses[$studentId] : 0;

                // Nothing to record and nothing recorded before — skip, so the
                // table does not fill up with empty "Topshirmagan" rows.
                if (! $hasScore && ! $hasComment && $status === HomeworkSubmission::STATUS_MISSING
                    && ! $existing->has($studentId)) {
                    continue;
                }

                $rows[] = [
                    'homework_id' => $homework->id,
                    'user_id'     => $studentId,
                    'status'      => $status,
                    'score'       => $hasScore ? (int) $rawScore : null,
                    'comment'     => $hasComment ? $rawComment : null,
                    'graded_by'   => ($hasScore || $hasComment) ? $teacherId : null,
                    'graded_at'   => ($hasScore || $hasComment) ? $now : null,
                ];
            }

            if ($rows) {
                // Only the teacher-side columns are in the update list, so a
                // student's submission_text / submission_file / submitted_at
                // survive every re-grade.
                HomeworkSubmission::upsert(
                    $rows,
                    ['homework_id', 'user_id'],
                    ['status', 'score', 'comment', 'graded_by', 'graded_at', 'updated_at']
                );
            }

            DB::commit();

            return redirect()->route('homework.grade', $homework->id)
                ->with('success', 'Baholar saqlandi.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('HomeworkController@storeGrades error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Baholarni saqlashda xatolik yuz berdi.');
        }
    }

    /** Read-only cross-group view for admins. */
    public function adminIndex(Request $request)
    {
        try {
            $filters = [
                'group_id' => $request->query('group_id'),
                'from'     => $request->query('from'),
                'to'       => $request->query('to'),
            ];

            $apply = function (Builder $query) use ($filters): Builder {
                return $query
                    ->when(filled($filters['group_id']), fn (Builder $q) => $q->where('homeworks.group_id', (int) $filters['group_id']))
                    ->when(filled($filters['from']), fn (Builder $q) => $q->whereDate('homeworks.created_at', '>=', $filters['from']))
                    ->when(filled($filters['to']), fn (Builder $q) => $q->whereDate('homeworks.created_at', '<=', $filters['to']));
            };

            $homeworks = $apply(Homework::query())
                ->with(['group:id,name', 'author:id,name'])
                ->withCount([
                    'submissions as submitted_count' => fn (Builder $q) => $q->whereNotNull('submitted_at'),
                    'submissions as graded_count'    => fn (Builder $q) => $q->whereNotNull('score'),
                ])
                ->withAvg(
                    ['submissions as average_score' => fn (Builder $q) => $q->whereNotNull('score')],
                    'score'
                )
                ->orderByDesc('homeworks.created_at')
                ->orderByDesc('homeworks.id')
                ->paginate(20)
                ->withQueryString();

            $totalHomeworks = $apply(Homework::query())->count();

            $totals = HomeworkSubmission::query()
                ->whereIn('homework_id', $apply(Homework::query())->select('homeworks.id'))
                ->selectRaw('COUNT(CASE WHEN submitted_at IS NOT NULL THEN 1 END) as submitted_total')
                ->selectRaw('COUNT(score) as graded_total')
                ->selectRaw('AVG(score) as average_total')
                ->first();

            return view('admin.homework.index', [
                'homeworks'      => $homeworks,
                'groups'         => Group::orderBy('name')->get(['id', 'name']),
                'memberCounts'   => $this->memberCounts($homeworks->pluck('group_id')),
                'filters'        => $filters,
                'totalHomeworks' => $totalHomeworks,
                'submittedTotal' => (int) ($totals->submitted_total ?? 0),
                'gradedTotal'    => (int) ($totals->graded_total ?? 0),
                'averageTotal'   => $totals->average_total ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('HomeworkController@adminIndex error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Uy vazifalari hisobotini yuklashda xatolik.');
        }
    }

    /** The student's own assignment list. */
    public function studentIndex(Request $request)
    {
        try {
            $student  = auth()->user();
            $groupIds = $student->groups()->pluck('groups.id');

            $homeworks = Homework::query()
                ->leftJoin('homework_submissions as hs', function ($join) use ($student) {
                    $join->on('hs.homework_id', '=', 'homeworks.id')
                        ->where('hs.user_id', '=', $student->id);
                })
                ->whereIn('homeworks.group_id', $groupIds)
                ->select('homeworks.*')
                ->with(['group:id,name'])
                ->with(['submissions' => fn ($q) => $q->where('user_id', $student->id)])
                ->orderByRaw(
                    'CASE WHEN hs.submitted_at IS NULL AND homeworks.due_date IS NOT NULL AND homeworks.due_date < ? THEN 0'
                    . ' WHEN hs.submitted_at IS NULL THEN 1 ELSE 2 END',
                    [now()->toDateString()]
                )
                ->orderByRaw('homeworks.due_date IS NULL')
                ->orderByDesc('homeworks.due_date')
                ->orderByDesc('homeworks.id')
                ->paginate(12)
                ->withQueryString();

            return view('student.homework.index', [
                'homeworks' => $homeworks,
            ]);
        } catch (\Exception $e) {
            Log::error('HomeworkController@studentIndex error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Uy vazifalarini yuklashda xatolik.');
        }
    }

    /** The student's hand-in form for one assignment. */
    public function studentShow(Homework $homework)
    {
        $this->assertEnrolled($homework);

        try {
            $homework->load(['group:id,name', 'author:id,name']);

            $submission = $homework->submissions()
                ->where('user_id', auth()->id())
                ->first();

            return view('student.homework.show', [
                'homework'   => $homework,
                'submission' => $submission,
            ]);
        } catch (\Exception $e) {
            Log::error('HomeworkController@studentShow error: ' . $e->getMessage());

            return redirect()->route('student.homework')->with('error', 'Vazifani yuklashda xatolik.');
        }
    }

    /** The student submits or updates their work. */
    public function submit(SubmitRequest $request, Homework $homework)
    {
        $this->assertEnrolled($homework);

        if ($request->hasFile('submission_file') && ! $homework->allow_file) {
            return redirect()->back()->withInput()
                ->with('error', 'Bu vazifaga fayl biriktirish taqiqlangan.');
        }

        $studentId = auth()->id();

        $existing = HomeworkSubmission::where('homework_id', $homework->id)
            ->where('user_id', $studentId)
            ->first();

        $oldFile = $existing?->submission_file;
        $newFile = $oldFile;
        $stored  = null;

        try {
            if ($request->hasFile('submission_file')) {
                $stored  = $this->storeUpload($request->file('submission_file'), 'javob');
                $newFile = $stored;
            }
        } catch (\Exception $e) {
            Log::error('HomeworkController@submit upload error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Faylni yuklashda xatolik.');
        }

        DB::beginTransaction();

        try {
            // Server decides the status — a student can never post it.
            $status = $homework->isOverdue()
                ? HomeworkSubmission::STATUS_LATE
                : HomeworkSubmission::STATUS_DONE;

            HomeworkSubmission::upsert(
                [[
                    'homework_id'     => $homework->id,
                    'user_id'         => $studentId,
                    'submission_text' => $request->input('submission_text'),
                    'submission_file' => $newFile,
                    'submitted_at'    => now(),
                    'status'          => $status,
                ]],
                ['homework_id', 'user_id'],
                ['submission_text', 'submission_file', 'submitted_at', 'status', 'updated_at']
            );

            DB::commit();

            if ($stored && $oldFile && $oldFile !== $stored) {
                $this->deleteUpload($oldFile);
            }

            return redirect()->route('student.homework.show', $homework->id)
                ->with('success', $status === HomeworkSubmission::STATUS_LATE
                    ? 'Javobingiz qabul qilindi (muddatdan kech topshirildi).'
                    : 'Javobingiz qabul qilindi.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->deleteUpload($stored);

            Log::error('HomeworkController@submit error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Javobni saqlashda xatolik yuz berdi.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * A student may only see / submit homework for a group they belong to.
     * Teachers and admins reach the same page through their own routes, so this
     * also lets a teacher of the group open it.
     */
    private function assertEnrolled(Homework $homework): void
    {
        $user = auth()->user();

        abort_if(! $user, 403);

        if ($user->hasRole('admin')) {
            return;
        }

        $enrolled = $user->groups()->where('groups.id', $homework->group_id)->exists();

        abort_unless($enrolled, 403, 'Bu vazifa sizning guruhingizga tegishli emas.');
    }

    /**
     * Roster size per group id, in ONE query. Aliased to members_count because
     * Group::getStudentsCountAttribute() shadows withCount('students').
     *
     * @param  \Illuminate\Support\Collection<int, mixed>|array<int, mixed>  $groupIds
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function memberCounts($groupIds): Collection
    {
        $ids = collect($groupIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Group::whereIn('id', $ids)
            ->withCount(['students as members_count' => fn (Builder $q) => $q->role('student')])
            ->pluck('members_count', 'id');
    }

    private function storeUpload(?UploadedFile $file, string $prefix): ?string
    {
        if (! $file) {
            return null;
        }

        $upload = config('grading.homework_upload');

        $name = $prefix . '_' . now()->format('YmdHis') . '_' . uniqid() . '.'
            . strtolower($file->getClientOriginalExtension() ?: 'dat');

        return $file->storeAs(TenantStorage::path($upload['directory']), $name, $upload['disk'] ?? 'public');
    }

    private function deleteUpload(?string $path): void
    {
        if (! $path) {
            return;
        }

        $disk = config('grading.homework_upload.disk', 'public');

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
