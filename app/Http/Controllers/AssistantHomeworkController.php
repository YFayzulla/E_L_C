<?php

namespace App\Http\Controllers;

use App\Http\Requests\Homework\GradeRequest;
use App\Models\Group;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssistantHomeworkController extends Controller
{
    public function index()
    {
        try {
            $request = request();

            $filters = [
                'skill'  => $request->query('skill'),
                'status' => $request->query('status'),
                'q'      => $request->query('q'),
            ];

            $allowedSkills = Homework::assistantSkills();

            $homeworks = Homework::query()
                ->whereIn('skill', $allowedSkills)
                ->with('group:id,name')
                ->withCount([
                    'submissions as submitted_count' => fn (Builder $q) => $q->whereNotNull('submitted_at'),
                    'submissions as graded_count'    => fn (Builder $q) => $q->whereNotNull('score'),
                ])
                ->withAvg(
                    ['submissions as average_score' => fn (Builder $q) => $q->whereNotNull('score')],
                    'score'
                )
                ->when(
                    filled($filters['skill']) && in_array($filters['skill'], $allowedSkills, true),
                    fn (Builder $q) => $q->where('skill', $filters['skill'])
                )
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

            return view('assistant.homework.index', [
                'homeworks' => $homeworks,
                'filters' => $filters,
                'skillOptions' => collect(Homework::skillOptions())->only($allowedSkills)->all(),
                'memberCounts' => $this->memberCounts($homeworks->pluck('group_id')),
            ]);
        } catch (\Exception $e) {
            Log::error('AssistantHomeworkController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Tekshiriladigan vazifalarni yuklashda xatolik.');
        }
    }

    public function grade(Homework $homework)
    {
        $this->assertAssistantHomework($homework);

        try {
            $homework->load('group:id,name');

            return view('assistant.homework.grade', [
                'homework' => $homework,
                'students' => $homework->roster()->get(['users.id', 'users.name', 'users.phone', 'users.photo']),
                'submissions' => $homework->submissions()->get()->keyBy('user_id'),
            ]);
        } catch (\Exception $e) {
            Log::error('AssistantHomeworkController@grade error: ' . $e->getMessage());

            return redirect()->route('assistant.homework.index')->with('error', 'Baholash sahifasini yuklashda xatolik.');
        }
    }

    public function storeGrades(GradeRequest $request, Homework $homework)
    {
        $this->assertAssistantHomework($homework);

        DB::beginTransaction();

        try {
            $rosterIds = $homework->roster()->pluck('users.id');
            $existing = $homework->submissions()->pluck('user_id')->flip();
            $statuses = (array) $request->input('status', []);
            $scores = (array) $request->input('score', []);
            $comments = (array) $request->input('comment', []);
            $assistantId = auth()->id();
            $now = now();
            $rows = [];

            foreach ($rosterIds as $studentId) {
                $studentId = (int) $studentId;
                $rawScore = $scores[$studentId] ?? null;
                $rawComment = $comments[$studentId] ?? null;
                $hasScore = $rawScore !== null && $rawScore !== '';
                $hasComment = filled($rawComment);
                $status = isset($statuses[$studentId]) ? (int) $statuses[$studentId] : 0;

                if (! $hasScore && ! $hasComment && $status === HomeworkSubmission::STATUS_MISSING
                    && ! $existing->has($studentId)) {
                    continue;
                }

                $rows[] = [
                    'homework_id' => $homework->id,
                    'user_id' => $studentId,
                    'status' => $status,
                    'score' => $hasScore ? (int) $rawScore : null,
                    'comment' => $hasComment ? $rawComment : null,
                    'graded_by' => ($hasScore || $hasComment) ? $assistantId : null,
                    'graded_at' => ($hasScore || $hasComment) ? $now : null,
                ];
            }

            if ($rows) {
                HomeworkSubmission::upsert(
                    $rows,
                    ['homework_id', 'user_id'],
                    ['status', 'score', 'comment', 'graded_by', 'graded_at', 'updated_at']
                );
            }

            DB::commit();

            return redirect()->route('assistant.homework.grade', $homework->id)
                ->with('success', 'Baholar saqlandi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AssistantHomeworkController@storeGrades error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Baholarni saqlashda xatolik.');
        }
    }

    private function assertAssistantHomework(Homework $homework): void
    {
        abort_unless(in_array($homework->skill, Homework::assistantSkills(), true), 403);
    }

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
}
