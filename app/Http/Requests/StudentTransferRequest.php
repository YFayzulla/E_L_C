<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for moving a student between groups.
 *
 * Authorisation is intentionally NOT done here: whether the caller actually
 * teaches the student and every posted target group is a relationship check,
 * and it lives in StudentTransferController via AuthorizesGroupAccess.
 */
class StudentTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Payment inputs are typed by humans as "500 000" — strip the separators
     * before `numeric` rejects them.
     */
    protected function prepareForValidation(): void
    {
        $payments = $this->input('group_payment');

        if (! is_array($payments)) {
            return;
        }

        $this->merge([
            'group_payment' => array_map(
                fn ($value) => is_string($value)
                    ? str_replace([' ', ',', "\u{00A0}"], '', $value)
                    : $value,
                $payments
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'group_id' => 'required|array|min:1',
            'group_id.*' => 'required|integer|exists:groups,id',
            'group_payment' => 'nullable|array',
            'group_payment.*' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'group_id.required' => 'Kamida bitta guruh tanlang.',
            'group_id.array' => 'Guruhlar ro‘yxati noto‘g‘ri yuborildi.',
            'group_id.min' => 'Kamida bitta guruh tanlang.',
            'group_id.*.required' => 'Guruh tanlanmagan.',
            'group_id.*.integer' => 'Guruh identifikatori noto‘g‘ri.',
            'group_id.*.exists' => 'Tanlangan guruhlardan biri mavjud emas.',
            'group_payment.array' => 'To‘lov ma’lumotlari noto‘g‘ri yuborildi.',
            'group_payment.*.numeric' => 'Oylik to‘lov raqam bo‘lishi kerak.',
            'group_payment.*.min' => 'Oylik to‘lov manfiy bo‘lishi mumkin emas.',
        ];
    }

    /**
     * Target group ids, de-duplicated and cast to int.
     *
     * @return array<int, int>
     */
    public function targetGroupIds(): array
    {
        $ids = array_map('intval', (array) $this->input('group_id', []));

        return array_values(array_unique($ids));
    }

    /**
     * Submitted monthly payments keyed by group id. A blank field means
     * "keep whatever the pivot already holds" — the service decides.
     *
     * @return array<int|string, int|string|null>
     */
    public function payments(): array
    {
        $payments = $this->input('group_payment', []);

        return is_array($payments) ? $payments : [];
    }
}
