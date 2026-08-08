<!DOCTYPE html>
<html lang="uz" class="light-style layout-menu-fixed layout-navbar-fixed" dir="ltr" data-theme="light"
      data-assets-path="{{ asset('assets') }}/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="description" content="ALPHA o'quv markazi boshqaruv tizimi"/>

    @php
        // Heading used by both <title> and the top bar. Views set it with
        // @section('title'); config/navigation.php covers the older screens.
        // Indexed directly, not via config() dot-notation — route names
        // contain dots and would be read as nested keys.
        $titleMap = config('navigation.titles', []);
        [$fallbackTitle, $fallbackSubtitle] = $titleMap[request()->route()?->getName()] ?? [null, null];
    @endphp

    <title>@hasSection('title')@yield('title') · @elseif($fallbackTitle){{ $fallbackTitle }} · @endif{{ config('app.name', 'ALPHA') }}</title>

    {{--
        Applied before any paint so the page never flashes the wrong theme.
        Must stay inline and must stay first.
    --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('alpha-theme');
                var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                var el = document.documentElement;
                el.setAttribute('data-theme', theme);
                el.setAttribute('data-bs-theme', theme);
                el.classList.toggle('dark-style', theme === 'dark');
                el.classList.toggle('light-style', theme !== 'dark');
            } catch (e) {
            }
        })();
    </script>

    <link rel="icon" type="image/png" href="{{ \App\Models\Centre::brandLogo() }}"/>

    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap"
          rel="stylesheet"/>

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}"/>

    <!-- Sneat base -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" class="template-customizer-core-css"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css"/>
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>

    <!-- App theme — must come last so it wins over the vendor sheets -->
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v={{ filemtime(public_path('assets/css/theme.css')) }}"/>

    {{-- Markaz rangi standart mavzu ustiga yoziladi. Faqat to'g'ri hex
         qiymat chiqadi (Centre::brandCssVariables tekshiradi). --}}
    @if(isset($centre) && $centre?->brandCssVariables())
        <style>html[data-theme]{ {!! $centre->brandCssVariables() !!} }</style>
    @endif


    @stack('styles')

    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        @include('template.sidebar')

        <div class="layout-page">

            @include('template.nav')

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    @include('template.partials.impersonation-banner')
                    @include('template.partials.flash')
                    @include('template.partials.verify-email-banner')

                    @yield('content')

                </div>

                <div class="content-backdrop fade"></div>
            </div>
        </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
</div>

<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/js/theme.js') }}?v={{ filemtime(public_path('assets/js/theme.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Choices.js on every .choices select -------------------------------
        function initializeChoices(selects) {
            selects.forEach(function (select) {
                if (select.choicesInstance) {
                    select.choicesInstance.destroy();
                }
                select.choicesInstance = new Choices(select, {
                    removeItemButton: true,
                    searchEnabled: true,
                    placeholder: true,
                    placeholderValue: select.dataset.placeholder || '',
                    noResultsText: 'Natija topilmadi',
                    itemSelectText: '',
                    shouldSort: false,
                    callbackOnInit: function () {
                        var selected = Array.from(select.querySelectorAll('option[selected]')).map(function (o) {
                            return o.value;
                        });
                        if (selected.length) {
                            this.setChoiceByValue(selected);
                        }
                    }
                });
            });
        }

        initializeChoices(Array.from(document.querySelectorAll('.choices:not(.modal .choices)')));

        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                initializeChoices(Array.from(modal.querySelectorAll('.choices')));
            });
        });

        // --- Quick table filter -------------------------------------------------
        // Filters the rows of #myTable as you type. This is a WITHIN-PAGE filter,
        // not a global search — so on a page with no filterable table the box is
        // removed rather than left sitting there doing nothing, which reads as a
        // broken control.
        var quickSearch = document.getElementById('myInput');
        var quickTable = document.getElementById('myTable');

        if (quickSearch && !quickTable) {
            var box = quickSearch.closest('.nav-item') || quickSearch.parentElement;
            if (box) {
                box.style.display = 'none';
            }
        } else if (quickSearch && quickTable) {
            var rows = quickTable.querySelectorAll('tr');

            var run = function () {
                var needle = quickSearch.value.trim().toLowerCase();
                var shown = 0;

                rows.forEach(function (row) {
                    var hit = !needle || row.textContent.toLowerCase().indexOf(needle) > -1;
                    row.style.display = hit ? '' : 'none';
                    if (hit) shown++;
                });

                // Tell the user when a filter hides everything, instead of
                // leaving them staring at an empty table.
                var empty = document.getElementById('quick-filter-empty');
                if (!empty) {
                    empty = document.createElement('div');
                    empty.id = 'quick-filter-empty';
                    empty.className = 'empty-state';
                    empty.innerHTML = '<i class="bx bx-search-alt"></i><h6>Hech narsa topilmadi</h6>' +
                        '<p class="mb-0">Qidiruvni o‘zgartiring yoki tozalang.</p>';
                    empty.style.display = 'none';
                    quickTable.closest('table').parentElement.appendChild(empty);
                }
                empty.style.display = (needle && shown === 0) ? '' : 'none';
                quickTable.closest('table').style.display = (needle && shown === 0) ? 'none' : '';
            };

            quickSearch.addEventListener('keyup', run);
            quickSearch.addEventListener('search', run);
        }

        // --- Keyboard shortcuts -------------------------------------------------
        document.addEventListener('keydown', function (e) {
            // "/" focuses search, unless already typing
            var typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);
            if (e.key === '/' && !typing && quickSearch) {
                e.preventDefault();
                quickSearch.focus();
            }
        });
    });
</script>

@stack('scripts')
</body>
</html>
