@extends('template.master')

@section('title', 'Test natijalari')
@section('subtitle', trim(($groupName ?? '') . ' · ' . ($testName ?? ''), ' ·'))

@section('content')

    @php
        $tone = fn (?int $mark) => $mark === null ? 'secondary' : ($mark >= 80 ? 'success' : ($mark >= 60 ? 'warning' : 'danger'));
    @endphp

    @if($students->isNotEmpty())
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">{{ $groupName }}</h5>
                    <small class="text-muted">{{ $testName }} · {{ $students->count() }} ta talaba</small>
                </div>
                <a href="{{ route('test.report', $historyId) }}" class="btn btn-sm btn-outline-danger">
                    <i class="bx bxs-file-pdf me-1"></i> Hisobot (PDF)
                </a>
            </div>

            <form action="{{ route('deleteMultiple') }}" method="post"
                  onsubmit="return confirm('Belgilangan talabalarning shu testdagi barcha ballari o‘chirilsinmi?');">
                @csrf
                @method('DELETE')

                <div class="d-flex gap-2 px-4 pb-3">
                    <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary">Hammasini belgilash</button>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Belgilanganlarni o‘chirish</button>
                </div>

                {{-- Bitta talaba — bitta qator. Ustunlar — shu testda baholangan
                     ko'nikmalar; belgi talabaning shu testdagi barcha ballarini oladi. --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 results-table">
                        <thead>
                        <tr>
                            <th style="width: 1%;"></th>
                            <th style="width: 1%;">#</th>
                            <th>Talaba</th>
                            @foreach($skills as $skill => $label)
                                <th class="text-center">{{ $label }} <span class="text-muted fw-normal">/{{ $maxes[$skill] ?? 100 }}</span></th>
                            @endforeach
                            @if(count($skills) > 1)
                                <th class="text-center">O‘rtacha</th>
                                <th class="text-center">Yig‘indi</th>
                            @endif
                            <th class="text-end">Guruh</th>
                        </tr>
                        </thead>
                        <tbody id="myTable">
                        @foreach($students as $row)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input js-row-check" name="selectedItems[]"
                                           value="{{ implode(',', $row->ids) }}"
                                           aria-label="{{ $row->name }}">
                                </td>
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row->name }}</div>
                                    @if($row->comment)
                                        <small class="text-muted">{{ $row->comment }}</small>
                                    @endif
                                </td>
                                @foreach($skills as $skill => $label)
                                    <td class="text-center">
                                        @if(array_key_exists($skill, $row->marks))
                                            <span class="badge bg-label-{{ $tone($row->marks[$skill]['percent']) }}" title="{{ $row->marks[$skill]['percent'] }}%">{{ $row->marks[$skill]['mark'] }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                @if(count($skills) > 1)
                                    <td class="text-center">
                                        <span class="badge bg-label-{{ $tone($row->average) }}" style="font-size: .9rem;">{{ $row->average === null ? '—' : $row->average . '%' }}</span>
                                    </td>
                                    <td class="text-center fw-semibold">{{ $row->sum }}/{{ $row->sum_max }}</td>
                                @endif
                                <td class="text-end">
                                    {{-- Ko'chirish sahifasi: summa, qarzdorlik va guruh tarixini
                                         to'g'ri yuritadi. Eski modal (GroupExtraController@change_group)
                                         to'lovni nolga tushirib, butun davomatni yangi guruhga yozardi. --}}
                                    <a href="{{ route('student.transfer.form', $row->user_id) }}"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bx bx-transfer"></i>
                                        <span class="d-none d-xxl-inline ms-1">Guruhini o‘zgartirish</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

    @else
        <div class="card">
            <div class="empty-state">
                <i class="bx bx-clipboard"></i>
                <h6>Ma’lumotlar topilmadi</h6>
                <p>Ushbu test uchun hali baholar kiritilmagan yoki barcha baholar o‘chirilgan.</p>
                <form action="{{ route('test.destroy.all', $historyId) }}" method="POST"
                      onsubmit="return confirm('Haqiqatan ham ushbu test tarixini va unga bog‘liq barcha baholarni o‘chirmoqchimisiz? Bu amalni ortga qaytarib bo‘lmaydi!');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Test tarixini o‘chirish</button>
                </form>
            </div>
        </div>
    @endif

    {{-- Ko'nikma ustunlari ko'p: standart katak oralig'i (1.35rem) bilan
         jadval odatiy ekranga sig'masdi. --}}
    <style>
        html[data-theme] .results-table th,
        html[data-theme] .results-table td { padding: .6rem .55rem; }
    </style>

    <script>
        document.getElementById('selectAllBtn')?.addEventListener('click', () => {
            const boxes = [...document.querySelectorAll('.js-row-check')];
            const allOn = boxes.every(b => b.checked);
            boxes.forEach(b => { b.checked = !allOn; });
        });
    </script>

@endsection
