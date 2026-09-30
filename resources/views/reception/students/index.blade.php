@extends('template.master')

@section('title', 'Reception')
@section('subtitle', 'Yangi kelganlar, test natijalari va guruhga ajratish')

@section('content')
    @php
        $hasFilters = filled($filters['status']) || filled($filters['level']) || filled($filters['q']);
    @endphp

    <div class="page-head">
        <div class="page-sub">Jami {{ $students->total() }} ta yozuv</div>
        <a href="{{ route('reception.students.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Yangi yozuv
        </a>
    </div>

    <form method="GET" action="{{ route('reception.students.index') }}" class="filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="status">Holat</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="level">Daraja</label>
                <select id="level" name="level" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($levels as $value => $label)
                        <option value="{{ $value }}" @selected($filters['level'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="q">Qidiruv</label>
                <input type="text" id="q" name="q" class="form-control"
                       value="{{ $filters['q'] }}" placeholder="Ism yoki telefon...">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bx bx-filter-alt me-1"></i> Filtr
                </button>
                @if($hasFilters)
                    <a href="{{ route('reception.students.index') }}" class="btn-icon" title="Tozalash">
                        <i class="bx bx-x"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        @if($students->isEmpty())
            <div class="empty-state">
                <i class="bx bx-user-plus"></i>
                <h6>{{ $hasFilters ? 'Filtrga mos yozuv topilmadi' : 'Hali yozuv yo‘q' }}</h6>
                <p class="mb-3">{{ $hasFilters ? 'Filtrlarni o‘zgartirib ko‘ring.' : 'Reception birinchi yangi kelgan o‘quvchini qo‘shishi mumkin.' }}</p>
                <a href="{{ route('reception.students.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i> Yangi yozuv
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>O‘quvchi</th>
                        <th>Ota-ona</th>
                        <th>Test</th>
                        <th>Daraja</th>
                        <th>Guruh</th>
                        <th>Holat</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($students as $item)
                        <tr>
                            <td>
                                <a href="{{ route('reception.students.edit', $item->id) }}"
                                   class="fw-semibold text-decoration-none">{{ $item->name }}</a>
                                <div class="text-muted" style="font-size: .78rem;" dir="ltr">
                                    {{ $item->phone ?: '—' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $item->parent_name ?: '—' }}</div>
                                <div class="text-muted" style="font-size: .78rem;" dir="ltr">
                                    {{ $item->parent_phone ?: '—' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $item->test_type ?: '—' }}</div>
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ $item->score ?: 'Natija yo‘q' }}
                                    @if($item->test_taken_at)
                                        · {{ $item->test_taken_at->format('d.m.Y') }}
                                    @endif
                                </div>
                            </td>
                            <td><span class="badge bg-label-info">{{ $item->levelLabel() }}</span></td>
                            <td>{{ $item->recommendedGroup?->name ?? '—' }}</td>
                            <td><span class="badge bg-label-{{ $item->statusTone() }}">{{ $item->statusLabel() }}</span></td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('reception.students.edit', $item->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Tahrirlash">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>
                                    @if($item->status !== \App\Models\ReceptionStudent::STATUS_ARCHIVED)
                                        <form action="{{ route('reception.students.archive', $item->id) }}" method="POST"
                                              onsubmit="return confirm('{{ $item->name }} yozuvi arxivlansinmi?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Arxivlash">
                                                <i class="bx bx-archive"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($students->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $students->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
