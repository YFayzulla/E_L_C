{{--
    Test natijalari: bitta imtihon — bitta qator. Qaysi ko'nikmalar
    topshirilgani ballari bilan, o'rtachasi va sanasi.

    @param \Illuminate\Support\Collection $tests  Assessment::groupByTest() natijasi
    @param bool $showGroup  guruh ustuni (ixtiyoriy, standart — ha)
--}}
@php
    $showGroup = $showGroup ?? true;
    $tone = fn (?int $mark) => $mark === null ? 'secondary' : ($mark >= 80 ? 'success' : ($mark >= 60 ? 'warning' : 'danger'));
@endphp

@if($tests->isEmpty())
    <div class="empty-state">
        <i class="bx bx-clipboard"></i>
        <h6>Natija yo‘q</h6>
        <p class="mb-0">Hozircha test natijalari kiritilmagan.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th style="width: 1%;">#</th>
                <th>Test</th>
                @if($showGroup)
                    <th class="d-none d-md-table-cell">Guruh</th>
                @endif
                <th>Ko‘nikmalar</th>
                <th class="text-center">O‘rtacha</th>
                <th class="text-end">Sana</th>
            </tr>
            </thead>
            <tbody>
            @foreach($tests as $test)
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td>
                        <div class="fw-semibold">{{ $test->test_name }}</div>
                        @if($test->comment)
                            <small class="text-muted">{{ $test->comment }}</small>
                        @endif
                    </td>
                    @if($showGroup)
                        <td class="d-none d-md-table-cell">{{ $test->group ?: '—' }}</td>
                    @endif
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($test->marks as $item)
                                <span class="badge bg-label-secondary fw-normal text-nowrap">
                                    {{ $item['label'] }}
                                    <strong class="text-{{ $tone($item['percent']) }} ms-1" title="{{ $item['percent'] }}%">{{ $item['mark'] }}/{{ $item['max'] }}</strong>
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-label-{{ $tone($test->average) }}" style="font-size: .9rem;">
                            {{ $test->average === null ? '—' : $test->average . '%' }}
                        </span>
                    </td>
                    <td class="text-end text-nowrap">{{ $test->date?->format('d.m.Y') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
