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
                                    </div>
                                    <div class="mt-sm-auto">
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
                                        <h5 class="text-nowrap mb-2">Today's Consumption</h5>
                                    </div>
                                    <div class="mt-sm-auto">
                                        <h3 class="mb-0">{{number_format($daily_consumption,0,' ',' ')}}</h3>
                                    </div>
                                </div>
                                <div id="profileReportChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mt-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0">Finance</h5>
                        @include('admin.finance.create')
                    </div>

                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Reason</th>
                                <th>Payment</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody id="myTable">
                            @forelse($finances as $finance)
                                <tr>
                                    <td>{{ $finance->reason }}</td>
                                    <td>{{ number_format($finance->payment,0,'',' ') }}</td>
                                    <td>{{ $finance->type_name }}</td>
                                    <td>{{ $finance->created_at }}</td>
                                    <td>
                                        <div class="d-flex">
                                            @include('admin.finance.edit')
                                            @include('admin.finance.delete')
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No finance records found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        function formatNumber(input) {
            let value = input.value.replace(/\s+/g, '');
            if (!isNaN(value)) {
                input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }
        }

        function formatAllNumbersBeforeSubmit() {
            document.querySelectorAll('input[type="text"]').forEach(input => {
                input.value = input.value.replace(/\s+/g, '');
            });
        }

    </script>
@endsection