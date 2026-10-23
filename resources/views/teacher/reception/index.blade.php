@extends('template.master')

@section('title', 'Reception’dan olish')
@section('subtitle', 'Guruhingizga yozish mumkin bo‘lgan yangi kelganlar')

@section('content')
    <div class="page-head justify-content-end">
        <a href="{{ route('teacher.groups') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Talabalarim
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Yangi kelganlar</h5>
            <small class="text-muted">Guruhingizga tavsiya qilinganlar tepada.</small>
        </div>

        @if($students->isEmpty())
            <div class="empty-state">
                <i class="bx bx-user-check"></i>
                <h6>Hozircha yo‘q</h6>
                <p class="mb-0">Reception guruhingizga talaba tavsiya qilsa, shu yerda ko‘rinadi.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>O‘quvchi</th>
                        <th class="d-none d-md-table-cell">Daraja</th>
                        <th class="d-none d-md-table-cell">Test</th>
                        <th>Tavsiya</th>
                        <th class="text-end">Amal</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($students as $item)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $item->name }}</div>
                                <small class="text-muted">{{ $item->created_at->format('d.m.Y') }}</small>
                            </td>
                            <td class="d-none d-md-table-cell">{{ $item->levelLabel() }}</td>
                            <td class="d-none d-md-table-cell">
                                {{ $item->score ?: '—' }}
                                @if($item->test_image_path)
                                    <a href="{{ route('reception.students.test-image', $item->id) }}" target="_blank" rel="noopener"
                                       class="ms-1" title="Test faylini ko‘rish"><i class="bx bx-file"></i></a>
                                @endif
                            </td>
                            <td>
                                @if($item->recommendedGroup)
                                    <span class="badge bg-label-success">{{ $item->recommendedGroup->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('reception.students.enroll', $item->id) }}" class="btn btn-sm btn-primary">
                                    <i class="bx bx-user-check"></i>
                                    <span class="d-none d-sm-inline ms-1">Guruhga olish</span>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
