<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\StudentDiscount;
use App\Models\User;
use App\Services\DiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Chegirmalar (faqat admin): bitta talabaga yoki guruhdagi barcha talabaga,
 * foizda, doimiy yoki muddatli. Summani DiscountService qayta hisoblaydi.
 */
class DiscountController extends Controller
{
    public function __construct(private DiscountService $discounts)
    {
    }

    /** Bitta talabaga chegirma. */
    public function store(Request $request, int $student)
    {
        $model = User::role('student')->inCurrentCentre()->findOrFail($student);

        $groupIds = $model->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all();
        $data = $this->validated($request, $groupIds);

        try {
            $this->discounts->give(
                $model,
                (int) $data['percent'],
                filled($data['group_id'] ?? null) ? (int) $data['group_id'] : null,
                $data['starts_on'],
                $data['period'] === 'limited' ? $data['ends_on'] : null,
                $data['reason'] ?? null
            );
        } catch (\Throwable $e) {
            Log::error('DiscountController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Chegirmani saqlashda xatolik yuz berdi.');
        }

        return redirect()->back()->with('success', "{$model->name} uchun {$data['percent']}% chegirma saqlandi. Oylik summa qayta hisoblandi.");
    }

    /** Guruhdagi barcha (hozirgi) talabaga chegirma. */
    public function storeForGroup(Request $request, int $group)
    {
        $model = Group::findOrFail($group);
        $data = $this->validated($request, null);

        try {
            $count = $this->discounts->giveToGroup(
                $model,
                (int) $data['percent'],
                $data['starts_on'],
                $data['period'] === 'limited' ? $data['ends_on'] : null,
                $data['reason'] ?? null
            );
        } catch (\Throwable $e) {
            Log::error('DiscountController@storeForGroup error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Chegirmani saqlashda xatolik yuz berdi.');
        }

        if ($count === 0) {
            return redirect()->back()->with('warning', 'Guruhda talaba yo‘q — chegirma berilmadi.');
        }

        return redirect()->back()->with('success', "«{$model->name}» guruhidagi {$count} ta talabaga {$data['percent']}% chegirma berildi.");
    }

    public function cancel(int $discount)
    {
        $model = StudentDiscount::whereNull('cancelled_at')->findOrFail($discount);

        try {
            $this->discounts->cancel($model);
        } catch (\Throwable $e) {
            Log::error('DiscountController@cancel error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Chegirmani bekor qilishda xatolik yuz berdi.');
        }

        return redirect()->back()->with('success', 'Chegirma bekor qilindi. Oylik summa qayta hisoblandi.');
    }

    /**
     * @param  array<int, int>|null  $groupIds  talabaning guruhlari (null — guruh tanlanmaydi)
     */
    private function validated(Request $request, ?array $groupIds): array
    {
        $rules = [
            'percent'   => ['required', 'integer', 'min:1', 'max:100'],
            'period'    => ['required', 'in:permanent,limited'],
            'starts_on' => ['required', 'date'],
            'ends_on'   => ['nullable', 'required_if:period,limited', 'date', 'after_or_equal:starts_on'],
            'reason'    => ['nullable', 'string', 'max:255'],
        ];

        if ($groupIds !== null) {
            $rules['group_id'] = ['nullable', 'integer', 'in:' . implode(',', $groupIds ?: [0])];
        }

        return $request->validate($rules, [
            'percent.required'       => 'Chegirma foizini kiriting.',
            'percent.min'            => 'Chegirma kamida 1% bo‘lishi kerak.',
            'percent.max'            => 'Chegirma 100% dan oshmasligi kerak.',
            'starts_on.required'     => 'Boshlanish sanasini kiriting.',
            'ends_on.required_if'    => 'Muddatli chegirma uchun tugash sanasini kiriting.',
            'ends_on.after_or_equal' => 'Tugash sanasi boshlanishdan oldin bo‘lmasligi kerak.',
            'group_id.in'            => 'Talaba bu guruhda o‘qimaydi.',
        ]);
    }
}
