@extends('template.master')

@section('title', $olympiad->name)
@section('subtitle', 'Olimpiada · ' . ($olympiad->held_on?->format('d.m.Y') ?? 'sana belgilanmagan'))

@section('content')
    @php
        $sections = collect($olympiad->sections ?? []);
        $maxTotal = $olympiad->maxTotal();
        $accepted = $participants->where('status', \App\Models\OlympiadParticipant::STATUS_ACCEPTED);
        $enrolledCount = $participants->filter->isEnrolled()->count();
        $statuses = \App\Models\OlympiadParticipant::statuses();
        $rules = old('rules', $olympiad->rules ?: [['from' => 1, 'to' => 3, 'percent' => 100], ['from' => 4, 'to' => 10, 'percent' => 50]]);
    @endphp

    <div class="page-head">
        <div>
            <span class="badge bg-label-{{ $olympiad->statusTone() }}">{{ $olympiad->statusLabel() }}</span>
            <span class="text-muted ms-2" style="font-size: .85rem;">
                Bo‘limlar: {{ $sections->map(fn ($s) => $s['label'] . ' /' . $s['max'])->implode(' · ') }} · jami {{ $maxTotal }}
            </span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('olympiads.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i> Ro‘yxat</a>
            <a href="{{ route('olympiads.edit', $olympiad) }}" class="btn btn-outline-secondary"><i class="bx bx-edit-alt me-1"></i> Tahrirlash</a>
            <a href="{{ route('olympiads.pdf', $olympiad) }}" class="btn btn-outline-danger"><i class="bx bxs-file-pdf me-1"></i> PDF</a>
        </div>
    </div>

    {{-- Hisobot --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['Ishtirokchilar', $participants->count(), 'bx-group', ''],
            ['Natijasi kiritilgan', $participants->whereNotNull('percent')->count(), 'bx-edit', 'is-info'],
            ['Qabul qilindi', $accepted->count(), 'bx-check-circle', 'is-success'],
            ['Guruhga biriktirildi', $enrolledCount, 'bx-user-check', 'is-warning'],
        ] as [$label, $value, $icon, $tone])
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><div class="stat-label">{{ $label }}</div><div class="stat-value">{{ $value }}</div></div>
                        <span class="stat-icon {{ $tone }}"><i class="bx {{ $icon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Ishtirokchi qo'shish --}}
    <div class="card mb-4">
        <div class="card-header">Ishtirokchi qo‘shish</div>
        <div class="card-body">
            {{-- Yangi odam: 1-qator — o'zi (ism, telefon, maktab, sinf),
                 2-qator — ota-onasi va "Qo'shish". --}}
            <h6 class="mb-2">Yangi odam</h6>
            <form method="POST" action="{{ route('olympiads.participants.store', $olympiad) }}" class="row g-2">
                @csrf
                <div class="col-md-6 col-xl-4"><input type="text" name="name" required maxlength="255" class="form-control form-control-sm" placeholder="Ism familiya *" aria-label="Ism familiya"></div>
                <div class="col-md-6 col-xl-3"><input type="text" name="phone" maxlength="32" class="form-control form-control-sm" placeholder="Telefon (901234567)" aria-label="Telefon"></div>
                <div class="col-8 col-xl-3"><input type="text" name="school" maxlength="255" class="form-control form-control-sm" placeholder="Maktab" aria-label="Maktab"></div>
                <div class="col-4 col-xl-2"><input type="text" name="grade" maxlength="32" class="form-control form-control-sm" placeholder="Sinf (9-A)" aria-label="Sinf"></div>

                <div class="col-md-6 col-xl-4"><input type="text" name="parent_name" maxlength="255" class="form-control form-control-sm" placeholder="Ota-ona ismi" aria-label="Ota-ona ismi"></div>
                <div class="col-md-6 col-xl-3"><input type="text" name="parent_phone" maxlength="32" class="form-control form-control-sm" placeholder="Ota-ona telefoni" aria-label="Ota-ona telefoni"></div>
                <div class="col-xl-2 ms-xl-auto d-grid"><button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i> Qo‘shish</button></div>
            </form>

            {{-- Tayyor ro'yxatlardan: alohida pastki qatorda. --}}
            <div class="border-top mt-4 pt-3">
                <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="mb-2">Reception daftaridan</h6>
                            <form method="POST" action="{{ route('olympiads.participants.reception', $olympiad) }}">
                                @csrf
                                <select name="reception_ids[]" class="choices form-select" multiple data-placeholder="Ism bo‘yicha qidiring…">
                                    @foreach($receptionOptions as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }}{{ $option->phone ? ' · ' . $option->phone : '' }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary mt-2"><i class="bx bx-user-plus me-1"></i> Tanlanganlarni qo‘shish</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-2">Markazdagi talabalar</h6>
                            <form method="POST" action="{{ route('olympiads.participants.students', $olympiad) }}">
                                @csrf
                                <select name="student_ids[]" class="choices form-select" multiple data-placeholder="Ism bo‘yicha qidiring…">
                                    @foreach($studentOptions as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }} · +{{ $option->phone }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary mt-2"><i class="bx bx-user-plus me-1"></i> Tanlanganlarni qo‘shish</button>
                            </form>
                        </div>
                </div>
            </div>
        </div>
    </div>

    @if($isAdmin)
        {{-- O'rin bo'yicha TAVSIYA chegirma — har bir ishtirokchida qo'lda o'zgartiriladi. --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span>Chegirma qoidalari</span>
                    <small class="text-muted d-block">O‘rin bo‘yicha tavsiya. Qo‘llangandan keyin pastdagi jadvalda har biriga alohida o‘zgartiring.</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addRule"><i class="bx bx-plus me-1"></i> Qoida</button>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('olympiads.rules', $olympiad) }}" id="rulesForm">
                    @csrf
                    <div id="ruleRows" class="d-flex flex-column gap-2">
                        @foreach($rules as $i => $rule)
                            <div class="d-flex align-items-center gap-2 flex-wrap js-rule-row">
                                <input type="number" min="1" name="rules[{{ $i }}][from]" value="{{ $rule['from'] ?? '' }}" class="form-control form-control-sm" style="width: 5rem;" placeholder="dan">
                                <span>–</span>
                                <input type="number" min="1" name="rules[{{ $i }}][to]" value="{{ $rule['to'] ?? '' }}" class="form-control form-control-sm" style="width: 5rem;" placeholder="gacha">
                                <span class="text-muted">o‘rin →</span>
                                <div class="input-group input-group-sm" style="width: 7rem;">
                                    <input type="number" min="1" max="100" name="rules[{{ $i }}][percent]" value="{{ $rule['percent'] ?? '' }}" class="form-control">
                                    <span class="input-group-text">%</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger js-remove-rule"><i class="bx bx-x"></i></button>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex gap-2 mt-3 flex-wrap">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Saqlash</button>
                        <button type="submit" name="apply" value="1" class="btn btn-sm btn-primary"
                                onclick="return confirm('Qoidalar guruhga biriktirilmagan barcha ishtirokchilarning chegirmasini o‘zgartiradi. Davom etilsinmi?');">
                            Saqlash va qo‘llash
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Natijalar --}}
    <form id="scoresForm" method="POST" action="{{ route('olympiads.scores', $olympiad) }}">@csrf</form>
    @if($isAdmin)
        <form id="decisionsForm" method="POST" action="{{ route('olympiads.decisions', $olympiad) }}">@csrf</form>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Natijalar va saralash</span>
            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" form="scoresForm" class="btn btn-sm btn-primary"><i class="bx bx-save me-1"></i> Ballarni saqlash</button>
                @if($isAdmin)
                    <button type="submit" form="decisionsForm" class="btn btn-sm btn-success"><i class="bx bx-check me-1"></i> Qarorlarni saqlash</button>
                @endif
            </div>
        </div>

        @if($isAdmin && $participants->isNotEmpty())
            {{-- Qabul qilinganlarni guruhga biriktirish --}}
            <form id="enrollForm" method="POST" action="{{ route('olympiads.enroll', $olympiad) }}"
                  class="d-flex align-items-center flex-wrap gap-2 px-4 py-3 border-bottom"
                  onsubmit="return document.querySelectorAll('.js-enroll-check:checked').length > 0
                      ? confirm('Belgilanganlar guruhga biriktirilsinmi? Yangilarga talaba hisobi ochiladi, chegirma beriladi.')
                      : (alert('Avval qabul qilinganlarni belgilang.'), false);">
                @csrf
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="enrollCheckAll">
                    <label class="form-check-label" for="enrollCheckAll">Qabul qilinganlarni belgilash</label>
                </div>
                <div class="form-check mb-0 ms-2">
                    <input class="form-check-input" type="checkbox" name="parent_account" value="1" id="enrollParentAccount">
                    <label class="form-check-label" for="enrollParentAccount">Ota-onaga hisob ochilsin</label>
                </div>
                <div class="d-flex align-items-center gap-2 ms-md-auto flex-wrap">
                    <select name="group_id" required class="form-select form-select-sm" style="width: auto; min-width: 14rem;" aria-label="Guruh">
                        <option value="">— Qaysi guruhga —</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary text-nowrap"><i class="bx bx-user-check me-1"></i> Guruhga biriktirish</button>
                </div>
            </form>
        @endif

        @if($participants->isEmpty())
            <div class="empty-state">
                <i class="bx bx-user-plus"></i>
                <h6>Ishtirokchi yo‘q</h6>
                <p class="mb-0">Yuqoridan yangi odam, reception yozuvi yoki markazdagi talabani qo‘shing.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 olympiad-table">
                    <thead>
                    <tr>
                        @if($isAdmin)<th style="width: 1%;"></th>@endif
                        <th style="width: 1%;">O‘rin</th>
                        <th>Ishtirokchi</th>
                        @foreach($sections as $section)
                            <th class="text-center">{{ $section['label'] }}<br><span class="text-muted fw-normal">/{{ $section['max'] }}</span></th>
                        @endforeach
                        <th class="text-center">Jami</th>
                        @if($isAdmin)
                            <th>Holat</th>
                            <th class="text-center">Chegirma</th>
                        @endif
                        <th>Guruh</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($participants as $p)
                        @php $scores = (array) ($p->scores ?? []); $locked = $p->isEnrolled(); @endphp
                        <tr>
                            @if($isAdmin)
                                <td>
                                    @if(! $locked && $p->status === \App\Models\OlympiadParticipant::STATUS_ACCEPTED)
                                        <input type="checkbox" class="form-check-input js-enroll-check" form="enrollForm"
                                               name="participant_ids[]" value="{{ $p->id }}" aria-label="{{ $p->name }}">
                                    @endif
                                </td>
                            @endif
                            <td class="fw-semibold">{{ $p->rank ?? '—' }}</td>
                            <td style="min-width: 9rem;">
                                <div class="fw-semibold">{{ $p->name }}</div>
                                <div class="text-muted" style="font-size: .75rem;">
                                    {{ $p->phone ?: 'telefon yo‘q' }}{{ $p->school ? ' · ' . $p->school : '' }}{{ $p->grade ? ' · ' . $p->grade . ' sinf' : '' }}
                                </div>
                                @if($p->user_id && ! $locked)
                                    <span class="badge bg-label-primary">markazdagi talaba</span>
                                @elseif($p->reception_student_id && ! $locked)
                                    <span class="badge bg-label-info">reception</span>
                                @endif
                            </td>
                            @foreach($sections as $section)
                                <td class="text-center">
                                    <input type="number" min="0" max="{{ $section['max'] }}" step="1" form="scoresForm"
                                           name="scores[{{ $p->id }}][{{ $section['key'] }}]"
                                           value="{{ $scores[$section['key']] ?? '' }}"
                                           class="form-control form-control-sm text-center mx-auto px-1" style="width: 3.6rem;" placeholder="—"
                                           aria-label="{{ $p->name }} — {{ $section['label'] }}">
                                </td>
                            @endforeach
                            <td class="text-center text-nowrap">
                                @if($p->percent !== null)
                                    <div class="fw-semibold">{{ $p->total }}/{{ $maxTotal }}</div>
                                    <span class="badge bg-label-{{ $p->percent >= 80 ? 'success' : ($p->percent >= 60 ? 'warning' : 'danger') }}">{{ $p->percent }}%</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @if($isAdmin)
                                <td>
                                    @if($locked)
                                        <span class="badge bg-label-success">Biriktirildi</span>
                                    @else
                                        <select name="decisions[{{ $p->id }}][status]" form="decisionsForm" class="form-select form-select-sm" style="min-width: 7.6rem;">
                                            @foreach($statuses as $value => $label)
                                                <option value="{{ $value }}" @selected($p->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($locked)
                                        {{ $p->discount_percent ? $p->discount_percent . '%' : '—' }}
                                    @else
                                        <div class="input-group input-group-sm mx-auto" style="width: 5.2rem;">
                                            <input type="number" min="0" max="100" name="decisions[{{ $p->id }}][discount]" form="decisionsForm"
                                                   value="{{ $p->discount_percent }}" class="form-control text-center" placeholder="0">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @php $suggested = $olympiad->suggestedPercentFor($p->rank); @endphp
                                        @if($suggested !== null && $suggested !== $p->discount_percent)
                                            <small class="text-muted">tavsiya: {{ $suggested }}%</small>
                                        @endif
                                    @endif
                                </td>
                            @endif
                            <td>
                                @if($locked)
                                    <span class="badge bg-label-info text-truncate d-inline-block" style="max-width: 8rem;" title="{{ $p->group?->name }}">{{ $p->group?->name ?? '—' }}</span>
                                    @if($p->user_id)
                                        <a href="{{ route('student.show', $p->user_id) }}" class="d-block small">Talaba sahifasi</a>
                                    @endif
                                @else
                                    <span class="badge bg-label-{{ $p->statusTone() }}">{{ $p->statusLabel() }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @unless($locked)
                                    <form method="POST" action="{{ route('olympiads.participants.destroy', [$olympiad, $p->id]) }}" class="m-0"
                                          onsubmit="return confirm('{{ $p->name }} ro‘yxatdan olib tashlansinmi?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Olib tashlash"><i class="bx bx-trash-alt"></i></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($isAdmin && ! $participants->filter->isEnrolled()->count() && $participants->isEmpty())
        <form method="POST" action="{{ route('olympiads.destroy', $olympiad) }}" class="mt-3 text-end"
              onsubmit="return confirm('Olimpiada o‘chirilsinmi?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Olimpiadani o‘chirish</button>
        </form>
    @endif

    <style>
        html[data-theme] .olympiad-table th,
        html[data-theme] .olympiad-table td { padding: .5rem .3rem; font-size: .85rem; }
        html[data-theme] .olympiad-table th:first-child,
        html[data-theme] .olympiad-table td:first-child { padding-left: 1rem; }
        html[data-theme] .olympiad-table thead th { font-size: .7rem; white-space: nowrap; }
    </style>

    <script>
        (function () {
            const all = document.getElementById('enrollCheckAll');
            if (all) {
                all.addEventListener('change', () => {
                    document.querySelectorAll('.js-enroll-check').forEach(b => { b.checked = all.checked; });
                });
            }

            const rows = document.getElementById('ruleRows');
            const add = document.getElementById('addRule');
            if (rows && add) {
                let i = rows.querySelectorAll('.js-rule-row').length + 100;
                add.addEventListener('click', () => {
                    const n = i++;
                    rows.insertAdjacentHTML('beforeend', `
                        <div class="d-flex align-items-center gap-2 flex-wrap js-rule-row">
                            <input type="number" min="1" name="rules[${n}][from]" class="form-control form-control-sm" style="width: 5rem;" placeholder="dan">
                            <span>–</span>
                            <input type="number" min="1" name="rules[${n}][to]" class="form-control form-control-sm" style="width: 5rem;" placeholder="gacha">
                            <span class="text-muted">o‘rin →</span>
                            <div class="input-group input-group-sm" style="width: 7rem;"><input type="number" min="1" max="100" name="rules[${n}][percent]" class="form-control"><span class="input-group-text">%</span></div>
                            <button type="button" class="btn btn-sm btn-outline-danger js-remove-rule"><i class="bx bx-x"></i></button>
                        </div>`);
                });
                rows.addEventListener('click', (e) => {
                    const b = e.target.closest('.js-remove-rule');
                    if (b) b.closest('.js-rule-row').remove();
                });
            }
        })();
    </script>
@endsection
