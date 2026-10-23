@extends('template.master')
@section('content')

    <div class="card">

        {{-- To'lovlar sahifasi bilan bir xil sarlavha: chapda nechta talaba
             yashirilgani, o'ngda filtrni almashtirish va amallar. --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">Talabalar</h5>
                @if(! $showAll && $hidden > 0)
                    <small class="text-muted">
                        {{ $hidden }} ta talaba ko‘rsatilmadi — kutish zalida yoki bitirgan.
                    </small>
                @elseif($showAll)
                    <small class="text-muted">Kutish zalidagilar va bitirganlar ham ko‘rsatilmoqda.</small>
                @endif
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2">
                @if($showAll)
                    <a href="{{ route('student.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bx bx-filter-alt me-1"></i> Faqat faol talabalar
                    </a>
                @else
                    <a href="{{ route('student.index', ['all' => 1]) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bx bx-show me-1"></i> Hammasini ko‘rsatish
                    </a>
                @endif

                @role('admin')
                <div class="btn-group">
                    <button class="btn btn-sm btn-label-primary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-export me-sm-1"></i>
                        <span class="d-none d-sm-inline-block">Eksport</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ URL::to('/student/pdf-list') }}"><i
                                        class="bx bxs-file-pdf me-1"></i> PDF</a></li>
                        <li><a class="dropdown-item" href="{{ route('students.export.all') }}"><i
                                        class="bx bxs-file-export me-1"></i> Excel</a></li>
                    </ul>
                </div>
                @endrole

                <a href="{{ route('student.create') }}" class="btn btn-sm btn-primary">
                    <i class="bx bx-plus me-sm-1"></i>
                    <span class="d-none d-sm-inline-block">Yangi talaba</span>
                </a>
            </div>
        </div>

        {{-- Ustunlar tor ekranda yashiriladi: "Amal" ustuni doim ko'rinib
             turishi va jadval yonga surilmasligi kerak. --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th style="width: 1%;">#</th>
                    <th>Ism</th>
                    <th class="d-none d-md-table-cell">Telefon</th>
                    <th class="d-none d-xl-table-cell">Ota-ona tel.</th>
                    <th class="d-none d-sm-table-cell">Guruh</th>
                    <th class="text-end" style="width: 1%;">Amal</th>
                </tr>
                </thead>
                <tbody id="myTable" class="table-border-bottom-0">
                @forelse($students as $student)
                    <tr>
                        <td class="text-muted pe-1">{{ $loop->iteration }}</td>
                        {{-- max-width:0 + width:100%: uzun ism kesiladi (…), jadvalni kengaytirmaydi. --}}
                        <td style="max-width: 0; width: 100%;"><x-avatar :user="$student" label /></td>
                        <td class="d-none d-md-table-cell text-nowrap" dir="ltr">+{{ $student->phone }}</td>
                        <td class="d-none d-xl-table-cell text-nowrap" dir="ltr">{{ $student->parents_tel }}</td>
                        <td class="d-none d-sm-table-cell" style="font-size: .85rem; min-width: 150px; overflow-wrap: normal;">{{ $student->studentsGroup() }}</td>
                        <td class="text-end ps-1">
                            <div class="d-inline-flex flex-nowrap gap-1">
                                <a href="{{ route('student.show', $student->id) }}" class="btn btn-sm btn-outline-primary px-2"
                                   title="Ko‘rish" aria-label="Ko‘rish">
                                    <i class="bx bx-show-alt"></i>
                                </a>
                                <a href="{{ route('student.edit', $student->id) }}" class="btn btn-sm btn-outline-warning px-2"
                                   title="Tahrirlash" aria-label="Tahrirlash">
                                    <i class="bx bx-edit-alt"></i>
                                </a>
                                <form action="{{ route('student.destroy', $student->id) }}" method="post" class="m-0"
                                      onsubmit="return confirm({{ \Illuminate\Support\Js::from($student->name . ' o‘chirilsinmi?') }});">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2"
                                            title="O‘chirish" aria-label="O‘chirish">
                                        <i class="bx bx-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Talaba topilmadi.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection