@extends('template.master')

@section('title', 'Kelmaganlar bilan ishlash')
@section('subtitle', 'Ota-onalar bilan gaplashish va qayta bog‘lanish jurnali')

@section('content')
    <div class="page-head">
        <div class="page-sub">
            {{ \Carbon\Carbon::parse($from)->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($to)->format('d.m.Y') }}
            · jami {{ $records->total() }} ta yozuv
        </div>
        <a href="{{ route('reception.students.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-user-plus me-1"></i> Reception ro‘yxati
        </a>
    </div>

    <form method="GET" action="{{ route('reception.absences.index') }}" class="filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="group_id">Guruh</label>
                <select id="group_id" name="group_id" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected($groupId === (int) $group->id)>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="from">Dan</label>
                <input type="date" id="from" name="from" class="form-control" value="{{ $from }}">
            </div>

            <div class="col-md-2">
                <label class="form-label" for="to">Gacha</label>
                <input type="date" id="to" name="to" class="form-control" value="{{ $to }}">
            </div>

            <div class="col-md-3">
                <label class="form-label" for="follow_status">Aloqa holati</label>
                <select id="follow_status" name="follow_status" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($followStatuses as $value => $label)
                        <option value="{{ $value }}" @selected($followStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bx bx-filter-alt me-1"></i> Filtr
                    </button>
                    <a href="{{ route('reception.absences.index') }}" class="btn btn-outline-secondary" title="Tozalash">
                        <i class="bx bx-reset"></i>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        @if($records->isEmpty())
            <div class="empty-state">
                <i class="bx bx-check-circle"></i>
                <h6>Yozuv yo‘q</h6>
                <p class="mb-0">Tanlangan davrda kelmagan yoki kechikkan talaba topilmadi.</p>
            </div>
        @else
            <div class="d-xl-none">
                @foreach($records as $record)
                    @php
                        $latest = $record->latestFollowUp;
                        $studentPhone = $record->user?->phone;
                        // Ota-onalar alohida: ota, ona, vasiy (bog'langanlar),
                        // bo'lmasa eski parents_name / parents_tel.
                        $contacts = collect($record->user?->guardians ?? [])
                            ->map(fn ($g) => [
                                'label' => \App\Services\ParentAccountService::LABELS[$g->pivot->relation ?? ''] ?? 'Ota-ona',
                                'name'  => $g->name,
                                'phone' => $g->phone,
                            ])
                            ->values();
                        if ($contacts->isEmpty() && ($record->user?->parents_tel || $record->user?->parents_name)) {
                            $contacts = collect([[
                                'label' => 'Ota-ona',
                                'name'  => $record->user->parents_name,
                                'phone' => $record->user->parents_tel,
                            ]]);
                        }
                    @endphp
                    <article class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold">{{ $record->user?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                            </div>
                            <span class="badge bg-label-{{ $record->kindTone() }}">{{ $record->kindLabel() }}</span>
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Guruh / dars</div>
                            <div>{{ $record->group?->name ?? '—' }} · {{ $record->lesson?->name ?? '—' }}</div>
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Sabab</div>
                            @include('partials.attendance-reason', ['record' => $record])
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Talaba telefoni</div>
                            <div>
                                @if($studentPhone)
                                    <a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $studentPhone) }}">{{ $studentPhone }}</a>
                                @else
                                    —
                                @endif
                            </div>
                            <div class="small text-muted mt-2">Ota-ona</div>
                            @forelse($contacts as $contact)
                                <div>
                                    <span class="text-muted">{{ $contact['label'] }}:</span>
                                    {{ $contact['name'] ?: '—' }}
                                    @if($contact['phone'])
                                        · <a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
                                    @endif
                                </div>
                            @empty
                                <div>—</div>
                            @endforelse
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Davomatni belgilagan</div>
                            <div>{{ $record->teacher?->name ?? '—' }}</div>
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Ota-ona bilan aloqa</div>
                            @if($latest)
                                <span class="badge bg-label-{{ $latest->statusTone() }}">{{ $latest->statusLabel() }}</span>
                                @if(filled($latest->contact_person))
                                    <div>Gaplashilgan: {{ $latest->contact_person }}</div>
                                @endif
                                <div class="small text-muted">
                                    {{ $latest->contacted_at?->format('d.m.Y H:i') ?? 'Vaqt ko‘rsatilmagan' }}
                                    @if($latest->next_follow_up_at)
                                        · Qayta: {{ $latest->next_follow_up_at->format('d.m.Y H:i') }}
                                    @endif
                                </div>
                                @if(filled($latest->note))
                                    <div class="mt-1 text-wrap">Izoh: {{ $latest->note }}</div>
                                @endif
                                <small class="text-muted">Yozgan: {{ $latest->recorder?->name ?? '—' }}</small>
                            @else
                                <span class="badge bg-label-secondary">Kutilmoqda</span>
                            @endif
                        </div>

                        @unless($latest)
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal" data-bs-target="#followUp{{ $record->id }}">
                                    <i class="bx bx-phone-call me-1"></i> Yozish
                                </button>
                            </div>
                        @endunless
                    </article>
                @endforeach
            </div>

            <div class="d-none d-xl-block table-responsive">
                <table class="table table-hover align-middle absences-table">
                    <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Talaba</th>
                        <th>Talaba tel.</th>
                        <th>Ota-ona</th>
                        <th>Ota-ona tel.</th>
                        <th>Guruh</th>
                        <th>Belgilagan</th>
                        <th>Sabab</th>
                        <th>Aloqa</th>
                        <th class="text-end">Amal</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($records as $record)
                        @php
                            $latest = $record->latestFollowUp;
                            $studentPhone = $record->user?->phone;
                            // Ota-onalar alohida: ota, ona, vasiy (bog'langanlar),
                            // bo'lmasa eski parents_name / parents_tel.
                            $contacts = collect($record->user?->guardians ?? [])
                                ->map(fn ($g) => [
                                    'label' => \App\Services\ParentAccountService::LABELS[$g->pivot->relation ?? ''] ?? 'Ota-ona',
                                    'name'  => $g->name,
                                    'phone' => $g->phone,
                                ])
                                ->values();
                            if ($contacts->isEmpty() && ($record->user?->parents_tel || $record->user?->parents_name)) {
                                $contacts = collect([[
                                    'label' => 'Ota-ona',
                                    'name'  => $record->user->parents_name,
                                    'phone' => $record->user->parents_tel,
                                ]]);
                            }
                        @endphp
                        <tr>
                            <td class="text-muted">
                                {{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}
                                <div>
                                    <span class="badge bg-label-{{ $record->kindTone() }}">{{ $record->kindLabel() }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $record->user?->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ $record->lesson?->name ?? 'Dars' }}
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @if($studentPhone)<a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $studentPhone) }}" class="text-reset">{{ $studentPhone }}</a>@else<span class="text-muted">—</span>@endif
                            </td>
                            {{-- Ota va ona alohida qatorda; ism va raqam yonma-yon ustunlarda. --}}
                            <td>
                                @forelse($contacts as $contact)
                                    {{-- Bir qatorda: raqam ustunidagi qator bilan tekis turishi uchun. --}}
                                    <div class="text-truncate" style="max-width: 7.5rem;" title="{{ $contact['name'] }}"><span class="text-muted">{{ $contact['label'] }}:</span> {{ $contact['name'] ?: '—' }}</div>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="text-nowrap">
                                @forelse($contacts as $contact)
                                    <div>
                                        @if($contact['phone'])<a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" class="text-reset">{{ $contact['phone'] }}</a>@else<span class="text-muted">—</span>@endif
                                    </div>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td><span class="badge bg-label-info text-wrap text-start">{{ $record->group?->name ?? '—' }}</span></td>
                            <td>{{ $record->teacher?->name ?? '—' }}</td>
                            <td>
                                @include('partials.attendance-reason', ['record' => $record])
                            </td>
                            <td>
                                @if($latest)
                                    <span class="badge bg-label-{{ $latest->statusTone() }}">{{ $latest->statusLabel() }}</span>
                                    <div class="text-muted" style="font-size: .78rem;">
                                        {{ $latest->contacted_at?->format('d.m.Y H:i') ?? '—' }}
                                    </div>
                                    @if(filled($latest->contact_person))
                                        <div class="small">Gaplashilgan: {{ $latest->contact_person }}</div>
                                    @endif
                                    @if(filled($latest->note))
                                        <div class="mt-2 text-wrap"><span class="fw-semibold">Izoh:</span> {{ $latest->note }}</div>
                                    @endif
                                    @if($latest->next_follow_up_at)
                                        <small class="text-muted">Qayta: {{ $latest->next_follow_up_at->format('d.m.Y H:i') }}</small>
                                    @endif
                                    @if($latest->recorder)
                                        <small class="text-muted">{{ $latest->recorder->name }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-label-secondary">Kutilmoqda</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @unless($latest)
                                    <button type="button" class="btn btn-sm btn-primary text-nowrap" title="Aloqani yozish"
                                            data-bs-toggle="modal" data-bs-target="#followUp{{ $record->id }}">
                                        <i class="bx bx-phone-call"></i><span class="d-none d-xxl-inline ms-1">Yozish</span>
                                    </button>
                                @else
                                    <span class="text-muted small">Yozilgan</span>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @foreach($records as $record)
                @php
                    $contactName = $record->user?->parents_name ?: 'Ota-ona';
                @endphp
                @if(!$record->latestFollowUp)
                <div class="modal fade" id="followUp{{ $record->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" method="POST"
                              action="{{ route('reception.absences.followups.store', $record->id) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $record->user?->name ?? 'Talaba' }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="status{{ $record->id }}">Holat</label>
                                        <select id="status{{ $record->id }}" name="status" class="form-select" required>
                                            @foreach($followStatuses as $value => $label)
                                                <option value="{{ $value }}" @selected($value === \App\Models\AbsenceFollowUp::STATUS_CALLED)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="contact_person{{ $record->id }}">Kim bilan</label>
                                        <input type="text" id="contact_person{{ $record->id }}" name="contact_person"
                                               class="form-control" value="{{ $contactName }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="contacted_at{{ $record->id }}">Gaplashilgan vaqt</label>
                                        <input type="datetime-local" id="contacted_at{{ $record->id }}" name="contacted_at"
                                               class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="next_follow_up_at{{ $record->id }}">Qayta bog‘lanish</label>
                                        <input type="datetime-local" id="next_follow_up_at{{ $record->id }}"
                                               name="next_follow_up_at" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="note{{ $record->id }}">Izoh</label>
                                        <textarea id="note{{ $record->id }}" name="note" rows="4" class="form-control"
                                                  placeholder="Nima deyildi, keyingi qadam..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Saqlash
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            @endforeach

            @if($records->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $records->links() }}
                </div>
            @endif
        @endif
    </div>
    {{-- Ustunlar ko'p (talaba, ota-ona va ularning raqamlari alohida) —
         odatiy ekranga sig'ishi uchun kataklar ixchamroq. --}}
    <style>
        html[data-theme] .absences-table th,
        html[data-theme] .absences-table td { padding: .5rem .32rem; font-size: .84rem; }
        html[data-theme] .absences-table th:first-child,
        html[data-theme] .absences-table td:first-child { padding-left: 1rem; }
        html[data-theme] .absences-table thead th { font-size: .7rem; white-space: nowrap; }
    </style>

@endsection
