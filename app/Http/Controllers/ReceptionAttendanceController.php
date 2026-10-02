<?php

namespace App\Http\Controllers;

use App\Models\AbsenceFollowUp;
use App\Models\Attendance;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReceptionAttendanceController extends Controller
{
    public function index()
    {
        try {
            $request = request();

            $from = $this->parseDay($request->query('from')) ?? now()->startOfMonth();
            $to = $this->parseDay($request->query('to')) ?? now();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to, $from];
            }

            $groupId = (int) $request->query('group_id');
            $followStatus = $request->query('follow_status');
            $validFollowStatus = is_string($followStatus)
                && array_key_exists($followStatus, AbsenceFollowUp::statuses());

            $records = Attendance::query()
                ->whereIn('status', [0, 2])
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->when($groupId > 0, fn (Builder $q) => $q->where('group_id', $groupId))
                ->when($followStatus === AbsenceFollowUp::STATUS_PENDING, function (Builder $q) {
                    $q->where(fn (Builder $w) => $w
                        ->whereDoesntHave('followUps')
                        ->orWhereHas('followUps', fn (Builder $f) => $f
                            ->where('status', AbsenceFollowUp::STATUS_PENDING)));
                })
                ->when($validFollowStatus && $followStatus !== 'pending', function (Builder $q) use ($followStatus) {
                    $q->whereHas('followUps', fn (Builder $f) => $f->where('status', $followStatus));
                })
                ->with([
                    'user:id,name,phone,parents_name,parents_tel',
                    'group:id,name',
                    'lesson:id,name',
                    'teacher:id,name',
                    'reasonWriter:id,name',
                    'latestFollowUp.recorder:id,name',
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

            return view('reception.absences.index', [
                'records' => $records,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'groupId' => $groupId,
                'followStatus' => $followStatus,
                'groups' => Group::query()->teaching()->orderBy('name')->get(['id', 'name']),
                'followStatuses' => AbsenceFollowUp::statuses(),
            ]);
        } catch (\Exception $e) {
            Log::error('ReceptionAttendanceController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Kelmaganlar ro‘yxatini yuklashda xatolik.');
        }
    }

    public function store(Request $request, Attendance $attendance)
    {
        abort_unless(in_array((int) $attendance->status, [0, 2], true), 404);

        if ($attendance->followUps()->exists()) {
            return redirect()->back()->with('error', 'Bu davomat uchun aloqa yozuvi avval saqlangan. Holatni qayta o‘zgartirib bo‘lmaydi.');
        }

        $data = $request->validate([
            'status'            => ['required', Rule::in(array_keys(AbsenceFollowUp::statuses()))],
            'contact_person'    => ['nullable', 'string', 'max:255'],
            'contacted_at'      => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
            'note'              => ['nullable', 'string', 'max:5000'],
        ], [
            'status.required' => 'Qo‘ng‘iroq holatini tanlang.',
        ]);

        try {
            DB::transaction(function () use ($attendance, $data) {
                $lockedAttendance = Attendance::query()->whereKey($attendance->id)->lockForUpdate()->firstOrFail();
                if ($lockedAttendance->followUps()->exists()) {
                    throw new \DomainException('follow-up-already-recorded');
                }

                $lockedAttendance->followUps()->create([
                    'student_id'        => $lockedAttendance->user_id,
                    'group_id'          => $lockedAttendance->group_id,
                    'contacted_by'      => auth()->id(),
                    'contacted_at'      => $data['contacted_at'] ?? now(),
                    'contact_person'    => $data['contact_person'] ?? null,
                    'status'            => $data['status'],
                    'note'              => $data['note'] ?? null,
                    'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                ]);
            });

            return redirect()->back()->with('success', 'Ota-ona bilan aloqa yozuvi saqlandi.');
        } catch (\Exception $e) {
            if ($e instanceof \DomainException && $e->getMessage() === 'follow-up-already-recorded') {
                return redirect()->back()->with('error', 'Bu davomat uchun aloqa yozuvi avval saqlangan. Holatni qayta o‘zgartirib bo‘lmaydi.');
            }
            Log::error('ReceptionAttendanceController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Aloqa yozuvini saqlashda xatolik.');
        }
    }

    private function parseDay($value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }
}
