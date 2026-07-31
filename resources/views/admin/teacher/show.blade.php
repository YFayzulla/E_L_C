@extends('template.master')

@section('title', $teacher->name)
@section('subtitle', 'O‘qituvchi kartochkasi')

@section('content')

    @php
        $groupCount = $groupLinks->count();
        $percent = $teacher->percent;
    @endphp

    <div class="page-head">
        <div class="d-flex align-items-center gap-3 min-w-0">
            <a href="{{ route('teacher.index') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div class="avatar avatar-lg">
                @if($teacher->photo)
                    <img src="{{ asset('storage/' . $teacher->photo) }}" alt=""
                         class="rounded-circle w-100 h-100" style="object-fit: cover;">
                @else
                    <span class="avatar-initial rounded-circle bg-label-primary">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($teacher->name, 0, 2)) }}
                    </span>
                @endif
            </div>
            <div class="min-w-0">
                <h4 class="mb-1 text-truncate">{{ $teacher->name }}</h4>
                <div class="page-sub" dir="ltr">+{{ $teacher->phone }}</div>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            {{-- O'qituvchi nimani ko'rayotganini aynan o'z ko'zi bilan ko'rish --}}
            <form method="POST" action="{{ route('impersonate.start', $teacher->id) }}"
                  onsubmit="return confirm('{{ $teacher->name }} hisobiga kirasizmi? Barcha amallar shu foydalanuvchi nomidan bajariladi.');">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bx bx-log-in me-1"></i> Profiliga kirish
                </button>
            </form>

            <a href="{{ route('teacher.edit', $teacher->id) }}" class="btn btn-outline-secondary">
                <i class="bx bx-edit-alt me-1"></i> Tahrirlash
            </a>
            <form action="{{ route('teacher.destroy', $teacher->id) }}" method="post"
                  onsubmit="return confirm('{{ $teacher->name }} o‘chirilsinmi? Guruh biriktirishlari ham bekor qilinadi.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bx bx-trash-alt me-1"></i> O‘chirish
                </button>
            </form>
        </div>
    </div>

    {{-- Statistika --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Guruhlar</div>
                        <div class="stat-value">{{ $groupCount }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-group"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Talabalar</div>
                        <div class="stat-value">{{ $studentCount }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-user"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Ulush</div>
                        <div class="stat-value">{{ $percent !== null && $percent !== '' ? $percent . '%' : '—' }}</div>
                    </div>
                    <span class="stat-icon"><i class="bx bx-pie-chart-alt-2"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Hisoblangan oylik</div>
                        <div class="stat-value text-success" style="font-size: 1.15rem;">
                            {{ number_format((float) $salary, 0, '.', ' ') }} so‘m
                        </div>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-wallet"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Guruhlar --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Biriktirilgan guruhlar</span>
                    <span class="badge bg-label-primary">{{ $groupCount }}</span>
                </div>

                @if($groupLinks->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-group"></i>
                        <h6>Guruh biriktirilmagan</h6>
                        <p class="mb-0">Quyidagi shakl orqali guruh biriktiring.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Guruh</th>
                                <th>Vaqti</th>
                                <th class="text-center">Talabalar</th>
                                <th class="text-end">Amal</th>
                            </tr>
                            </thead>
                            <tbody id="myTable">
                            @foreach($groupLinks as $link)
                                <tr>
                                    <td class="fw-semibold">{{ $link->group->name }}</td>
                                    <td class="text-muted">
                                        @if($link->group->start_time || $link->group->finish_time)
                                            <i class="bx bx-time me-1"></i>{{ $link->group->start_time }} – {{ $link->group->finish_time }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-label-warning">{{ $link->group->members_count }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end">
                                            {{-- {id} = group_teachers QATOR id si, guruh id emas --}}
                                            <form action="{{ route('teacher_group.delete', $link->id) }}" method="post"
                                                  onsubmit="return confirm('{{ $link->group->name }} guruhi bu o‘qituvchidan ajratilsinmi?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Ajratish">
                                                    <i class="bx bx-unlink"></i>
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

                <div class="card-footer">
                    @if($availableGroups->isEmpty())
                        <div class="text-muted" style="font-size: .85rem;">
                            <i class="bx bx-check-circle me-1"></i> Barcha guruhlar allaqachon biriktirilgan.
                        </div>
                    @else
                        <form action="{{ route('teacher_group.store', $teacher->id) }}" method="post">
                            @csrf
                            @method('PUT')
                            <label class="form-label" for="attach_group_id">Yangi guruh biriktirish</label>
                            <div class="row g-2 align-items-start">
                                <div class="col-md-9">
                                    {{-- `required` ATAYIN yo‘q: Choices.js asl <select> ni yashiradi va
                                         brauzer fokuslay olmay formani bloklab qo‘yadi. Tekshiruv serverda. --}}
                                    <select id="attach_group_id" name="group_id[]" class="choices form-select" multiple
                                            data-placeholder="Guruh qidiring…">
                                        @foreach($availableGroups as $group)
                                            <option value="{{ $group->id }}">
                                                {{ $group->name }}@if($group->start_time) — {{ $group->start_time }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-link me-1"></i> Biriktirish
                                    </button>
                                </div>
                            </div>
                            @error('group_id')
                            <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div>
                            @enderror
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Shaxsiy ma'lumotlar --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Shaxsiy ma’lumotlar</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 fw-normal text-muted">Telefon</dt>
                        <dd class="col-7 mb-2" dir="ltr">+{{ $teacher->phone }}</dd>

                        <dt class="col-5 fw-normal text-muted">E-pochta</dt>
                        <dd class="col-7 mb-2 min-w-0 text-break">
                            @if($teacher->email)
                                {{ $teacher->email }}
                                @if($teacher->email_verified_at)
                                    <span class="badge bg-label-success ms-1">Tasdiqlangan</span>
                                @else
                                    <span class="badge bg-label-warning ms-1">Tasdiqlanmagan</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-5 fw-normal text-muted">Tug‘ilgan sana</dt>
                        <dd class="col-7 mb-2">
                            {{ $teacher->date_born ? \Carbon\Carbon::parse($teacher->date_born)->format('d.m.Y') : '—' }}
                        </dd>

                        <dt class="col-5 fw-normal text-muted">Passport</dt>
                        <dd class="col-7 mb-2">{{ $teacher->passport ?: '—' }}</dd>

                        <dt class="col-5 fw-normal text-muted">Manzil</dt>
                        <dd class="col-7 mb-2">{{ $teacher->location ?: '—' }}</dd>

                        <dt class="col-5 fw-normal text-muted">Qo‘shilgan</dt>
                        <dd class="col-7 mb-0">{{ $teacher->created_at?->format('d.m.Y') ?: '—' }}</dd>
                    </dl>

                    @if($teacher->description)
                        <div class="mt-3 pt-3 border-top">
                            <div class="text-muted mb-1" style="font-size: .82rem;">Izoh</div>
                            <div>{{ $teacher->description }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- So'nggi darslar --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>So‘nggi darslar</span>
                    <span class="badge bg-label-info">{{ $recentLessons->count() }}</span>
                </div>

                @if($recentLessons->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-calendar-x"></i>
                        <h6>Dars qayd etilmagan</h6>
                        <p class="mb-0">Bu o‘qituvchi hali davomat kiritmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Dars</th>
                                <th>Guruh</th>
                                <th class="text-end">Sana</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($recentLessons as $lesson)
                                <tr>
                                    <td>
                                        <i class="bx bx-book-open me-1 text-muted"></i>
                                        {{ $lesson->name ?: 'Dars' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-label-primary">
                                            {{ $groupNames[(int) $lesson->group] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ $lesson->created_at?->format('d.m.Y H:i') ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
