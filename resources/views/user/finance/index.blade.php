@extends('template.master')
@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">
            <div class="col-md-6">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                    <div class="card-title">
                                        <h5 class="text-nowrap mb-2">All Consumption</h5>
                                        {{--                                        <span class="badge bg-label-warning rounded-pill">Year 2021</span>--}}
                                    </div>
                                    <div class="mt-sm-auto">
                                        {{--                                        <small class="text-success text-nowrap fw-semibold"--}}
                                        {{--                                        ><i class="bx bx-chevron-up"></i> 68.2%</small--}}
                                        {{--                                        >--}}
                                        <h3 class="mb-0">{{number_format($consumption, 0,'.',' ' )}}</h3>
                                    </div>
                                </div>
                                <div id="profileReportChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                    <div class="card-title">
                                        <h5 class="text-nowrap mb-2">Profile Report</h5>
                                        <span class="badge bg-label-warning rounded-pill">Year 2021</span>
                                    </div>
                                    <div class="mt-sm-auto">
                                        <small class="text-success text-nowrap fw-semibold"
                                        ><i class="bx bx-chevron-up"></i> 68.2%</small
                                        >
                                        <h3 class="mb-0">$84,686k</h3>
                                    </div>
                                </div>
                                <div id="profileReportChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-column flex-sm-row">
                    @include('user.finance.create')
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Reason</th>
                            <th>Payment</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($finances as $finance)
                            <tr>
                                <td>{{ $finance->reason }}</td>
                                <td>{{ $finance->payment }}</td>
                                <td>{{ $finance->type_name }}</td>
                                <td>
                                    <div class="d-flex">

                                        @include('user.finance.edit')

                                        @include('user.finance.delete')

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
