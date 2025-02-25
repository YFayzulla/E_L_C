@extends('template.master')

@section('content')
    @role('admin')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <div class="row">
                    <!-- Students Card -->
                    <div class="col-lg-6 col-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5>Students</h5>
                                <span class="badge bg-label-success rounded-pill">{{ now()->format('d-m-y') }}</span>
                                <h3 class="mt-3"><b>{{ $number_of_students }}</b></h3>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Income Card -->
                    <div class="col-lg-6 col-12 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5>Today's Income</h5>
                                <span class="badge bg-label-warning rounded-pill">{{ now()->format('d-m-y') }}</span>
                                <h6 class="mt-3">{{ number_format($daily_income, 0, '.', ' ') }} sum</h6>
                            </div>
                        </div>
                    </div>

                    <!-- Teachers Table -->
                    <div class="col-12">
                        <div class="card m-2">
                            <h5 class="card-header">Teachers</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>%</th>
                                        <th>Groups</th>
                                        <th>Students</th>
                                        <th>Salary</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($teachers as $teacher)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td><strong>{{ $teacher->name }}</strong></td>
                                            <td>{{ $teacher->percent }}</td>
                                            <td>{{ $teacher->teacherhasGroup() }}</td>
                                            <td>{{ $teacher->teacherHasStudents() }}</td>
                                            <td>{{ number_format($teacher->teacherPayment(), 0, '.', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Profit Card -->
                <div class="card mb-3">
                    <div class="card-body ">
                        <h5>Profit</h5>
                        <span class="badge bg-label-info rounded-pill">{{ today()->format('d-m-y') }}</span>
                        <h6 class="mt-2">{{ number_format($profit, 0, '.', ' ') }} sum</h6>
                    </div>
                </div>

                <!-- Attendance Card -->
                <div class="card ">
                    <div class="card-header">
                        <h5>{{ count($attendances) == 0 ? 'Attendance is ok' : count($attendances) . " Students didn't come" }}</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled border-1">
                            @foreach($attendances as $attendance)
                                <li class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <span class="border-1">{{ $loop->index + 1 }}</span>
                                    <span class="border-1">{{ $attendance->user->name }}</span>
                                    <span>{{ $attendance->group->name }}</span>
                                    <span>{{ $attendance->created_at }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="card-footer">
                        {{ $attendances->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endrole

    @role('user')
    <div class="p-4 m-4 bg-white shadow rounded-lg text-success">
        <p>Success</p>
    </div>
    @endrole
@endsection
