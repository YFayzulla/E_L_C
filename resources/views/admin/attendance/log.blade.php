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
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Talaba</th>
                        <th>Guruh</th>
                        <th>Dars</th>
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
                                <a href="{{ route('group.attendance', $record->group_id) }}"
                                   class="badge bg-label-info">{{ $record->group?->name ?? '—' }}</a>
                            </td>
                            <td class="text-muted">{{ $record->lesson?->name ?? '—' }}</td>
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
