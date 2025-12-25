@extends('template.master')
@section('content')
    @role('admin')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- Statistics -->
            <div class="col-lg-8 col-12">
                <div class="row">
                    <!-- Students Card -->
                    <div class="col-md-6 col-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex flex-column">
                                        <h5 class="card-title text-nowrap mb-2">Total Students</h5>
                                        <h3 class="mb-0">{{ $number_of_students }}</h3>
                                    </div>
                                    <div class="avatar avatar-md bg-label-primary p-2 rounded-circle">
                                        <span class="avatar-content text-primary">
                                            <i class="bx bx-user-check bx-md"></i>
                                        </span>
                                    </div>
                                </div>
                                <small class="text-muted">As of {{ now()->format('d M Y') }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Income Card -->
                    <div class="col-md-6 col-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex flex-column">
                                        <h5 class="card-title text-nowrap mb-2">Today's Income</h5>
                                        <h3 class="mb-0">{{ number_format($daily_income, 0, '.', ' ') }} UZS</h3>
                                    </div>
                                    <div class="avatar avatar-md bg-label-success p-2 rounded-circle">
                                        <span class="avatar-content text-success">
                                            <i class="bx bx-dollar bx-md"></i>
                                        </span>
                                    </div>
                                </div>
                                <small class="text-muted">For {{ now()->format('d M Y') }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Teachers Table -->
                    <div class="col-12">
                        <div class="card m-2">
                            <h5 class="card-header">Active Teachers</h5>
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Teacher</th>
                                        <th>Groups</th>
                                        <th>Students</th>
                                        <th class="text-end">Salary</th>
                                    </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                    @forelse($teachers as $teacher)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-3">
                                                        @if($teacher->photo)
                                                            <img src="{{ asset('storage/' . $teacher->photo) }}" alt="Avatar" class="rounded-circle">
                                                        @else
                                                            <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr($teacher->name, 0, 2)) }}</span>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 text-truncate" style="max-width: 150px;">{{ $teacher->name }}</h6>
                                                        <small class="text-muted">{{ $teacher->percent }}%</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-label-info">{{ $teacher->teacherhasGroup() }} Groups</span></td>
                                            <td><span class="badge bg-label-warning">{{ $teacher->teacherHasStudents() }} Students</span></td>
                                            <td class="text-end fw-bold text-success">{{ number_format($teacher->teacherPayment(), 0, ' ', ' ') }} UZS</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No active teachers found.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Side Cards -->
            <div class="col-lg-4 col-12">
                <!-- Profit Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">Total Profit</h5>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <div class="avatar avatar-lg bg-label-info p-3 mb-3 rounded-circle">
                            <span class="avatar-content text-info">
                                <i class="bx bx-line-chart bx-lg"></i>
                            </span>
                        </div>
                        <h4 class="mb-0">{{ number_format($profit, 0, '.', ' ') }} UZS</h4>
                        <small class="text-muted">As of {{ today()->format('d M Y') }}</small>
                    </div>
                </div>

                <!-- Absences Card -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0">Today's Absences</h5>
                        <span class="badge bg-danger">{{ count($today_attendances) }}</span>
                    </div>
                    <div class="card-body">
                        @forelse($today_attendances as $attendance)
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <span>{{ $loop->iteration }}. {{ $attendance->user->name }}</span>
                                    <small class="fw-semibold text-muted">{{ $attendance->group->name }}</small>
                                </li>
                            </ul>
                        @empty
                            <p class="text-center text-muted mt-3">No absences reported today.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endrole

    @role('user')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- Test Results -->
            {{--            <div class="col-md-8 col-12 mb-4">--}}
            {{--                <div class="card h-100">--}}
            {{--                    <div class="card-header">--}}
            {{--                        <h5 class="card-title mb-0">Your Test Results</h5>--}}
            {{--                    </div>--}}
            {{--                    <div class="card-body">--}}
            {{--                        <canvas id="myChart"></canvas>--}}
            {{--                    </div>--}}
            {{--                </div>--}}
            {{--            </div>--}}

            <!-- Salary Card -->
            <div class="col-md-4 col-12 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Your Salary</h5>
                    </div>
                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                        <i class="bx bx-wallet bx-lg text-primary mb-3"></i>
                        <h4>Coming Soon</h4>
                        <p class="text-muted">Salary details will be available here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('myChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], // Example labels
                    datasets: [{
                        label: 'Test Score',
                        data: [85, 92, 78, 88, 95, 81], // Example data
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
    @endrole
@endsection