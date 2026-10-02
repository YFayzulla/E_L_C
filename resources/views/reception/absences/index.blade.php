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
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Talaba</th>
                        <th>Telefonlar</th>
                        <th>Guruh</th>
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
                            $parentPhone = $record->user?->parents_tel;
                            $contactName = $record->user?->parents_name ?: 'Ota-ona';
                        @endphp
                        <tr>
                            <td class="text-muted">
                                {{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}
                                <div>
                                    @if((int) $record->status === 2)
                                        <span class="badge bg-label-warning">Kechikdi</span>
                                    @else
                                        <span class="badge bg-label-danger">Kelmadi</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $record->user?->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ $record->lesson?->name ?? 'Dars' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $record->user?->name ?? 'O‘quvchi' }}:
                                    @if($studentPhone)<a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $studentPhone) }}" class="text-reset">{{ $studentPhone }}</a>@else<span>—</span>@endif
                                </div>
                                <div>{{ $contactName }}:
                                    @if($parentPhone)<a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $parentPhone) }}" class="text-reset">{{ $parentPhone }}</a>@else<span>—</span>@endif
                                </div>
                            </td>
                            <td><span class="badge bg-label-info">{{ $record->group?->name ?? '—' }}</span></td>
                            <td>
                                @if(filled($record->reason))
                                    <div>{{ $record->reason }}</div>
                                    @if($record->reasonWriter)
                                        <small class="text-muted">{{ $record->reasonWriter->name }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($latest)
                                    <span class="badge bg-label-{{ $latest->statusTone() }}">{{ $latest->statusLabel() }}</span>
                                    <div class="text-muted" style="font-size: .78rem;">
                                        {{ $latest->contacted_at?->format('d.m.Y H:i') ?? '—' }}
                                    </div>
                                    @if(filled($latest->note))
                                        <div class="mt-2 text-wrap"><span class="fw-semibold">Izoh:</span> {{ $latest->note }}</div>
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
                                    <button type="button" class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal" data-bs-target="#followUp{{ $record->id }}">
                                        <i class="bx bx-phone-call me-1"></i> Yozish
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
@endsection
