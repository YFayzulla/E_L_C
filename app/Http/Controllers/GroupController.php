<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    /** Sortable columns, whitelisted — never interpolate raw input into ORDER BY. */
    private const SORTABLE = ['name', 'members_count', 'monthly_payment', 'created_at'];

    /**
     * NOTE: the parent class declares index() with no parameters, so this one
     * cannot take a Request either — read the query string with request().
     */
    public function index()
    {
        try {
            $request = request();

            [$sortCol, $sortDir] = $this->resolveSort($request->input('sort'));

            // ?q[]=x would reach the scope as an array and blow up inside the
            // query builder — flatten everything to a scalar string first.
            $filters = collect($request->only('q', 'teacher_id', 'room_id', 'has_students'))
                ->map(fn ($value) => is_scalar($value) ? (string) $value : null)
                ->all();

            $groups = Group::query()
                ->teaching()
                ->with(['teachers:id,name', 'room:id,room'])
                // Group::getStudentsCountAttribute() shadows `students_count`,
                // so the count MUST be aliased or the view silently re-queries.
                ->withCount(['students as members_count'])
                ->filter($filters)
                ->orderBy($sortCol, $sortDir)
                ->paginate(25)
                ->withQueryString();

            $teachers = User::role('user')->orderBy('name')->get(['id', 'name']);
            $rooms = Room::orderBy('room')->get(['id', 'room']);

            return view('admin.group.index', compact('groups', 'teachers', 'rooms'));
        } catch (\Exception $e) {
            Log::error('GroupController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhlarni yuklashda xatolik yuz berdi.');
        }
    }

    /**
     * "column-direction" from the sort <select> into a safe ORDER BY pair.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveSort(mixed $sort): array
    {
        $parts = explode('-', is_scalar($sort) ? (string) $sort : '', 2);

        $column = $parts[0] ?? '';
        $direction = strtolower($parts[1] ?? 'asc');

        if (! in_array($column, self::SORTABLE, true)) {
            $column = 'name';
            $direction = 'asc';
        }

        return [$column, $direction === 'desc' ? 'desc' : 'asc'];
    }

    public function create()
    {
        return view('admin.group.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'nullable|string|max:255',
            'finish_time' => 'nullable|string|max:255',
            'monthly_payment' => 'required|numeric|min:0',
        ]);

        try {
            $group = Group::create([
                'name' => $request->name,
                'start_time' => $request->start_time,
                'finish_time' => $request->finish_time,
                'monthly_payment' => (int)$request->monthly_payment,
            ]);

            if (method_exists($group, 'hasTeacher')) {
                $teacherId = $group->hasTeacher();
                if ($teacherId) {
                    GroupTeacher::create([
                        'group_id' => $group->id,
                        'teacher_id' => $teacherId,
                    ]);
                }
            }

            return redirect()->route('group.index')
                ->with('success', 'Guruh muvaffaqiyatli qo\'shildi va o\'qituvchiga biriktirildi.');

        } catch (\Exception $e) {
            Log::error('GroupController@store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Guruhni saqlashda xatolik yuz berdi.');
        }
    }

    public function show($id)
    {
        // This method seems redundant if index shows all groups, but keeping it for compatibility if needed
        // Or maybe it's used to show a specific group details?
        // Based on previous code, it was showing groups for a room.
        // Now we might want to show details of a single group or just redirect to index.

        // If the intention is to show details of a specific group:
        try {
            $group = Group::findOrFail($id);
            // You might want a specific view for showing group details
            // For now, let's just return the index view with all groups, or maybe filter?
            // But usually show($id) is for a single resource.

            // Let's assume we want to list groups, similar to index.
            // If the previous logic was listing groups in a room, now we list all groups.
            return redirect()->route('group.index');

        } catch (\Exception $e) {
            return redirect()->route('group.index');
        }
    }

    public function edit(Group $group)
    {
        return view('admin.group.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'nullable|string|max:255',
            'finish_time' => 'nullable|string|max:255',
            'monthly_payment' => 'required|numeric|min:0',
        ]);

        try {
            $group->update($request->all());

            return redirect()->route('group.index')->with('success', 'Ma\'lumotlar muvaffaqiyatli yangilandi.');

        } catch (\Exception $e) {
            Log::error('GroupController@update error: ' . $e->getMessage());
            return redirect()->route('group.index')->with('error', 'Yangilashda xatolik yuz berdi.');
        }
    }

    public function destroy(Group $group)
    {
        DB::beginTransaction();
        try {
            // 1. O'qituvchi bog'lanishini o'chirish (Pivot jadvaldan)
            // This removes the relationship but keeps the teacher user
            GroupTeacher::where('group_id', $group->id)->delete();

            // 2. Talabalar bog'lanishini o'chirish (Pivot jadvaldan)
            // detach() metodi pivot jadvaldan (group_user) yozuvlarni o'chiradi
            // This removes the relationship but keeps the student user
            $group->students()->detach();

            // 3. Guruhni o'chirish
            $group->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Guruh muvaffaqiyatli o\'chirildi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GroupController@destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhni o\'chirishda xatolik yuz berdi.');
        }
    }
}
