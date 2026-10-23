{{--
    Joriy oy bo'yicha to'lov holati: to'langan bo'lsa "qarz yo'q", aks holda
    qolgan qarz summasi. Hisob User::currentDebt() da — talaba va ota-ona
    sahifalari bir xil raqamni ko'rsatishi uchun.

    @param \App\Models\User $student
--}}
@php
    $debt = $student->currentDebt();
    $debtMonths = $student->debtMonths();
    $monthly = (int) ($student->deptStudent?->dept ?: $student->should_pay);
@endphp

<div class="d-flex align-items-center justify-content-between py-2 border-bottom">
    <span class="text-muted">Oylik to‘lov</span>
    <span class="fw-semibold">{{ $monthly ? number_format($monthly, 0, '.', ' ') . ' so‘m' : '—' }}</span>
</div>

<div class="d-flex align-items-center justify-content-between py-2">
    <span class="text-muted">{{ now()->translatedFormat('F') }} oyi</span>
    @if($debt > 0)
        <span class="text-end">
            <span class="badge bg-label-danger">Qarz</span>
            <span class="d-block fw-semibold text-danger mt-1">
                {{ number_format($debt, 0, '.', ' ') }} so‘m
            </span>
            @if($debtMonths > 1)
                <small class="text-muted">{{ $debtMonths }} oy uchun</small>
            @endif
        </span>
    @else
        <span class="text-end">
            <span class="badge bg-label-success">To‘langan</span>
            <small class="d-block text-success mt-1">Qarz yo‘q</small>
        </span>
    @endif
</div>
