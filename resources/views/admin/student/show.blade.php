@extends('template.master')
@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Student's Data</h5>
            <div class="dt-action-buttons text-end pt-3 pt-md-0">
                <div class="dt-buttons btn-group flex-wrap">
                    <div class="btn-group">
                        <a class="btn buttons-collection dropdown-toggle btn-label-primary me-2" tabindex="0"
                           aria-controls="DataTables_Table_0" type="button" id="dropdownMenuButton"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span><i class="bx bx-export me-sm-1"></i> <span
                                        class="d-none d-sm-inline-block">Export</span></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="{{ URL::to('/student/pdf',$student->id) }}"><i
                                            class="bx bxs-file-pdf me-1"></i> Pdf</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md">
                <div class="card mb-3">
                    <div class="row g-0">
                        <div class="col-md-8">
                            <div class="card-body">
                                <h4><b>Full Name: </b>{{$student->name}}</h4>
                                <h4><b>Location:</b> {{$student->location}}</h4>
                                <h4><b>Tel: </b>{{$student->phone}}</h4>
                                <h4><b>Parents name: </b>{{$student->parents_name}} </h4>
                                <h4><b>Parents tel: </b> {{$student->parents_tel}}</h4>
                                <h4><b>Description: </b> {{($student->description)}}</h4>
                                <h4><b>Last Test Result: </b>{{$student->mark}}</h4>
                                <h4><b>Current Groups: </b> {{ $student->groups->pluck('name')->implode(', ') }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            @if($student->photo)
                                <img class="card-img card-img-right" src="{{asset( 'storage/'.$student->photo) }}" alt="Student Photo" />
                            @else
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <p class="text-muted">No Photo</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-6 mt-4">
            <div class="card">
                <h5 class="card-header">Payment History</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Paid</th>
                            <th>Type</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @forelse($student->studenthistory as $item)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{number_format($item->payment,0,'',' ')}}</td>
                                <td>{{$item->type_of_money}}</td>
                                <td>{{ \Carbon\Carbon::parse($item->date ?? $item->created_at)->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No payment history found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mt-4">
            <div class="card">
                <h5 class="card-header">Group History</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Group</th>
                            <th>Date Joined</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @forelse($groupHistory as $item)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$item->group}}</td>
                                <td>{{$item->created_at->format('d M Y, H:i')}}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No group history found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
