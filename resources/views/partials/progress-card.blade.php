{{--
    O'zlashtirish bo'limi — bitta karta ichida uchta narsa:

      1. joriy oy ko'rsatkichi va o'tgan oyga nisbatan o'zgarish
      2. oylik dinamika chizmasi
      3. ko'rsatkich tarkibi (davomat / test / uy vazifasi / ko'nikma)

    @include('partials.progress-card', [
        'progress' => $progress,          // ProgressService::forStudent() natijasi
        'chartId'  => 'childProgress',    // sahifada TAKRORLANMAS bo'lishi shart
        'title'    => 'O‘zlashtirish',    // ixtiyoriy
        'link'     => route('student.progress'),   // ixtiyoriy "Batafsil" havolasi
    ])

    Diagramma partials.progress-chart orqali chiziladi: u mavzu almashganda
    ranglarni qayta o'qiydi va JS o'chirilgan bo'lsa o'sha raqamlarni jadval
    ko'rinishida qoldiradi.

    Ma'lumot yo'q oy — chizmada haqiqiy uzilish, nol EMAS. Nol "keldi, lekin
    baho olmadi" degani bo'lardi; uzilish esa "hech narsa bo'lmagan".
--}}
@php
    $progress = $progress ?? [];
    $chartId  = $chartId ?? 'progressCard';
    $title    = $title ?? 'O‘zlashtirish';
    $link     = $link ?? null;

    $good = config('grading.bands.good', 80);
    $ok   = config('grading.bands.ok', 60);
    $tone = fn ($v) => $v === null
        ? 'secondary'
        : ($v >= $good ? 'success' : ($v >= $ok ? 'warning' : 'danger'));

    $weights = (array) config('grading.progress_weights', []);

    $componentMeta = [
        'attendance' => ['label' => 'Davomat',     'icon' => 'bx-calendar-check'],
        'tests'      => ['label' => 'Testlar',     'icon' => 'bx-clipboard'],
        'homework'   => ['label' => 'Uy vazifasi', 'icon' => 'bx-book'],
        'skills'     => ['label' => 'Ko‘nikmalar', 'icon' => 'bx-award'],
    ];

    $current      = $progress['current'] ?? null;
    $delta        = $progress['delta'] ?? null;
    $currentMonth = ($progress['current_month'] ?? null)
        ? \App\Services\ProgressService::monthLabel($progress['current_month'])
        : null;
@endphp

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span>{{ $title }}</span>
        <div class="d-flex align-items-center gap-2">
            @if($currentMonth)
                <span class="text-muted" style="font-size: .8rem;">{{ $currentMonth }}</span>
            @endif
            @if($link)
                <a href="{{ $link }}" class="btn btn-sm btn-outline-secondary">Batafsil</a>
            @endif
        </div>
    </div>

    <div class="card-body">
        <div class="row g-4">

            {{-- Joriy ko'rsatkich --}}
            <div class="col-12 col-lg-3">
                <div class="stat-card h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="stat-label">Umumiy ko‘rsatkich</div>
                        <div class="stat-value text-{{ $tone($current) }}" style="font-size: 2.4rem;">
                            {{ $current ?? '—' }}
                        </div>

                        <div class="mt-1">
                            @if($delta === null)
                                <span class="delta is-flat" style="font-size: .78rem;">
                                    solishtirish uchun ma’lumot yo‘q
                                </span>
                            @elseif($delta > 0)
                                <span class="delta is-up"><i class="bx bx-up-arrow-alt"></i>+{{ $delta }}</span>
                            @elseif($delta < 0)
                                <span class="delta is-down"><i class="bx bx-down-arrow-alt"></i>{{ $delta }}</span>
                            @else
                                <span class="delta is-flat"><i class="bx bx-minus"></i>o‘zgarishsiz</span>
                            @endif
                        </div>

                        <div class="score-bar is-{{ $tone($current) }} mt-3">
                            <span style="width: {{ $current ?? 0 }}%;"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Oylik dinamika --}}
            <div class="col-12 col-lg-9">
                <div class="stat-label mb-2">Oylik dinamika</div>
                @include('partials.progress-chart', [
                    'chartId'     => $chartId,
                    'buckets'     => $progress['buckets'] ?? [],
                    'chartLabel'  => 'O‘zlashtirish',
                    'chartHeight' => 220,
                ])
            </div>

            {{-- Tarkibi --}}
            <div class="col-12">
                <div class="stat-label mb-2">Ko‘rsatkich tarkibi</div>
                <div class="row g-3">
                    @foreach($componentMeta as $key => $meta)
                        @php $value = $progress['components'][$key] ?? null; @endphp
                        <div class="col-6 col-md-3">
                            <div class="metric-box h-100">
                                <i class="bx {{ $meta['icon'] }} text-{{ $tone($value) }}"
                                   style="font-size: 1.35rem;"></i>
                                <div class="metric-value text-{{ $tone($value) }}">{{ $value ?? '—' }}</div>
                                <div class="metric-label">{{ $meta['label'] }}</div>
                                <div class="score-bar is-{{ $tone($value) }} mt-2">
                                    <span style="width: {{ $value ?? 0 }}%;"></span>
                                </div>
                                <div class="metric-label mt-1">
                                    ulushi {{ (int) round(($weights[$key] ?? 0) * 100) }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="text-muted mt-3 mb-0" style="font-size: .78rem;">
                    <i class="bx bx-info-circle me-1"></i>
                    Ko‘rsatkich davomat, test, uy vazifasi va ko‘nikma baholaridan
                    hisoblanadi. Ma’lumot bo‘lmagan tarkibiy qism hisobga olinmaydi.
                </p>
            </div>
        </div>
    </div>
</div>
