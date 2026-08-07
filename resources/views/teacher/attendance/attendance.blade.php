@extends('template.master')

@section('title', $group->name)
@section('subtitle', 'Guruh davomati')

@section('content')

    @php
        /** Har doim Y-m ko'rinishidagi tanlangan oy. */
        $selectedMonth = $date ?? sprintf('%04d-%02d', $year, $month);
        $monthLabel = \Carbon\Carbon::createFromDate($year, $month, 1)
            ->locale('uz_Latn')->translatedFormat('F Y');

        $lessonDays = $lessonDays ?? [];
        $todayDay = (int) now()->day;
        $currentMonth = now()->format('Y-m');
        // Yo'qlama HAR DOIM bugungi darsga yoziladi. O'tgan oy ko'rilayotganda
        // radiolar bugungi holatni ko'rsata olmaydi (jadval boshqa oyники), shuning
        // uchun forma umuman chiqarilmaydi — aks holda "Saqlash" bosilsa bugungi
        // yozuvlar sanoqsiz o'chib ketardi.
        $isCurrentMonth = $selectedMonth === $currentMonth;
        $markedToday = $isCurrentMonth && in_array($todayDay, $lessonDays, true);

        $rateTone = $rate === null ? 'secondary' : ($rate >= 90 ? 'success' : ($rate >= 75 ? 'warning' : 'danger'));

        // Oxirgi 24 oy. Bookmark qilingan eskiroq oy ham ro'yxatdan tushib qolmaydi.
        $monthOptions = collect(range(0, 23))->map(fn($i) => now()->startOfMonth()->subMonths($i));
        if (! $monthOptions->contains(fn($m) => $m->format('Y-m') === $selectedMonth)) {
            $monthOptions->prepend(\Carbon\Carbon::createFromDate($year, $month, 1));
        }
    @endphp

    <div class="page-head justify-content-end">
        @role('admin')
        <a href="{{ route('attendance.overview') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Umumiy davomat
        </a>
        @endrole
        @role('user')
        <a href="{{ route('attendance') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Guruhlarim
        </a>
        @endrole
    </div>

    {{-- Oy tanlash + Excel: o'qituvchi ham, admin ham ko'radi --}}
    <div class="filter-card">
        <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="month">Oy</label>
                <select id="month" name="date" class="form-select">
                    @foreach($monthOptions as $option)
                        <option value="{{ $option->format('Y-m') }}"
                                @selected($option->format('Y-m') === $selectedMonth)>
                            {{ $option->locale('uz_Latn')->translatedFormat('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-search-alt me-1"></i> Ko‘rish
                    </button>
                    <a href="{{ route('export.attendances', ['id' => $group->id, 'date' => $selectedMonth]) }}"
                       class="btn btn-outline-secondary">
                        <i class="bx bx-export me-1"></i> Excelga yuklash
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Oylik xulosa --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">O‘tilgan darslar</div>
                        <div class="stat-value">{{ count($lessonDays) }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-calendar"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Kelmagan</div>
                        <div class="stat-value text-danger">{{ $absentCount }}</div>
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
                        <div class="stat-value text-warning">{{ $lateCount }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-time-five"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Davomat</div>
                        <div class="stat-value text-{{ $rateTone }}">{{ $rate === null ? '—' : $rate . '%' }}</div>
                    </div>
                    <span class="stat-icon is-{{ $rateTone === 'secondary' ? 'info' : $rateTone }}">
                        <i class="bx bx-calendar-check"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Yo'qlama qilish (faqat o'qituvchi, faqat joriy oyda) --}}
    @role('user')
    @if(! $isCurrentMonth)
        <div class="card mb-4">
            <div class="empty-state">
                <i class="bx bx-info-circle"></i>
                <h6>Arxiv ko‘rinishi</h6>
                <p class="mb-0">
                    Yo‘qlama faqat joriy oyda olinadi.
                    <a href="{{ url()->current() }}?date={{ $currentMonth }}">Joriy oyga qaytish</a>
                </p>
            </div>
        </div>
    @else
    <div class="card mb-4">
        <form action="{{ route('attendance.submit', $id) }}" method="post">
            @csrf
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="min-w-0">
                    <span>Yo‘qlama — {{ now()->locale('uz_Latn')->translatedFormat('d F Y') }}</span>
                    @if($markedToday)
                        <span class="badge bg-label-success ms-2">Bugun belgilangan</span>
                    @endif
                </div>
                <div style="min-width: 16rem;">
                    <input type="text" name="lesson" class="form-control form-control-sm @error('lesson') is-invalid @enderror"
                           value="{{ old('lesson') }}" maxlength="255" placeholder="Dars nomi (ixtiyoriy)">
                    @error('lesson')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @if($students->isEmpty())
                <div class="empty-state">
                    <i class="bx bx-user-x"></i>
                    <h6>Talaba yo‘q</h6>
                    <p class="mb-0">Bu guruhga hali talaba biriktirilmagan.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th style="width: 3rem;">#</th>
                            <th>Talaba</th>
                            <th class="text-end">Holat</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($students as $student)
                            @php
                                $current = $markedToday ? (int) ($data[$student->id][$todayDay] ?? 1) : 1;
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <x-avatar :user="$student" label class="fw-semibold" />
                                        {{-- A teacher may move a student between the groups they
                                             teach; the controller re-checks that on every request. --}}
                                        <a href="{{ route('student.transfer.form', $student->id) }}"
                                           class="btn-icon text-muted flex-shrink-0"
                                           title="{{ $student->name }} — boshqa guruhga ko‘chirish">
                                            <i class="bx bx-transfer"></i>
                                        </a>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="{{ $student->name }} holati">
                                        <input type="radio" class="btn-check" name="status[{{ $student->id }}]"
                                               id="st-1-{{ $student->id }}" value="1" @checked($current === 1)>
                                        <label class="btn btn-outline-secondary" for="st-1-{{ $student->id }}">Keldi</label>

                                        <input type="radio" class="btn-check" name="status[{{ $student->id }}]"
                                               id="st-0-{{ $student->id }}" value="0" @checked($current === 0)>
                                        <label class="btn btn-outline-danger" for="st-0-{{ $student->id }}">Kelmadi</label>

                                        <input type="radio" class="btn-check" name="status[{{ $student->id }}]"
                                               id="st-2-{{ $student->id }}" value="2" @checked($current === 2)>
                                        <label class="btn btn-outline-warning" for="st-2-{{ $student->id }}">Kechikdi</label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="page-sub mb-0">
                        Bir kunga bitta dars yoziladi — qayta yuborsangiz bugungi yo‘qlama yangilanadi.
                    </span>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Saqlash
                    </button>
                </div>
            @endif
        </form>
    </div>
    @endif
    @endrole

    {{-- Oylik jadval --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>{{ $monthLabel }} — davomat jadvali</span>
            <div class="d-flex align-items-center flex-wrap gap-3" style="font-size: .78rem;">
                <span><span class="badge bg-label-success"><i class="bx bx-check"></i></span> keldi</span>
                <span><span class="badge bg-label-danger"><i class="bx bx-x"></i></span> kelmadi</span>
                <span><span class="badge bg-label-warning"><i class="bx bx-time"></i></span> kechikdi</span>
            </div>
        </div>

        @if(empty($lessonDays) || $students->isEmpty())
            <div class="empty-state">
                <i class="bx bx-calendar-x"></i>
                <h6>Ma’lumot yo‘q</h6>
                <p class="mb-0">{{ $monthLabel }} uchun bu guruhda dars qayd etilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover grade-table mb-0">
                    <thead>
                    <tr>
                        <th style="min-width: 12rem;">Talaba</th>
                        @foreach($lessonDays as $day)
                            <th>{{ $day }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($students as $student)
                        <tr>
                            <td class="fw-semibold">{{ $studentNames[$student->id] ?? $student->name }}</td>
                            @foreach($lessonDays as $day)
                                @php $status = $data[$student->id][$day] ?? null; @endphp
                                <td>
                                    @if($status === 0)
                                        <span class="badge bg-label-danger" title="Kelmadi"><i class="bx bx-x"></i></span>
                                    @elseif($status === 2)
                                        <span class="badge bg-label-warning" title="Kechikdi"><i class="bx bx-time"></i></span>
                                    @elseif($status === 1)
                                        <span class="badge bg-label-success" title="Keldi"><i class="bx bx-check"></i></span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Qoldirilgan darslar ro'yxati --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>{{ $monthLabel }} — qoldirilgan darslar</span>
            <span class="badge bg-label-primary">{{ $attendances->total() }}</span>
        </div>

        @if($attendances->isEmpty())
            <div class="empty-state">
                <i class="bx bx-check-circle"></i>
                <h6>Qoldirish yo‘q</h6>
                <p class="mb-0">Bu oyda kelmagan yoki kechikkan talaba qayd etilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Talaba</th>
                        <th>Dars</th>
                        <th>Kim belgilagan</th>
                        <th>Holat</th>
                        <th class="text-end">Amal</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($attendances as $attendance)
                        <tr>
                            <td class="text-muted">{{ $attendance->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td class="fw-semibold">{{ $attendance->user?->name ?? '—' }}</td>
                            <td class="text-muted">{{ $attendance->lesson?->name ?? '—' }}</td>
                            <td class="text-muted">{{ $attendance->teacher?->name ?? '—' }}</td>
                            <td>
                                @if((int) $attendance->status === 2)
                                    <span class="badge bg-label-warning">Kechikdi</span>
                                @else
                                    <span class="badge bg-label-danger">Kelmadi</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('attendance.delete', $attendance->id) }}" method="post"
                                      onsubmit="return confirm('Ushbu davomat yozuvi o‘chirilsinmi?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="O‘chirish">
                                        <i class="bx bx-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($attendances->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $attendances->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
