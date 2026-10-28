{{--
    Davomat yozuvi: Sababli / Sababsiz / Kechikdi belgisi, o'qituvchi izohi
    va uni kim, qachon yozgani. O'qituvchi, admin va reception jurnallari bir
    xil ko'rinishi uchun bitta joyda.

    @param \App\Models\Attendance $record
    @param bool $withBadge  belgi ham chiqsinmi (alohida "Holat" ustuni bo'lsa — yo'q)
    @param bool $withTime   izoh yozilgan vaqt (standart — ha)
--}}
@php
    $withBadge = $withBadge ?? true;
    $withTime = $withTime ?? true;
@endphp

@if($withBadge)
    <span class="badge bg-label-{{ $record->kindTone() }}">{{ $record->kindLabel() }}</span>
@endif

@if($record->hasComment())
    <div class="text-wrap {{ $withBadge ? 'mt-1' : '' }}">{{ $record->reason }}</div>
    @if($record->reasonWriter || $record->reason_written_at)
        <small class="text-muted d-block">
            {{ $record->reasonWriter?->name ?? '—' }}
            @if($withTime && $record->reason_written_at) · {{ $record->reason_written_at->format('d.m.Y H:i') }} @endif
        </small>
    @endif
@elseif(! $withBadge)
    <span class="text-muted">—</span>
@endif
