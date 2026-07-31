@extends('template.master')

@section('title', 'Davomat')
@section('subtitle', 'Yo‘qlama qilish uchun guruhni tanlang')

@section('content')

    <div class="page-head justify-content-end">
        <div class="page-sub">Jami {{ $groups->count() }} ta guruh</div>
    </div>

    @if($groups->isEmpty())
        <div class="card">
            <div class="empty-state">
                <i class="bx bx-group"></i>
                <h6>Guruh yo‘q</h6>
                <p class="mb-0">Sizga hali guruh biriktirilmagan. Administratorga murojaat qiling.</p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 3rem;">#</th>
                        <th>Guruh</th>
                        <th>Vaqti</th>
                        <th class="text-center">Talabalar</th>
                        <th class="text-center">O‘tilgan darslar</th>
                        <th>Oxirgi dars</th>
                        <th class="text-end">Amal</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($groups as $group)
                        <tr>
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $group->name }}</td>
                            <td class="text-muted">
                                {{ $group->start_time && $group->finish_time
                                    ? $group->start_time . ' – ' . $group->finish_time
                                    : '—' }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-info">{{ $group->members_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-primary">{{ $group->lessons_total }}</span>
                            </td>
                            <td class="text-muted">
                                {{ $group->last_lesson_at
                                    ? \Carbon\Carbon::parse($group->last_lesson_at)->format('d.m.Y')
                                    : '—' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('attendance.check', $group->id) }}" class="btn btn-sm btn-primary">
                                    <i class="bx bx-check-square me-1"></i> Yo‘qlama
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
