{{--
    Monthly progress trend.

    @include('partials.progress-chart', [
        'chartId'     => 'studentProgress',      // unique per page
        'buckets'     => $progress['buckets'],   // ['Y-m' => int|null]
        'chartLabel'  => 'O‘zlashtirish',        // optional series name
        'chartHeight' => 260,                    // optional
    ])

    ApexCharts is already loaded by template/master.blade.php — no new dependency
    and nothing in <head>. Colours are read from the CSS custom properties at
    runtime and refreshed on the 'themechange' event theme.js dispatches, so the
    chart follows the light/dark switch instead of freezing on light axes.

    Months with no data at all are null and render as genuine gaps in the line —
    never as a zero. The same numbers are repeated in a table underneath so the
    figures survive with JavaScript off and for screen readers.
--}}
@php
    $chartId     = $chartId ?? 'progressChart';
    $buckets     = $buckets ?? [];
    $chartLabel  = $chartLabel ?? 'O‘zlashtirish';
    $chartHeight = (int) ($chartHeight ?? 260);

    $categories = [];
    $series     = [];

    foreach ($buckets as $bucketKey => $bucketValue) {
        $categories[] = \App\Services\ProgressService::monthLabel((string) $bucketKey);
        $series[]     = $bucketValue === null ? null : (int) $bucketValue;
    }

    $hasData = count(array_filter($series, fn($v) => $v !== null)) > 0;
@endphp

@if($hasData)
    <div id="{{ $chartId }}" style="min-height: {{ $chartHeight }}px;" aria-hidden="true"></div>
@else
    <div class="empty-state py-4">
        <i class="bx bx-line-chart"></i>
        <h6>Dinamika hali hisoblanmadi</h6>
        <p class="mb-0">Davomat, test, uy vazifasi yoki ko‘nikma bahosi kiritilgach ko‘rsatkich paydo bo‘ladi.</p>
    </div>
@endif

<div class="table-responsive">
    <table class="table table-sm mb-0">
        <caption class="visually-hidden">{{ $chartLabel }} — oylar bo‘yicha</caption>
        <thead>
        <tr>
            <th scope="row" class="text-muted fw-normal" style="min-width: 8rem;">Oy</th>
            @foreach($categories as $category)
                <th class="text-center" style="min-width: 6.5rem;">{{ $category }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        <tr>
            <th scope="row" class="text-muted fw-normal">{{ $chartLabel }}</th>
            @foreach($series as $value)
                <td class="text-center">
                    @if($value === null)
                        <span class="text-muted" title="Ma’lumot yo‘q">—</span>
                    @else
                        <span class="badge bg-label-{{ $value >= config('grading.bands.good', 80) ? 'success' : ($value >= config('grading.bands.ok', 60) ? 'warning' : 'danger') }}">
                            {{ $value }}
                        </span>
                    @endif
                </td>
            @endforeach
        </tr>
        </tbody>
    </table>
</div>

@if($hasData)
    @push('scripts')
        <script>
            (function () {
                var el = document.getElementById(@json($chartId));

                if (!el || typeof ApexCharts === 'undefined') {
                    return;
                }

                var data = @json($series);
                var categories = @json($categories);
                var label = @json($chartLabel);

                function token(name, fallback) {
                    var value = getComputedStyle(document.documentElement).getPropertyValue(name);
                    return (value || '').trim() || fallback;
                }

                function mode() {
                    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                }

                function palette() {
                    return {
                        primary: token('--app-primary', '#4f46e5'),
                        muted: token('--app-text-muted', '#667085'),
                        border: token('--app-border', '#e6e8f0')
                    };
                }

                var colours = palette();

                var chart = new ApexCharts(el, {
                    chart: {
                        type: 'area',
                        height: {{ $chartHeight }},
                        parentHeightOffset: 0,
                        fontFamily: 'inherit',
                        background: 'transparent',
                        toolbar: {show: false},
                        zoom: {enabled: false}
                    },
                    theme: {mode: mode()},
                    series: [{name: label, data: data}],
                    colors: [colours.primary],
                    stroke: {curve: 'smooth', width: 3},
                    markers: {size: 4, strokeWidth: 0, hover: {size: 6}},
                    dataLabels: {enabled: false},
                    fill: {
                        type: 'gradient',
                        gradient: {shadeIntensity: 1, opacityFrom: 0.32, opacityTo: 0.02, stops: [0, 90, 100]}
                    },
                    grid: {
                        borderColor: colours.border,
                        strokeDashArray: 4,
                        padding: {left: 8, right: 8, top: 0}
                    },
                    xaxis: {
                        categories: categories,
                        axisBorder: {show: false},
                        axisTicks: {show: false},
                        tooltip: {enabled: false},
                        labels: {style: {colors: colours.muted, fontSize: '12px'}}
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        tickAmount: 4,
                        labels: {
                            style: {colors: colours.muted, fontSize: '12px'},
                            formatter: function (value) {
                                return Math.round(value);
                            }
                        }
                    },
                    tooltip: {
                        theme: mode(),
                        y: {
                            formatter: function (value) {
                                return value === null ? 'Ma’lumot yo‘q' : Math.round(value) + ' / 100';
                            }
                        }
                    },
                    noData: {text: 'Ma’lumot yo‘q', style: {color: colours.muted}}
                });

                chart.render();

                // theme.js fires this on every light/dark switch; without it the
                // axes keep their old colours on the new background.
                window.addEventListener('themechange', function () {
                    var next = palette();

                    chart.updateOptions({
                        theme: {mode: mode()},
                        colors: [next.primary],
                        grid: {borderColor: next.border},
                        xaxis: {labels: {style: {colors: next.muted}}},
                        yaxis: {labels: {style: {colors: next.muted}}},
                        tooltip: {theme: mode()},
                        noData: {style: {color: next.muted}}
                    }, false, false);
                });
            })();
        </script>
    @endpush
@endif
