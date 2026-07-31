<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\HistoryPayments;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read-only portal for guardians. A parent can only ever reach the students
 * attached to them through the `parent_student` table.
 */
class ParentPanelController extends Controller
{
    /**
     * Overview of every child attached to the signed-in parent.
     */
    public function index()
    {
        try {
            $parent = auth()->user();

            $children = $parent->children()
                ->with(['groups.teachers', 'groups.room', 'deptStudent'])
                ->orderBy('name')
                ->get()
                ->map(function (User $child) {
                    $child->setAttribute('attendance_rate', $child->attendanceRate());
                    $child->setAttribute('absence_count', $child->absences()
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count());

                    return $child;
                });

            return view('parent.index', compact('children'));
        } catch (\Exception $e) {
            Log::error('ParentPanelController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', "Ma'lumotlarni yuklashda xatolik yuz berdi.");
        }
    }

    /**
     * Full detail for one child.
     */
    public function child($id)
    {
        try {
            $child = $this->resolveChild($id);

            $attendances = Attendance::where('user_id', $child->id)
                ->whereIn('status', [0, 2])
                ->with(['group', 'lesson'])
                ->orderByDesc('created_at')
                ->paginate(12);

            $testResults = Assessment::where('assessments.user_id', $child->id)
                ->leftJoin('lesson_and_histories', 'assessments.history_id', '=', 'lesson_and_histories.id')
                ->select('assessments.*', 'lesson_and_histories.name as test_name')
                ->orderByDesc('assessments.created_at')
                ->limit(20)
                ->get();

            $payments = HistoryPayments::where('user_id', $child->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            return view('parent.child', [
                'child' => $child,
                'attendances' => $attendances,
                'testResults' => $testResults,
                'payments' => $payments,
                'attendanceRate' => $child->attendanceRate(),
            ]);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ParentPanelController@child error: ' . $e->getMessage());

            return redirect()->route('parent.index')->with('error', "Ma'lumotlarni yuklashda xatolik yuz berdi.");
        }
    }

    /**
     * Attendance across all children, newest first.
     */
    public function attendance()
    {
        try {
            $childIds = auth()->user()->children()->pluck('users.id');

            $attendances = Attendance::whereIn('user_id', $childIds)
                ->whereIn('status', [0, 2])
                ->with(['user', 'group', 'lesson'])
                ->orderByDesc('created_at')
                ->paginate(20);

            return view('parent.attendance', compact('attendances'));
        } catch (\Exception $e) {
            Log::error('ParentPanelController@attendance error: ' . $e->getMessage());

            return redirect()->route('parent.index')->with('error', "Davomatni yuklashda xatolik yuz berdi.");
        }
    }

    /**
     * Payment history across all children.
     */
    public function payments()
    {
        try {
            $children = auth()->user()->children()->with('deptStudent')->orderBy('name')->get();

            $payments = HistoryPayments::whereIn('user_id', $children->pluck('id'))
                ->with('user')
                ->orderByDesc('created_at')
                ->paginate(20);

            return view('parent.payments', compact('children', 'payments'));
        } catch (\Exception $e) {
            Log::error('ParentPanelController@payments error: ' . $e->getMessage());

            return redirect()->route('parent.index')->with('error', "To'lovlarni yuklashda xatolik yuz berdi.");
        }
    }

    /**
     * Guard: the id must belong to one of this parent's children.
     */
    private function resolveChild($id): User
    {
        $child = auth()->user()->children()
            ->with(['groups.teachers', 'deptStudent'])
            ->where('users.id', $id)
            ->first();

        abort_if(! $child, 404);

        return $child;
    }
}
