{{--
    Talaba tarixi (PDF) — talaba sahifasidagi bo'limlar shu tartibda:
    ma'lumotlar, izohlar, ota-ona, chegirmalar, o'zlashtirish, to'lovlar,
    guruh tarixi, test natijalari va davomat (kelmagan / kechikkan kunlar).
--}}
<!doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>{{ $student->name }} — talaba tarixi</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 17px; margin: 0; }
        h2 { font-size: 12px; margin: 18px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #6366f1; color: #312e81; page-break-after: avoid; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .muted { color: #6b7280; }
        .small { font-size: 8.5px; }
        table { width: 100%; border-collapse: collapse; }
        .grid th, .grid td { border: 1px solid #e5e7eb; padding: 4px 5px; text-align: left; vertical-align: top; }
        .grid th { background: #f3f4f6; font-size: 8.5px; text-transform: uppercase; color: #4b5563; }
        .grid tr:nth-child(even) td { background: #fafafa; }
        .c { text-align: center !important; }
        .r { text-align: right !important; }
        .nowrap { white-space: nowrap; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8.5px; }
        .b-success { background: #dcfce7; color: #166534; }
        .b-warning { background: #fef3c7; color: #92400e; }
        .b-danger  { background: #fee2e2; color: #991b1b; }
        .b-primary { background: #e0e7ff; color: #3730a3; }
        .b-info    { background: #e0f2fe; color: #075985; }
        .b-secondary { background: #f3f4f6; color: #374151; }
        .info td { padding: 3px 0; vertical-align: top; }
        .info .k { width: 30%; color: #6b7280; }
        .metric { border: 1px solid #e5e7eb; padding: 6px; text-align: center; }
        .metric .v { font-size: 15px; font-weight: bold; }
        .comments li { margin-bottom: 3px; }
        .empty { color: #9ca3af; font-style: italic; padding: 4px 0; }
    </style>
</head>
<body>
@php
    $tone = fn (?int $v) => $v === null ? 'secondary'
        : ($v >= config('grading.bands.good', 80) ? 'success'
        : ($v >= config('grading.bands.ok', 60) ? 'warning' : 'danger'));
    $money = fn ($v) => number_format((int) $v, 0, '', ' ');
    $centre = \App\Models\Centre::current();
    $comments = collect(preg_split('/\r?\n/', (string) $student->description))->map(fn ($c) => trim($c))->filter()->values();
@endphp

{{-- Sarlavha --}}
<table>
    <tr>
        <td>
            <h1>{{ $student->name }}</h1>
            <div class="muted">Talabaning tizimdagi umumiy tarixi{{ $centre ? ' · ' . $centre->name : '' }}</div>
        </td>
        <td class="r muted small nowrap" style="vertical-align: top;">
            Tuzilgan sana: {{ now()->format('d.m.Y H:i') }}
        </td>
    </tr>
</table>

{{-- Talaba ma'lumotlari --}}
<h2>Talaba ma’lumotlari</h2>
<table>
    <tr>
        <td style="vertical-align: top;">
            <table class="info">
                <tr><td class="k">F.I.Sh.</td><td><strong>{{ $student->name }}</strong></td></tr>
                <tr><td class="k">Telefon</td><td>{{ $student->phone ? '+' . $student->phone : '—' }}</td></tr>
                <tr><td class="k">Manzil</td><td>{{ $student->location ?: '—' }}</td></tr>
                <tr><td class="k">Tug‘ilgan sana</td><td>{{ $student->date_born ?: '—' }}</td></tr>
                <tr><td class="k">Oxirgi test natijasi</td><td>{{ $student->mark ?? '—' }}</td></tr>
                <tr><td class="k">Hozirgi guruhlari</td><td>{{ $student->groups->pluck('name')->implode(', ') ?: '—' }}</td></tr>
            </table>
        </td>
        @if($photo)
            <td style="width: 110px; vertical-align: top; text-align: right;">
                <img src="{{ $photo }}" alt="" style="max-width: 100px; max-height: 120px;">
            </td>
        @endif
    </tr>
</table>

<h2>Izohlar</h2>
@if($comments->isEmpty())
    <div class="empty">Izoh yozilmagan.</div>
@else
    <ul class="comments" style="margin: 0; padding-left: 14px;">
        @foreach($comments as $comment)
            <li>{{ $comment }}</li>
        @endforeach
    </ul>
@endif

{{-- Ota-ona va vasiylar --}}
<h2>Ota-ona va vasiylar</h2>
@if(empty($guardians))
    <div class="empty">Ota-ona ma’lumoti kiritilmagan.</div>
@else
    <table class="grid">
        <thead><tr><th>Kim</th><th>Ism</th><th>Telefon</th><th>Pochta</th><th>Kabinet</th></tr></thead>
        @foreach($guardians as $relation => $guardian)
            <tr>
                <td>{{ \App\Services\ParentAccountService::LABELS[$relation] ?? 'Vasiy' }}</td>
                <td>{{ $guardian['name'] ?: '—' }}</td>
                <td class="nowrap">{{ filled($guardian['phone']) ? '+' . $guardian['phone'] : '—' }}</td>
                <td>{{ $guardian['email'] ?: '—' }}</td>
                <td>{{ ($guardian['account'] ?? false) ? 'Kira oladi' : 'Faqat aloqa' }}</td>
            </tr>
        @endforeach
    </table>
@endif

{{-- Chegirmalar --}}
@if($discounts->isNotEmpty())
    <h2>Chegirmalar</h2>
    <table class="grid">
        <thead><tr><th class="c">Foiz</th><th>Holat</th><th>Guruh</th><th>Muddat</th><th>Sabab</th></tr></thead>
        @foreach($discounts as $discount)
            <tr>
                <td class="c"><strong>{{ $discount->percent }}%</strong></td>
                <td><span class="badge b-{{ $discount->stateTone() }}">{{ $discount->stateLabel() }}</span></td>
                <td>{{ $discount->group?->name ?? 'Barcha guruhlari' }}</td>
                <td class="nowrap">{{ $discount->periodLabel() }}</td>
                <td>{{ $discount->reason ?: '—' }}</td>
            </tr>
        @endforeach
    </table>
@endif

{{-- O'zlashtirish --}}
@if(! empty($progress))
    <h2>O‘zlashtirish dinamikasi
        @if($progress['current'] !== null)
            <span class="badge b-{{ $tone($progress['current']) }}" style="margin-left: 6px;">Joriy: {{ $progress['current'] }}</span>
        @endif
    </h2>
    <table style="border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px;">
        <tr>
            @foreach(['attendance' => 'Davomat', 'homework' => 'Uy vazifa', 'skills' => 'Dars faoliyati', 'tests' => 'Test'] as $key => $label)
                @php $value = $progress['components'][$key] ?? null; @endphp
                <td class="metric" style="width: 25%;">
                    <div class="v" style="color: {{ ['success' => '#16a34a', 'warning' => '#d97706', 'danger' => '#dc2626', 'secondary' => '#9ca3af'][$tone($value)] }};">{{ $value ?? '—' }}</div>
                    <div class="muted small">{{ $label }}</div>
                </td>
            @endforeach
        </tr>
    </table>
    @if(($progress['basis'] ?? null) === 'attendance_only')
        <div class="muted small" style="margin-top: 4px;">Hozircha faqat davomat asosida hisoblangan.</div>
    @endif

    @if(! empty($progress['buckets']))
        <table class="grid" style="margin-top: 8px;">
            <tr>
                <th>Oy</th>
                @foreach(array_keys($progress['buckets']) as $month)
                    <th class="c">{{ \App\Services\ProgressService::monthLabel((string) $month) }}</th>
                @endforeach
            </tr>
            <tr>
                <td>O‘zlashtirish</td>
                @foreach($progress['buckets'] as $value)
                    <td class="c">
                        @if($value === null)
                            <span class="muted">—</span>
                        @else
                            <span class="badge b-{{ $tone((int) $value) }}">{{ (int) $value }}</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        </table>
    @endif
@endif

{{-- To'lovlar --}}
<h2>To‘lovlar tarixi</h2>
@if($payments->isEmpty())
    <div class="empty">To‘lov qilinmagan.</div>
@else
    <table class="grid">
        <thead><tr><th style="width: 4%;">№</th><th class="r">Summa</th><th>Turi</th><th>Izoh</th><th>Sana</th></tr></thead>
        @foreach($payments as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="r nowrap">{{ $money($item->payment) }}</td>
                <td>{{ $item->type_of_money ?: '—' }}</td>
                <td>{{ $item->description ?: '—' }}</td>
                <td class="nowrap">{{ \Carbon\Carbon::parse($item->date ?? $item->created_at)->format('d.m.Y') }}</td>
            </tr>
        @endforeach
        <tr>
            <td></td>
            <td class="r nowrap"><strong>{{ $money($payments->sum('payment')) }}</strong></td>
            <td colspan="3" class="muted">jami</td>
        </tr>
    </table>
@endif

{{-- Guruh tarixi --}}
<h2>Guruhlar tarixi</h2>
@if($groupHistory->isEmpty())
    <div class="empty">Guruh tarixi yo‘q.</div>
@else
    <table class="grid">
        <thead><tr><th style="width: 4%;">№</th><th>Guruh</th><th>Amal</th><th>Kim</th><th>Sana</th></tr></thead>
        @foreach($groupHistory as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->group ?: '—' }}</td>
                <td><span class="badge b-{{ $item->actionTone() }}">{{ $item->actionLabel() }}</span></td>
                <td>{{ $item->actor?->name ?? '—' }}</td>
                <td class="nowrap">{{ $item->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
            </tr>
        @endforeach
    </table>
@endif

{{-- Test natijalari --}}
<h2>Test natijalari</h2>
@if($testResults->isEmpty())
    <div class="empty">Test natijasi kiritilmagan.</div>
@else
    <table class="grid">
        <thead><tr><th style="width: 4%;">№</th><th>Test</th><th>Guruh</th><th>Ko‘nikmalar</th><th class="c">O‘rtacha</th>@if(\App\Services\CefrScale::enabled())<th class="c">Daraja</th>@endif<th class="r">Sana</th></tr></thead>
        @foreach($testResults as $test)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $test->test_name }}</strong>
                    @if($test->comment)<div class="muted small">{{ $test->comment }}</div>@endif
                </td>
                <td>{{ $test->group ?: '—' }}</td>
                <td>
                    @foreach($test->marks as $mark)
                        <span class="badge b-secondary nowrap">{{ $mark['label'] }} <strong>{{ $mark['mark'] }}/{{ $mark['max'] }}</strong></span>
                    @endforeach
                </td>
                <td class="c">
                    @if($test->average === null)
                        —
                    @else
                        <span class="badge b-{{ $tone((int) $test->average) }}">{{ $test->average }}%</span>
                    @endif
                </td>
                @if(\App\Services\CefrScale::enabled())
                    <td class="c"><strong>{{ \App\Services\CefrScale::levelFor($test->average) ?? '—' }}</strong></td>
                @endif
                <td class="r nowrap">{{ $test->date?->format('d.m.Y') }}</td>
            </tr>
        @endforeach
    </table>
@endif

{{-- Davomat --}}
<h2>Davomat: kelmagan va kechikkan kunlar</h2>
@if($attendances->isEmpty())
    <div class="empty">Qoldirilgan dars yo‘q.</div>
@else
    @php
        $late = $attendances->filter(fn ($a) => (int) $a->status === 2)->count();
        $excused = $attendances->filter(fn ($a) => (int) $a->status !== 2 && $a->isExcused())->count();
        $unexcused = $attendances->count() - $late - $excused;
    @endphp
    <div style="margin-bottom: 6px;">
        <span class="badge b-danger">Sababsiz: {{ $unexcused }}</span>
        <span class="badge b-primary">Sababli: {{ $excused }}</span>
        <span class="badge b-warning">Kechikdi: {{ $late }}</span>
    </div>
    <table class="grid">
        <thead><tr><th style="width: 4%;">№</th><th>Sana</th><th>Guruh</th><th>Holat</th><th>Izoh</th></tr></thead>
        @foreach($attendances as $attendance)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="nowrap">{{ $attendance->created_at?->format('d.m.Y') }}</td>
                <td>{{ $attendance->group?->name ?? '—' }}</td>
                <td><span class="badge b-{{ $attendance->kindTone() }}">{{ $attendance->kindLabel() }}</span></td>
                <td>{{ $attendance->reason ?: '—' }}</td>
            </tr>
        @endforeach
    </table>
@endif

</body>
</html>
