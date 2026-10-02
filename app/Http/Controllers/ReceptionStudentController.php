<?php

namespace App\Http\Controllers;

use App\Models\Centre;
use App\Models\Group;
use App\Models\ReceptionStudent;
use App\Tenancy\TenantStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReceptionStudentController extends Controller
{
    public function index()
    {
        try {
            $request = request();
            $requestedStatus = $request->query('status', 'active');
            $requestedStatus = is_string($requestedStatus) ? $requestedStatus : 'active';

            $filters = [
                'status' => $requestedStatus,
                'level'  => $request->query('level'),
                'q'      => $request->query('q'),
            ];
            if ($filters['status'] !== 'active' && ! array_key_exists($filters['status'], ReceptionStudent::statuses())) {
                $filters['status'] = 'active';
            }

            $students = ReceptionStudent::query()
                ->with(['recommendedGroup:id,name', 'registrar:id,name', 'assigner:id,name'])
                ->when($filters['status'] === 'active', fn (Builder $q) => $q->where('status', '!=', ReceptionStudent::STATUS_ARCHIVED))
                ->when($filters['status'] !== 'active', fn (Builder $q) => $q->where('status', $filters['status']))
                ->when(filled($filters['level']), fn (Builder $q) => $q->where('level', $filters['level']))
                ->when(filled($filters['q']), function (Builder $q) use ($filters) {
                    $needle = '%' . $filters['q'] . '%';

                    $q->where(fn (Builder $w) => $w
                        ->where('name', 'like', $needle)
                        ->orWhere('phone', 'like', $needle)
                        ->orWhere('parent_name', 'like', $needle)
                        ->orWhere('parent_phone', 'like', $needle));
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

            return view('reception.students.index', [
                'students' => $students,
                'filters'  => $filters,
                'levels'   => ReceptionStudent::levels(),
                'statuses' => ReceptionStudent::statuses(),
            ]);
        } catch (\Exception $e) {
            Log::error('ReceptionStudentController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Reception ro‘yxatini yuklashda xatolik.');
        }
    }

    public function create()
    {
        return view('reception.students.create', [
            'student'  => null,
            'groups'   => $this->groupOptions(),
            'levels'   => ReceptionStudent::levels(),
            'statuses' => ReceptionStudent::statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['registered_by'] = auth()->id();

        $this->stampAssignment($data);
        $imagePath = null;

        try {
            $imagePath = $request->file('test_image')?->store(TenantStorage::path('reception-tests'), 'local');
            if ($request->hasFile('test_image') && ! $imagePath) {
                return redirect()->back()->withInput()->with('error', 'Test rasmini saqlab bo‘lmadi.');
            }
            $data['test_image_path'] = $imagePath;
            ReceptionStudent::create($data);

            return redirect()->route('reception.students.index')
                ->with('success', 'Yangi kelgan talaba reception daftariga qo‘shildi.');
        } catch (\Exception $e) {
            if ($imagePath) {
                Storage::disk('local')->delete($imagePath);
            }
            Log::error('ReceptionStudentController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Ma’lumotni saqlashda xatolik.');
        }
    }

    public function edit(ReceptionStudent $student)
    {
        return view('reception.students.edit', [
            'student'  => $student,
            'groups'   => $this->groupOptions(),
            'levels'   => ReceptionStudent::levels(),
            'statuses' => ReceptionStudent::statuses(),
        ]);
    }

    public function update(Request $request, ReceptionStudent $student)
    {
        $data = $this->validated($request);
        $this->stampAssignment($data, $student);
        $oldImagePath = $student->test_image_path;
        $newImagePath = null;

        try {
            $newImagePath = $request->file('test_image')?->store(TenantStorage::path('reception-tests'), 'local');
            if ($request->hasFile('test_image') && ! $newImagePath) {
                return redirect()->back()->withInput()->with('error', 'Test rasmini saqlab bo‘lmadi.');
            }
            if ($newImagePath) {
                $data['test_image_path'] = $newImagePath;
            }
            $student->update($data);
            if ($newImagePath && $oldImagePath) {
                Storage::disk('local')->delete($oldImagePath);
            }

            return redirect()->route('reception.students.index')
                ->with('success', 'Reception yozuvi yangilandi.');
        } catch (\Exception $e) {
            if ($newImagePath) {
                Storage::disk('local')->delete($newImagePath);
            }
            Log::error('ReceptionStudentController@update error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Ma’lumotni yangilashda xatolik.');
        }
    }

    public function archive(ReceptionStudent $student)
    {
        $student->update(['status' => ReceptionStudent::STATUS_ARCHIVED]);

        return redirect()->back()->with('success', 'Yozuv arxivlandi.');
    }

    public function testImage(ReceptionStudent $student)
    {
        abort_unless($student->test_image_path, 404);

        $viewer = auth()->user();
        $isOfficeStaff = $viewer->hasRole('admin') || $viewer->hasRole('reception');
        $isAssignedTeacher = $student->recommended_group_id
            && Group::query()->whereKey($student->recommended_group_id)
                ->whereHas('teachers', fn (Builder $q) => $q->where('users.id', $viewer->id))
                ->exists();

        abort_unless($isOfficeStaff || $isAssignedTeacher, 403);
        abort_unless(Storage::disk('local')->exists($student->test_image_path), 404);

        return response()->file(Storage::disk('local')->path($student->test_image_path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validated(Request $request): array
    {
        $centreId = Centre::currentId();

        return $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'phone'                => ['nullable', 'string', 'max:32'],
            'parent_name'          => ['nullable', 'string', 'max:255'],
            'parent_phone'         => ['nullable', 'string', 'max:32'],
            'source'               => ['nullable', 'string', 'max:255'],
            'test_taken_at'        => ['nullable', 'date'],
            'test_type'            => ['nullable', 'string', 'max:100'],
            'score'                => ['nullable', 'string', 'max:100'],
            'test_image'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'level'                => ['nullable', Rule::in(array_keys(ReceptionStudent::levels()))],
            'recommended_group_id' => [
                'nullable',
                'integer',
                Rule::exists('groups', 'id')->where(fn ($q) => $q->where('centre_id', $centreId)),
            ],
            'status'               => ['required', Rule::in(array_keys(ReceptionStudent::statuses()))],
            'notes'                => ['nullable', 'string', 'max:5000'],
        ], [
            'name.required' => 'Ism familiyani kiriting.',
            'status.required' => 'Holatni tanlang.',
            'test_image.image' => 'Test rasmi rasm fayli bo‘lishi kerak.',
            'test_image.mimes' => 'Test rasmi JPG, PNG yoki WEBP bo‘lishi kerak.',
            'test_image.max' => 'Test rasmi 5 MB dan oshmasligi kerak.',
        ]);
    }

    private function stampAssignment(array &$data, ?ReceptionStudent $student = null): void
    {
        $assigned = $data['status'] === ReceptionStudent::STATUS_ASSIGNED
            || filled($data['recommended_group_id'] ?? null);

        if (! $assigned) {
            $data['assigned_by'] = null;
            $data['assigned_at'] = null;

            return;
        }

        $groupChanged = $student === null
            || (int) ($student->recommended_group_id ?? 0) !== (int) ($data['recommended_group_id'] ?? 0);
        $statusChanged = $student === null || $student->status !== $data['status'];

        if ($student?->assigned_at && ! $groupChanged && ! $statusChanged) {
            return;
        }

        $data['assigned_by'] = auth()->id();
        $data['assigned_at'] = now();
    }

    private function groupOptions()
    {
        return Group::query()
            ->teaching()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
