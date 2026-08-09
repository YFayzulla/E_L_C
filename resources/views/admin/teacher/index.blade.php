@extends('template.master')

@section('title', 'O‘qituvchilar')
@section('subtitle', 'O‘qituvchilar, ularning guruhlari va talabalari')

@section('content')

    <div class="page-head">
        <div class="page-sub">Jami {{ $teachers->count() }} ta o‘qituvchi</div>
        <div class="d-flex gap-2 flex-wrap">
            @role('admin')
            <a href="{{ url('/teacher/pdf') }}" class="btn btn-outline-secondary">
                <i class="bx bxs-file-pdf me-1"></i> PDF
            </a>
            @endrole
            <a href="{{ route('teacher.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Yangi o‘qituvchi
            </a>
        </div>
    </div>

    <div class="card">
        @if($teachers->isEmpty())
            <div class="empty-state">
                <i class="bx bx-user-voice"></i>
                <h6>O‘qituvchilar yo‘q</h6>
                <p class="mb-3">Birinchi o‘qituvchini qo‘shing va unga guruh biriktiring.</p>
                <a href="{{ route('teacher.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i> Yangi o‘qituvchi
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 3rem;">#</th>
                        <th>O‘qituvchi</th>
                        <th>Turi</th>
                        <th>Telefon</th>
                        <th>Guruhlar</th>
                        <th class="text-center">Talabalar</th>
                        <th class="text-center">Ulush</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($teachers as $teacher)
                        <tr>
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <x-avatar :user="$teacher" />
                                    <div class="min-w-0">
                                        <a href="{{ route('teacher.show', $teacher->id) }}"
                                           class="fw-semibold text-truncate d-block">{{ $teacher->name }}</a>
                                        @if($teacher->location)
                                            <small class="text-muted">{{ $teacher->location }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($teacher->hasRole('support'))
                                    <span class="badge bg-label-secondary">
                                        <i class="bx bx-support me-1"></i>Support
                                    </span>
                                @else
                                    <span class="badge bg-label-primary">O‘qituvchi</span>
                                @endif
                            </td>
                            <td dir="ltr">+{{ $teacher->phone }}</td>
                            <td>
                                @forelse($teacher->teacherGroups->take(3) as $group)
                                    <span class="badge bg-label-primary me-1 mb-1 d-inline-block">{{ $group->name }}</span>
                                @empty
                                    <span class="text-muted">— biriktirilmagan</span>
                                @endforelse
                                @if($teacher->groups_count > 3)
                                    <a href="{{ route('teacher.show', $teacher->id) }}"
                                       class="badge bg-label-info d-inline-block">+{{ $teacher->groups_count - 3 }}</a>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-warning">{{ $studentCounts[$teacher->id] ?? 0 }}</span>
                            </td>
                            <td class="text-center">
                                @if($teacher->percent !== null && $teacher->percent !== '')
                                    <span class="badge bg-label-info">{{ $teacher->percent }}%</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('teacher.show', $teacher->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Ko‘rish">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="{{ route('teacher.edit', $teacher->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Tahrirlash">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>
                                    <form action="{{ route('teacher.destroy', $teacher->id) }}" method="post"
                                          onsubmit="return confirm('{{ $teacher->name }} o‘chirilsinmi? Guruh biriktirishlari ham bekor qilinadi.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="O‘chirish">
                                            <i class="bx bx-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
