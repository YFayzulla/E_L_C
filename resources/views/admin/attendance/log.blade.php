@extends('template.master')

@section('title', 'Qoldirishlar jurnali')
@section('subtitle', 'Kelmagan va kechikkan talabalarning to‘liq ro‘yxati')

@section('content')

    <div class="page-head">
        <div class="page-sub">
            {{ \Carbon\Carbon::parse($from)->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($to)->format('d.m.Y') }}
            · jami {{ $records->total() }} ta yozuv
        </div>
        <a href="{{ route('attendance.overview') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Umumiy davomat
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Kelmagan</div>
                        <div class="stat-value text-danger">{{ $absentTotal }}</div>
                    </div>
                    <span class="stat-icon is-danger"><i class="bx bx-x-circle"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Kechikkan</div>
                        <div class="stat-value text-warning">{{ $lateTotal }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-time-five"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtrlar --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('attendance.log') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="group_id">Guruh</label>
                <select id="group_id" name="group_id" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($groupOptions as $option)
                        <option value="{{ $option->id }}" @selected($groupId === (int) $option->id)>{{ $option->name }}</option>
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
                <label class="form-label" for="status">Holat</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Barchasi</option>
                    <option value="0" @selected($status === 0)>Kelmadi</option>
                    <option value="2" @selected($status === 2)>Kechikdi</option>
                </select>
            </div>

            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bx bx-filter-alt me-1"></i> Filtr
                    </button>
                    <a href="{{ route('attendance.log') }}" class="btn btn-outline-secondary" title="Tozalash">
                        <i class="bx bx-reset"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        @if($records->isEmpty())
            <div class="empty-state">
                <i class="bx bx-check-circle"></i>
                <h6>Yozuv yo‘q</h6>
                <p class="mb-0">Tanlangan davrda kelmagan yoki kechikkan talaba qayd etilmagan.</p>
            </div>
        @else
            <div class="d-xl-none">
                @foreach($records as $record)
                    <article class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold">{{ $record->user?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                            </div>
                            @if((int) $record->status === 2)
                                <span class="badge bg-label-warning">Kechikdi</span>
                            @else
                                <span class="badge bg-label-danger">Kelmadi</span>
                            @endif
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Guruh / dars</div>
                            <div>{{ $record->group?->name ?? '—' }} · {{ $record->lesson?->name ?? '—' }}</div>
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Sabab</div>
                            <div class="text-wrap">{{ filled($record->reason) ? $record->reason : 'Sabab kiritilmagan' }}</div>
                            @if($record->reasonWriter || $record->reason_written_at)
                                <small class="text-muted">
                                    Sababni {{ $record->reasonWriter?->name ?? '—' }} yozgan
                                    @if($record->reason_written_at) · {{ $record->reason_written_at->format('d.m.Y H:i') }} @endif
                                </small>
                            @endif
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Telefonlar</div>
                            <div>{{ $record->user?->name ?? 'O‘quvchi' }}:
                                @if($record->user?->phone)
                                    <a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $record->user->phone) }}">{{ $record->user->phone }}</a>
                                @else
                                    —
                                @endif
                            </div>
                            <div>{{ $record->user?->parents_name ?: 'Ota-ona' }}:
                                @if($record->user?->parents_tel)
                                    <a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $record->user->parents_tel) }}">{{ $record->user->parents_tel }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Davomatni belgilagan</div>
                            <div>{{ $record->teacher?->name ?? '—' }}</div>
                        </div>

                        <div class="mt-3">
                            <div class="small text-muted">Ota-ona bilan aloqa</div>
                            @if($record->latestFollowUp)
                                <span class="badge bg-label-{{ $record->latestFollowUp->statusTone() }}">{{ $record->latestFollowUp->statusLabel() }}</span>
                                @if(filled($record->latestFollowUp->contact_person))
                                    <div>Gaplashilgan: {{ $record->latestFollowUp->contact_person }}</div>
                                @endif
                                <div class="small text-muted">
                                    {{ $record->latestFollowUp->contacted_at?->format('d.m.Y H:i') ?? 'Vaqt ko‘rsatilmagan' }}
                                    @if($record->latestFollowUp->next_follow_up_at)
                                        · Qayta: {{ $record->latestFollowUp->next_follow_up_at->format('d.m.Y H:i') }}
                                    @endif
                                </div>
                                @if(filled($record->latestFollowUp->note))
                                    <div class="mt-1 text-wrap">Izoh: {{ $record->latestFollowUp->note }}</div>
                                @endif
                                <small class="text-muted">Yozgan: {{ $record->latestFollowUp->recorder?->name ?? '—' }}</small>
                            @else
                                <span class="text-muted">Aloqa qaydi yo‘q</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="d-none d-xl-block table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Talaba</th>
                        <th>Telefonlar</th>
                        <th>Guruh</th>
                        <th>Dars</th>
                        <th>Sabab</th>
                        <th>Ota-ona bilan aloqa</th>
                        <th>Holat</th>
                        <th>Kim belgilagan</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($records as $record)
                        <tr>
                            <td class="text-muted">{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td class="fw-semibold">{{ $record->user?->name ?? '—' }}</td>
                            <td>
                                <div>{{ $record->user?->name ?? 'O‘quvchi' }}: {{ $record->user?->phone ?? '—' }}</div>
                                <div>{{ $record->user?->parents_name ?: 'Ota-ona' }}: {{ $record->user?->parents_tel ?? '—' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('group.attendance', $record->group_id) }}"
                                   class="badge bg-label-info">{{ $record->group?->name ?? '—' }}</a>
                            </td>
                            <td class="text-muted">{{ $record->lesson?->name ?? '—' }}</td>
                            <td>
                                @if(filled($record->reason))
                                    <div>{{ $record->reason }}</div>
                                    <small class="text-muted">{{ $record->reasonWriter?->name ?? '—' }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->latestFollowUp)
                                    <span class="badge bg-label-{{ $record->latestFollowUp->statusTone() }}">{{ $record->latestFollowUp->statusLabel() }}</span>
                                    @if(filled($record->latestFollowUp->note))
                                        <div class="mt-1 text-wrap">{{ $record->latestFollowUp->note }}</div>
                                    @endif
                                    @if(filled($record->latestFollowUp->contact_person))
                                        <div class="small">Gaplashilgan: {{ $record->latestFollowUp->contact_person }}</div>
                                    @endif
                                    @if($record->latestFollowUp->next_follow_up_at)
                                        <small class="text-muted">Qayta bog‘lanish: {{ $record->latestFollowUp->next_follow_up_at->format('d.m.Y H:i') }}</small>
                                    @endif
                                    <small class="text-muted">{{ $record->latestFollowUp->recorder?->name ?? '—' }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if((int) $record->status === 2)
                                    <span class="badge bg-label-warning">Kechikdi</span>
                                @else
                                    <span class="badge bg-label-danger">Kelmadi</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $record->teacher?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $records->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
