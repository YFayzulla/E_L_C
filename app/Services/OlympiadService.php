<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Olympiad;
use App\Models\OlympiadParticipant;
use App\Models\ReceptionStudent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Olimpiada: natijadan o'rin, o'rindan tavsiya chegirma, qabul
 * qilinganlarni guruhga biriktirish (hisob + chegirma).
 */
class OlympiadService
{
    public function __construct(
        private StudentEnrollmentService $enrollment,
        private StudentGroupService $membership,
        private DiscountService $discounts
    ) {
    }

    /**
     * Jami ball, foiz va o'rin. O'rin foiz bo'yicha (teng foizda — jami
     * ball), teng natijalar bir xil o'rin oladi: 1, 2, 2, 4.
     * Hech bir bo'limdan ball yo'q ishtirokchi o'rinsiz qoladi.
     */
    public function recalculate(Olympiad $olympiad): void
    {
        $sections = collect($olympiad->sections ?? []);
        $maxTotal = max(1, (int) $sections->sum('max'));

        $participants = $olympiad->participants()->get();

        foreach ($participants as $participant) {
            $scores = (array) ($participant->scores ?? []);
            $given = $sections->filter(fn ($s) => isset($scores[$s['key']]) && $scores[$s['key']] !== null && $scores[$s['key']] !== '');

            if ($given->isEmpty()) {
                $participant->total = null;
                $participant->percent = null;
            } else {
                $participant->total = (int) $given->sum(fn ($s) => (int) $scores[$s['key']]);
                $participant->percent = (int) round($participant->total * 100 / $maxTotal);
            }
        }

        $ranked = $participants->filter(fn ($p) => $p->percent !== null)
            ->sortBy(fn ($p) => [-$p->percent, -$p->total, mb_strtolower($p->name)])
            ->values();

        $rank = 0;
        $previous = null;

        foreach ($ranked as $index => $participant) {
            $key = $participant->percent . ':' . $participant->total;

            if ($key !== $previous) {
                $rank = $index + 1;
                $previous = $key;
            }

            $participant->rank = $rank;
        }

        foreach ($participants as $participant) {
            if ($participant->percent === null) {
                $participant->rank = null;
            }

            if ($participant->isDirty()) {
                $participant->save();
            }
        }
    }

    /**
     * Qoidalar bo'yicha tavsiya chegirmani yozadi (hali guruhga
     * biriktirilmaganlarga). Keyin admin har birida qo'lda o'zgartiradi.
     *
     * @return int nechta ishtirokchiga qo'llandi
     */
    public function applyRules(Olympiad $olympiad): int
    {
        $count = 0;

        foreach ($olympiad->participants()->whereNull('enrolled_at')->get() as $participant) {
            $suggested = $olympiad->suggestedPercentFor($participant->rank);

            if ($participant->discount_percent !== $suggested) {
                $participant->update(['discount_percent' => $suggested]);
            }

            if ($suggested !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Qabul qilinganlarni guruhga biriktirish.
     *
     * Yangi odamga talaba hisobi ochiladi, markazdagi talaba esa guruhga
     * ko'chiriladi; chegirma (bo'lsa) shu guruhga doimiy beriladi. Har bir
     * ishtirokchi alohida — biridagi xato boshqalarini to'xtatmaydi.
     *
     * @param  Collection<int, OlympiadParticipant>  $participants
     * @return array{enrolled: int, errors: array<int, string>}
     */
    public function enroll(Olympiad $olympiad, Collection $participants, Group $group, bool $parentAccount = false): array
    {
        $enrolled = 0;
        $errors = [];

        foreach ($participants as $participant) {
            if ($participant->isEnrolled()) {
                $errors[] = "{$participant->name}: allaqachon guruhga biriktirilgan.";
                continue;
            }

            if ($participant->status !== OlympiadParticipant::STATUS_ACCEPTED) {
                $errors[] = "{$participant->name}: avval «Qabul qilindi» holatini belgilang.";
                continue;
            }

            try {
                DB::transaction(function () use ($olympiad, $participant, $group, $parentAccount) {
                    $student = $participant->user_id
                        ? $this->moveExisting($participant, $group)
                        : $this->createStudent($participant, $group, $parentAccount);

                    $discountId = null;

                    if ((int) $participant->discount_percent > 0) {
                        $rankText = $participant->rank ? ", {$participant->rank}-o‘rin" : '';
                        $discountId = $this->discounts->give(
                            $student,
                            (int) $participant->discount_percent,
                            (int) $group->id,
                            today(),
                            null,
                            "Olimpiada: {$olympiad->name}{$rankText}"
                        )->id;
                    }

                    $participant->update([
                        'user_id'     => $student->id,
                        'group_id'    => $group->id,
                        'discount_id' => $discountId,
                        'enrolled_at' => now(),
                    ]);

                    if ($participant->reception_student_id) {
                        ReceptionStudent::whereKey($participant->reception_student_id)->whereNull('student_id')->update([
                            'student_id'           => $student->id,
                            'enrolled_at'          => now(),
                            'recommended_group_id' => $group->id,
                            'status'               => ReceptionStudent::STATUS_ASSIGNED,
                            'assigned_by'          => auth()->id(),
                            'assigned_at'          => now(),
                        ]);
                    }
                });

                $enrolled++;
            } catch (OlympiadEnrollException $e) {
                $errors[] = "{$participant->name}: {$e->getMessage()}";
            } catch (\Throwable $e) {
                Log::error('OlympiadService@enroll error: ' . $e->getMessage(), ['participant' => $participant->id]);
                $errors[] = "{$participant->name}: tizim xatosi, saqlanmadi.";
            }
        }

        return ['enrolled' => $enrolled, 'errors' => $errors];
    }

    /** Markazdagi talaba: Kutish zalidan chiqib, shu guruhga qo'shiladi (boshqa guruhlari qoladi). */
    private function moveExisting(OlympiadParticipant $participant, Group $group): User
    {
        $student = User::role('student')->inCurrentCentre()->find($participant->user_id);

        if (! $student) {
            throw new OlympiadEnrollException('talaba hisobi topilmadi.');
        }

        $current = $student->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all();
        $targets = array_values(array_unique(array_merge(
            array_diff($current, [(int) Group::waitingRoomId()]),
            [(int) $group->id]
        )));

        $this->membership->transfer($student, $targets, [], auth()->id());

        return $student->fresh();
    }

    private function createStudent(OlympiadParticipant $participant, Group $group, bool $parentAccount): User
    {
        $phone = ReceptionStudent::localPhone($participant->phone);

        if (strlen($phone) !== 9) {
            throw new OlympiadEnrollException('telefon raqami kiritilmagan yoki noto‘g‘ri (9 ta raqam kerak).');
        }

        if (User::role('student')->where('phone', '998' . $phone)->exists()) {
            throw new OlympiadEnrollException('bu telefon raqami bilan talaba allaqachon bor — uni «markazdagi talaba» sifatida qo‘shing.');
        }

        $parentPhone = ReceptionStudent::localPhone($participant->parent_phone) ?: null;

        return $this->enrollment->enroll(
            [
                'name'         => $participant->name,
                'phone'        => $phone,
                'parents_name' => $participant->parent_name,
                'parents_tel'  => $parentPhone,
                'description'  => $participant->notes,
            ],
            [$group->id],
            [],
            [
                'ota' => [
                    'name'    => $participant->parent_name,
                    'phone'   => $parentPhone,
                    'email'   => null,
                    'account' => $parentAccount && $parentPhone !== null,
                ],
            ]
        );
    }
}
