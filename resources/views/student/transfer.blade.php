@extends('template.master')

@section('title', 'Guruhni o‘zgartirish')
@section('subtitle', $student->name)

@section('content')

    @php
        /** @var \App\Models\User $student */
        // The Kutish zali is never pre-selected: assigning a waiting student to a
        // real group is the main use of this page, and since the posted list is
        // absolute, leaving it ticked would silently keep the student waiting.
        // An admin can still tick it back on explicitly.
        $defaultSelection = array_values(array_diff(
            array_intersect($currentIds, $selectableIds),
            [$waitingRoomId]
        ));

        // old() replays whatever was POSTed, so a tampered scalar `group_id=5`
        // fails the |array rule and comes back here as a string — array_map()
        // on a string is a fatal TypeError. Cast before touching it.
        $selected      = array_map('intval', (array) old('group_id', $defaultSelection));
        $oldPayments   = (array) old('group_payment', []);
        $currentPay    = $student->groups->mapWithKeys(fn ($g) => [(int) $g->id => (int) ($g->pivot->payment ?? 0)]);
        $lockedTotal   = $lockedGroups->sum(fn ($g) => (int) ($g->pivot->payment ?? 0));
    @endphp

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('student.show', $student->id) }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div class="min-w-0">
                <h4 class="mb-1">{{ $student->name }}</h4>
                <div class="page-sub">
                    {{ $student->groups->pluck('name')->implode(', ') ?: 'Guruhga biriktirilmagan' }}
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
        <i class="bx bx-error-circle" style="font-size: 1.25rem; line-height: 1.4;"></i>
        <div>
            <strong>Diqqat.</strong> Tanlangan guruhlar talabaning <u>yakuniy</u> ro‘yxati bo‘ladi:
            ro‘yxatga kirmagan har bir guruhdan talaba chiqariladi.
            Saqlaganingizdan so‘ng <strong>oylik to‘lov va qarzdorlik qayta hisoblanadi</strong>.
            Eski davomat yozuvlari o‘z guruhida qoladi va o‘zgarmaydi.
        </div>
    </div>

    @php
        $confirmText = $student->name . ' uchun guruhlar yangilansinmi?'
            . "\n\nOylik to‘lov va qarzdorlik qayta hisoblanadi.";
    @endphp

    <form action="{{ route('student.transfer', $student->id) }}" method="post" id="transferForm"
          onsubmit="return confirm({{ \Illuminate\Support\Js::from($confirmText) }});">
        @csrf

        <div class="row g-4">

            {{-- Hozirgi holat --}}
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">Hozirgi guruhlar</div>
                    <div class="card-body">
                        @forelse($student->groups as $group)
                            <div class="d-flex align-items-start gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                <span class="stat-icon"><i class="bx bx-group"></i></span>
                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="mb-1">{{ $group->name }}</h6>
                                    <div class="text-muted" style="font-size: .82rem;">
                                        <i class="bx bx-wallet me-1"></i>
                                        {{ number_format((int) ($group->pivot->payment ?? 0), 0, '.', ' ') }} so‘m
                                    </div>
                                </div>
                                @if(in_array((int) $group->id, $selectableIds, true))
                                    <span class="badge bg-label-info">O‘zgartirish mumkin</span>
                                @elseif(in_array((int) $group->id, $lockedIds, true))
                                    <span class="badge bg-label-secondary">Saqlanadi</span>
                                @else
                                    <span class="badge bg-label-warning">Chiqariladi</span>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state py-4">
                                <i class="bx bx-group"></i>
                                <h6>Guruh yo‘q</h6>
                                <p class="mb-0">Talaba hozircha hech qaysi guruhga biriktirilmagan.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="stat-label mb-0">Hozirgi oylik to‘lov</span>
                            <span class="fw-semibold">
                                {{ number_format((int) $student->should_pay, 0, '.', ' ') }} so‘m
                            </span>
                        </div>
                        @if($lockedGroups->isNotEmpty())
                            <div class="text-muted mt-2" style="font-size: .8rem;">
                                <i class="bx bx-lock-alt me-1"></i>
                                Sizga biriktirilmagan {{ $lockedGroups->count() }} ta guruh
                                ({{ $lockedGroups->pluck('name')->implode(', ') }})
                                o‘zgarishsiz saqlanadi —
                                {{ number_format($lockedTotal, 0, '.', ' ') }} so‘m.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Yangi guruhlar --}}
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">Yangi guruhlar</div>
                    <div class="card-body d-flex flex-column">

                        @if($targets->isEmpty())
                            <div class="empty-state py-4">
                                <i class="bx bx-block"></i>
                                <h6>Mos guruh yo‘q</h6>
                                <p class="mb-0">
                                    Sizga biriktirilgan guruhlar topilmadi. Administratorga murojaat qiling.
                                </p>
                            </div>
                        @else
                            <label class="form-label" for="group_id">
                                Guruhlarni tanlang <span class="text-danger">*</span>
                            </label>
                            <select id="group_id" name="group_id[]"
                                    class="choices form-select @error('group_id') is-invalid @enderror"
                                    multiple data-placeholder="Guruh qidiring…">
                                @foreach($targets as $group)
                                    <option value="{{ $group->id }}" @selected(in_array((int) $group->id, $selected, true))>
                                        {{ $group->name }}
                                        @if($group->teachers->isNotEmpty()) — {{ $group->teachers->pluck('name')->implode(', ') }} @endif
                                        ({{ $group->members_count }} talaba)
                                    </option>
                                @endforeach
                            </select>
                            @error('group_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('group_id.*') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror

                            <div class="form-text mt-2">
                                @if($isAdmin)
                                    Barcha guruhlar, shu jumladan Kutish zali ham mavjud.
                                @else
                                    Faqat sizga biriktirilgan guruhlar ko‘rsatilmoqda. Kutish zaliga
                                    ko‘chirish administrator huquqini talab qiladi.
                                @endif
                            </div>

                            <hr class="my-4">

                            <div class="stat-label mb-2">Oylik to‘lov (guruh bo‘yicha)</div>

                            <div id="paymentRows">
                                @foreach($targets as $group)
                                    @php
                                        $isSelected = in_array((int) $group->id, $selected, true);
                                        $value = $oldPayments[$group->id]
                                            ?? $currentPay[(int) $group->id]
                                            ?? (int) $group->monthly_payment;
                                    @endphp
                                    <div class="row g-2 align-items-center mb-2 js-payment-row {{ $isSelected ? '' : 'd-none' }}"
                                         data-group="{{ $group->id }}">
                                        <div class="col-sm-6">
                                            <label class="form-label mb-0" for="payment-{{ $group->id }}">
                                                {{ $group->name }}
                                            </label>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <input type="text" id="payment-{{ $group->id }}"
                                                       name="group_payment[{{ $group->id }}]"
                                                       class="form-control js-payment @error('group_payment.' . $group->id) is-invalid @enderror"
                                                       value="{{ $value }}" inputmode="numeric">
                                                <span class="input-group-text">so‘m</span>
                                            </div>
                                            @error('group_payment.' . $group->id)
                                                <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="metric-box mt-3">
                                <div class="metric-label">Yangi oylik to‘lov (jami)</div>
                                <div class="metric-value" id="paymentTotal">0 so‘m</div>
                            </div>

                            <div class="mt-auto pt-4 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-transfer me-1"></i> Ko‘chirish
                                </button>
                                <a href="{{ route('student.show', $student->id) }}" class="btn btn-outline-secondary">
                                    Bekor qilish
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                var select = document.getElementById('group_id');
                var rows = document.querySelectorAll('.js-payment-row');
                var total = document.getElementById('paymentTotal');

                if (!select || !total) {
                    return;
                }

                function chosen() {
                    return Array.prototype.filter
                        .call(select.options, function (option) { return option.selected; })
                        .map(function (option) { return option.value; });
                }

                function spaced(number) {
                    return String(number).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                }

                function refresh() {
                    var ids = chosen();
                    var sum = 0;

                    Array.prototype.forEach.call(rows, function (row) {
                        var active = ids.indexOf(row.dataset.group) !== -1;
                        row.classList.toggle('d-none', !active);

                        if (!active) {
                            return;
                        }

                        var input = row.querySelector('.js-payment');
                        var value = parseInt(String(input && input.value ? input.value : '0').replace(/[^0-9]/g, ''), 10);
                        sum += isNaN(value) ? 0 : value;
                    });

                    total.textContent = spaced(sum) + ' so‘m';
                }

                // Choices.js keeps the underlying <select> in sync and fires these.
                select.addEventListener('change', refresh);
                select.addEventListener('addItem', refresh);
                select.addEventListener('removeItem', refresh);

                document.addEventListener('input', function (event) {
                    if (event.target && event.target.classList.contains('js-payment')) {
                        refresh();
                    }
                });

                refresh();
            })();
        </script>
    @endpush

@endsection
