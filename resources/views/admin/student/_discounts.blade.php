{{--
    Talaba sahifasi: chegirmalar (admin). Har bir guruh bo'yicha asl narx,
    chegirma va to'lanadigan summa; chegirmalar tarixi va yangi chegirma.
--}}
@php
    $activeDiscounts = $discounts->filter(fn ($d) => $d->state() === 'active');
@endphp

<div class="card mb-4" id="discounts">
    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
            <h5 class="mb-0">Chegirmalar</h5>
            <small class="text-muted">Foizda; doimiy yoki muddatli. Bir vaqtda bir nechtasi bo‘lsa — eng kattasi qo‘llanadi.</small>
        </div>
        @if($activeDiscounts->isNotEmpty())
            <span class="badge bg-label-success">Amalda: {{ $activeDiscounts->max('percent') }}%</span>
        @endif
    </div>

    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-6">
                {{-- Guruh bo'yicha: asl narx → to'lanadigan summa --}}
                @if($student->groups->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr>
                                <th>Guruh</th>
                                <th class="text-end">Asl narx</th>
                                <th class="text-end">To‘laydi</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($student->groups as $group)
                                @php
                                    $base = (int) ($group->pivot->base_payment ?? $group->pivot->payment ?? 0);
                                    $pay = (int) ($group->pivot->payment ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $group->name }}</td>
                                    <td class="text-end text-nowrap {{ $pay < $base ? 'text-muted text-decoration-line-through' : '' }}">
                                        {{ number_format($base, 0, '.', ' ') }}
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold">
                                        {{ number_format($pay, 0, '.', ' ') }} so‘m
                                        @if($pay < $base && $base > 0)
                                            <span class="badge bg-label-success ms-1">−{{ (int) round(100 - $pay * 100 / $base) }}%</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($discounts->isEmpty())
                    <p class="text-muted mb-0">Chegirma berilmagan.</p>
                @else
                    <div class="list-group">
                        @foreach($discounts as $discount)
                            <div class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <div class="fw-semibold">
                                        {{ $discount->percent }}%
                                        <span class="badge bg-label-{{ $discount->stateTone() }} ms-1">{{ $discount->stateLabel() }}</span>
                                        @if($discount->batch)
                                            <span class="badge bg-label-info ms-1">guruhga</span>
                                        @endif
                                    </div>
                                    <div class="text-muted" style="font-size: .82rem;">
                                        {{ $discount->group?->name ?? 'Barcha guruhlari' }} · {{ $discount->periodLabel() }}
                                    </div>
                                    @if($discount->reason)
                                        <div style="font-size: .82rem;">{{ $discount->reason }}</div>
                                    @endif
                                    <small class="text-muted">
                                        {{ $discount->creator?->name ?? '—' }} · {{ $discount->created_at?->format('d.m.Y') }}
                                    </small>
                                </div>
                                @if(in_array($discount->state(), ['active', 'upcoming'], true))
                                    <form action="{{ route('discounts.cancel', $discount->id) }}" method="post" class="m-0"
                                          onsubmit="return confirm('Chegirma bekor qilinsinmi? Oylik summa asl narxga qaytadi.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Bekor qilish">
                                            <i class="bx bx-x"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="col-lg-6">
                <h6 class="mb-3">Yangi chegirma</h6>
                @include('partials.discount-form', [
                    'action' => route('discounts.student.store', $student->id),
                    'prefix' => 'student-discount',
                    'groups' => $student->groups,
                ])
            </div>
        </div>
    </div>
</div>
