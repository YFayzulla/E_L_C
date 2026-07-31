@extends('template.master')

@section('title', 'Kutish zali')
@section('subtitle', 'Hali guruhga biriktirilmagan talabalar')

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('group.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-group me-1"></i> Guruhlar
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>Kutayotgan talabalar: <strong>{{ $students->count() }}</strong></span>
            <span class="text-muted" style="font-size: .82rem;">Guruhga biriktirilgach ro‘yxatdan chiqadi</span>
        </div>

        @if($students->isEmpty())
            <div class="empty-state">
                <i class="bx bx-check-circle"></i>
                <h6>Kutish zali bo‘sh</h6>
                <p class="mb-0">Barcha talabalar guruhlarga biriktirilgan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 3rem;">#</th>
                        <th>Talaba</th>
                        <th>Telefon</th>
                        <th>Ota-ona telefoni</th>
                        <th>Holati</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($students as $student)
                        <tr>
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <div class="avatar avatar-sm">
                                        @if($student->photo)
                                            <img src="{{ asset('storage/' . $student->photo) }}" alt=""
                                                 class="rounded-circle w-100 h-100" style="object-fit: cover;">
                                        @else
                                            <span class="avatar-initial rounded-circle bg-label-primary">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student->name, 0, 2)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('student.show', $student->id) }}"
                                       class="fw-semibold text-decoration-none">{{ $student->name }}</a>
                                </div>
                            </td>
                            <td dir="ltr">{{ $student->phone ? '+' . $student->phone : '—' }}</td>
                            <td dir="ltr">{{ $student->parents_tel ?: '—' }}</td>
                            <td><span class="badge bg-label-warning">Kutmoqda</span></td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('student.show', $student->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Ma’lumotlari">
                                        <i class="bx bx-show-alt"></i>
                                    </a>
                                    <a href="{{ route('student.transfer.form', $student->id) }}"
                                       class="btn btn-sm btn-outline-primary" title="Guruhga biriktirish">
                                        <i class="bx bx-user-plus me-1"></i> Guruhga biriktirish
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{--
        The old inline "Assign to Group" modal posted to student.change.group
        (GroupExtraController@change_group). That endpoint does a bare
        groups()->sync($ids) — nulling group_user.payment and shrinking every
        affected teacher's salary — and runs

            Attendance::where('user_id', $user->id)->update(['group_id' => $ids[0]]);

        unscoped by date or old group, rewriting the student's whole attendance
        history into one group. It also never recomputes should_pay/dept.
        Assignment now goes through student.transfer.form, which uses
        StudentGroupService (pivot payment preserved, history recorded both
        ways, attendances untouched).
    --}}

@endsection
