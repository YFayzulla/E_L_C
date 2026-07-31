<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Http\Requests\StudentTransferRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\StudentGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Moving a student between groups.
 *
 * Membership carries money — group_user.payment feeds the teacher's salary, and
 * users.should_pay / dept_students.dept drive the debt screens — so every write
 * goes through App\Services\StudentGroupService, never through a bare sync().
 *
 * A teacher may only move a student between groups they themselves teach; an
 * admin may move anywhere.
 */
class StudentTransferController extends Controller
{
    use AuthorizesGroupAccess;

    /** Kutish zali — teachers must not park students there. */
    private const WAITING_ROOM_ID = 1;

    public function __construct(private StudentGroupService $membership)
    {
    }

    public function create(Request $request, int $student)
    {
        $this->assertTeachesStudent($student);

        $model = User::with('groups')->findOrFail($student);

        abort_unless($model->hasRole('student'), 404, 'Talaba topilmadi.');

        try {
            $isAdmin = auth()->user()->hasRole('admin');

            $targets = Group::query()
                ->with('teachers:id,name')
                ->withCount(['students as members_count'])
                ->when(! $isAdmin, function ($query) {
                    $query->whereIn('id', $this->accessibleGroupIds())
                        ->where('id', '!=', self::WAITING_ROOM_ID);
                })
                ->orderByRaw("CASE WHEN id = " . self::WAITING_ROOM_ID . " THEN 1 ELSE 0 END, name")
                ->get();

            $selectableIds = $targets->pluck('id')->map(fn ($id) => (int) $id)->all();

            // A teacher may not detach a student from somebody else's group, so
            // those memberships are shown read-only and re-submitted untouched.
            // The Kutish zali is NOT preserved — leaving it is the whole point
            // of being assigned to a real group.
            $lockedGroups = $model->groups->reject(
                fn ($group) => in_array((int) $group->id, $selectableIds, true)
                    || (int) $group->id === self::WAITING_ROOM_ID
            );

            $currentIds = $model->groups->pluck('id')->map(fn ($id) => (int) $id)->all();

            return view('student.transfer', [
                'student' => $model,
                'targets' => $targets,
                'currentIds' => $currentIds,
                'selectableIds' => $selectableIds,
                'lockedIds' => $lockedGroups->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'lockedGroups' => $lockedGroups,
                'isAdmin' => $isAdmin,
                'waitingRoomId' => self::WAITING_ROOM_ID,
            ]);
        } catch (\Exception $e) {
            Log::error('StudentTransferController@create error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Ko‘chirish sahifasini yuklashda xatolik yuz berdi.');
        }
    }

    public function store(StudentTransferRequest $request, int $student)
    {
        $this->assertTeachesStudent($student);

        $model = User::findOrFail($student);

        abort_unless($model->hasRole('student'), 404, 'Talaba topilmadi.');

        $targets = $request->targetGroupIds();
        $isAdmin = auth()->user()->hasRole('admin');

        if (! $isAdmin) {
            abort_if(
                in_array(self::WAITING_ROOM_ID, $targets, true),
                403,
                'Kutish zaliga faqat administrator ko‘chira oladi.'
            );

            // Every posted target must belong to this teacher…
            $this->assertTeachesGroups($targets);

            // …and memberships outside their reach survive the sync untouched,
            // the Kutish zali excepted — a student assigned to a real group
            // must be able to leave it.
            $accessible = $this->accessibleGroupIds()->map(fn ($id) => (int) $id)->all();

            $preserved = array_diff(
                $model->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all(),
                $accessible,
                [self::WAITING_ROOM_ID]
            );

            $targets = array_values(array_unique(array_merge($targets, $preserved)));
        }

        try {
            $change = $this->membership->preview($model, $targets);

            $this->membership->transfer($model, $targets, $request->payments(), auth()->id());
        } catch (\Exception $e) {
            Log::error('StudentTransferController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()
                ->with('error', 'Talabani ko‘chirishda xatolik yuz berdi. O‘zgarishlar saqlanmadi.');
        }

        return redirect()->route('student.show', $model->id)
            ->with('success', $this->summary($model, $change));
    }

    /**
     * Uzbek flash naming the groups joined and left.
     *
     * @param  array{joined: array<int, int>, left: array<int, int>}  $change
     */
    private function summary(User $model, array $change): string
    {
        $names = Group::whereIn('id', array_merge($change['joined'], $change['left']))
            ->pluck('name', 'id');

        $label = fn (array $ids) => collect($ids)
            ->map(fn ($id) => $names[$id] ?? ('#' . $id))
            ->implode(', ');

        $parts = [];

        if ($change['joined']) {
            $parts[] = 'qo‘shildi — ' . $label($change['joined']);
        }

        if ($change['left']) {
            $parts[] = 'chiqarildi — ' . $label($change['left']);
        }

        if (! $parts) {
            return $model->name . ' guruhlari o‘zgarmadi.';
        }

        return $model->name . ': ' . implode('; ', $parts)
            . '. Oylik to‘lov va qarzdorlik qayta hisoblandi.';
    }
}
