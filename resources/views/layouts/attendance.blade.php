@extends('template.master')
@section('content')

    {{-- Yuqoridagi Matritsa qismi o'zgarishsiz qoladi... --}}
    <div class="card mt-3">
        <div class="p-4 mt-4 d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Attendance for {{ \Carbon\Carbon::createFromDate($year, $month)->format('F Y') }}</h4>
            {{-- Filter va Export tugmalari joyida... --}}
            <div class="d-flex">
                @role('admin')
                <div class="d-flex justify-content-center me-3">
                    <form method="GET" action="{{ route('group.attendance', $group->id) }}" class="d-flex">
                        <select id="month" name="date" class="form-select me-1">
                            <option value="">Select Month</option>
                            @php
                                $currentYear = date('Y');
                                for ($monthOption = 1; $monthOption <= 12; $monthOption++) {
                                    $monthNum = str_pad($monthOption, 2, '0', STR_PAD_LEFT);
                                    $monthYear = $currentYear . '-' . $monthNum;
                                    $monthName = date('F', mktime(0, 0, 0, $monthOption, 1));
                                    $selected = (request('date') == $monthYear || date('Y-m') == $monthYear && !request('date')) ? 'selected' : '';
                                    echo "<option value=\"$monthYear\" $selected>$monthName $currentYear</option>";
                                }
                            @endphp
                        </select>
                        <button type="submit" class="btn btn-primary">Show</button>
                    </form>
                </div>
                <div class="d-flex align-items-center">
                    <form id="exportFormLayout" method="GET" action="{{ route('export.attendances', ['id' => $group->id]) }}">
                        <input type="hidden" name="date" value="{{ request('date') ?? ($year . '-' . $month) }}">
                        <button type="submit" class="btn btn-danger">Export to Excel</button>
                    </form>
                </div>
                @endrole
            </div>
        </div>

        {{-- MATRITSA JADVALI --}}
        <div class="table-responsive">
            <table class="table table-bordered text-center">
                @php
                    $currentMonthDays = \Carbon\Carbon::createFromDate($year, $month)->daysInMonth;
                @endphp
                <thead>
                <tr>
                    <th>Name</th>
                    @for ($i = 1; $i <= $currentMonthDays; $i++)
                        @php
                            $currentDate = \Carbon\Carbon::createFromDate($year, $month, $i);
                            $isWeekend = $currentDate->isWeekend();
                            $isToday = $currentDate->isToday();
                        @endphp
                        <th class="{{ $isToday ? 'bg-success text-white' : ($isWeekend ? 'bg-danger text-white' : '') }}">
                            {{ $i }}
                        </th>
                    @endfor
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $userName => $days)
                    <tr>
                        <td class="text-start ps-3">{{ $userName }}</td>
                        @for ($i = 1; $i <= $currentMonthDays; $i++)
                            @php
                                $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                                $status = $days[$day] ?? '';

                                // Logika: 0 = Kelmadi (Qizil), 1 = Keldi (Yashil/Oddiy)
                                // Sizning kodingizda: $isPresent = ($status === '1' || $status === 1);

                                $currentDate = \Carbon\Carbon::createFromDate($year, $month, $i);
                                $isWeekend = $currentDate->isWeekend();
                            @endphp

                            <td class="{{ ($status === 0 || $status === '0') ? 'bg-danger text-white' : ($isWeekend ? 'bg-secondary text-white' : '') }}">
                                @if ($status === 1 || $status === '1')
                                    <i class='bx bx-check text-success'></i>
                                @elseif ($status === 0 || $status === '0')
                                    <span>NB</span>
                                @else
                                    {{ $status }}
                                @endif
                            </td>
                        @endfor
                    </tr>
                @empty
                    <tr><td colspan="{{ $currentMonthDays + 1 }}">No data available</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function(){
            var exportForm = document.getElementById('exportFormLayout');
            var monthSelect = document.getElementById('month');
            if (!exportForm || !monthSelect) return;
            exportForm.addEventListener('submit', function(){
                var selected = monthSelect.value;
                if (selected) {
                    var input = exportForm.querySelector('input[name="date"]');
                    if (input) input.value = selected;
                }
            });
        })();
    </script>

    {{-- PASTKI JADVAL (History) --}}
    <div class="card mt-4">
        <div class="card-header">
            <h5>Detailed Attendance History</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Teacher</th>
                    <th>Topic (Lesson)</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
                </thead>
                {{-- BU YERDA O'ZGARISH: $students emas, $attendanceRecords ishlatiladi --}}
                @forelse($attendanceRecords as $attendance)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        {{-- Null Safe Operator (?->) va (??) ishlatildi --}}
                        <td>{{ $attendance->user?->name ?? 'Unknown' }}</td>
                        <td>{{ $attendance->teacher?->name ?? 'Not found' }}</td>
                        <td>{{ $attendance->lesson?->name ?? 'Not entered' }}</td>
                        <td>{{ $attendance->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <form action="{{route('attendance.delete', $attendance->id)}}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete?');">
                                @csrf
                                @method("DELETE")
                                <button class="btn btn-sm btn-outline-danger"><i class="bx bx-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No attendance records found for the current month.</td>
                    </tr>
                @endforelse
            </table>
        </div>
        <div class="card-footer">
            {{-- Pagination --}}
            {{ $attendanceRecords->appends(['date' => request('date')])->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection