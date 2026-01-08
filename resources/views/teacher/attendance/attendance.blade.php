@extends('template.master')

@section('content')

    @role('user')
    <div class="card shadow-md rounded-lg mb-4">
        <form action="{{ route('attendance.submit', $id) }}" method="post">
            @csrf
            <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap py-3">
                <h5 class="card-title mb-0 text-primary">Mark Attendance</h5>
                <div class="d-flex align-items-center">
                    {{-- Lesson input removed as per request --}}
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th class="text-center">Status</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @forelse($students as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><b>{{ $student->name }}</b></td>
                            <td class="text-center">
                                <div class="btn-group" role="group" aria-label="Attendance status">
                                    <input type="radio" class="btn-check" name="status[{{ $student->id }}]" id="status-present-{{ $student->id }}" value="1" checked>
                                    <label class="btn btn-outline-success" for="status-present-{{ $student->id }}">Present</label>

                                    <input type="radio" class="btn-check" name="status[{{ $student->id }}]" id="status-absent-{{ $student->id }}" value="0">
                                    <label class="btn btn-outline-danger" for="status-absent-{{ $student->id }}">Absent</label>

                                    <input type="radio" class="btn-check" name="status[{{ $student->id }}]" id="status-late-{{ $student->id }}" value="2">
                                    <label class="btn btn-outline-warning" for="status-late-{{ $student->id }}">Reasonable</label>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">No students found in this group.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer text-end py-3">
                <button type="submit" class="btn btn-primary">Submit Attendance</button>
            </div>

        </form>
    </div>
    @endrole


    <div class="card shadow-md rounded-lg mt-4">
        <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap py-3">
            <h5 class="card-title mb-0 text-primary">Attendance for {{ \Carbon\Carbon::createFromDate($year, $month)->format('F Y') }}</h5>

            <div class="d-flex align-items-center">
                @role('admin')
                <form method="GET" action="{{ route('group.attendance', $group->id) }}" class="d-flex me-3">
                    <select id="month" name="date" class="form-select me-2">
                        <option value="">Select Month</option>
                        @php
                            $currentYear = date('Y');
                            for ($monthOption = 1; $monthOption <= 12; $monthOption++) {
                                $monthNum = str_pad($monthOption, 2, '0', STR_PAD_LEFT);
                                $monthYear = $currentYear . '-' . $monthNum;
                                $monthName = date('F', mktime(0, 0, 0, $monthOption, 1));
                                $selected = ($year . '-' . $monthNum == $monthYear) ? 'selected' : '';
                                echo "<option value=\"$monthYear\" $selected>$monthName $currentYear</option>";
                            }
                        @endphp
                    </select>
                    <button type="submit" class="btn btn-outline-primary">Show</button>
                </form>

                <form id="exportForm" method="GET" action="{{ route('export.attendances', ['id' => $group->id]) }}">
                    <input type="hidden" id="export_date" name="date" value="{{ $year . '-' . $month }}">
                    <button type="submit" class="btn btn-success d-flex align-items-center">
                        <i class="bx bxs-file-export me-1"></i> Export to Excel
                    </button>
                </form>
                @endrole
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center">
                @php
                    // Only show columns for days where lessons were recorded
                    $lessonDays = $lessonDays ?? [];
                    $colspan = max(count($lessonDays), 1) + 1; // name + days
                @endphp

                <thead class="table-light">
                <tr>
                    <th class="text-start">Name</th>
                    @foreach($lessonDays as $day)
                        <th>{{ (int) $day }}</th>
                    @endforeach
                </tr>
                </thead>

                <tbody>
                @forelse ($data as $userName => $days)
                    <tr>
                        <td class="text-start fw-bold">{{ $userName }}</td>
                        @foreach($lessonDays as $day)
                            @php
                                $status = $days[$day] ?? null;
                                $isAbsent = ($status === '0' || $status === 0);
                                $isPresent = ($status === '1' || $status === 1);
                                $isLate = ($status === '2' || $status === 2);
                            @endphp
                            <td class="{{ $isAbsent ? 'bg-danger-subtle text-danger' : ($isLate ? 'bg-warning-subtle text-warning' : ($isPresent ? 'text-success' : '')) }}">
                                @if ($isPresent)
                                    <i class="bx bx-check-circle"></i>
                                @elseif ($isAbsent)
                                    <i class="bx bx-x-circle"></i>
                                @elseif ($isLate)
                                    <i class="bx bx-info-circle"></i>
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="text-center py-4 text-muted">No attendance data available for this month.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-md rounded-lg mt-4">
        <div class="card-header bg-light border-bottom">
            <h5 class="card-title mb-0 text-primary">Recent Attendance Records</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Teacher</th>
                        <th>Lesson</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $loop->index + 1 }}</td>
                        <td>{{ $attendance->user->name }}</td>
                        <td>{{ $attendance->teacher->name ?? 'N/A' }}</td>
                        <td>{{ $attendance->lesson->name ?? 'N/A' }}</td>
                        <td>
                            @if($attendance->status == 1)
                                <span class="badge bg-success">Present</span>
                            @elseif($attendance->status == 0)
                                <span class="badge bg-danger">Absent</span>
                            @elseif($attendance->status == 2)
                                <span class="badge bg-warning">Reasonable</span>
                            @endif
                        </td>
                        <td>{{ $attendance->created_at->format('d M Y H:i') }}</td>
                        <td class="text-center">
                            <form action="{{route('attendance.delete', $attendance->id)}}" method="POST" onsubmit="return confirm('Are you sure you want to delete this attendance record?');">
                                @csrf
                                @method("DELETE")
                                <button type="submit" class="btn btn-sm btn-danger d-inline-flex align-items-center">
                                    <i class="bx bx-trash-alt me-1"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No recent attendance records found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            <div class="card-footer">
                {{ $attendances->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const monthSelect = document.getElementById('month');
            const exportDateInput = document.getElementById('export_date');

            if (monthSelect && exportDateInput) {
                monthSelect.addEventListener('change', function () {
                    exportDateInput.value = this.value;
                });
            }
        });
    </script>

@endsection
