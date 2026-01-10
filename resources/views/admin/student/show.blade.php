@extends('template.master')
@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Student's Data</h5>
           @role('admin')
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
            @endrole
        </div>

        <div class="row mb-5">
            <div class="col-md">
                <div class="card mb-3">
                    <div class="row g-0">
                        <div class="col-md-8">
                            <div class="card-body">
                                <h4><b>Full Name: </b>{{$student->name}}</h4>
                                <h4><b>Tel: </b>{{$student->phone}}</h4>
                                @role ('admin')
                                <h4><b>Location:</b> {{$student->location}}</h4>
                                <h4><b>Parents name: </b>{{$student->parents_name}} </h4>
                                <h4><b>Parents tel: </b> {{$student->parents_tel}}</h4>
                                <h4><b>Last Test Result: </b>{{$student->mark}}</h4>
                                <h4><b>Current Groups: </b> {{ $student->groups->pluck('name')->implode(', ') }}</h4>
                                @endrole
                                <div class="mt-4">
                                    <h5 class="text-primary mb-3"><i class="bx bx-note me-2"></i>Comments & Description
                                    </h5>
                                    <div class="bg-light p-3 rounded border">
                                        @if($student->description)
                                            @php
                                                // Split description by newline to separate comments
                                                $comments = explode("\n", $student->description);
                                            @endphp
                                            <ul class="list-unstyled mb-0">
                                                @foreach($comments as $comment)
                                                    @if(trim($comment) !== '')
                                                        <li class="mb-2 pb-2 border-bottom border-light-subtle last:border-0">
                                                            <i class="bx bx-chevron-right text-muted me-1"></i> {{ trim($comment) }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-muted mb-0">No description or comments available.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @role('admin')
                        <div class="col-md-4 d-flex justify-content-center align-items-center p-3">
                            @if($student->photo)
                                <img class="img-fluid rounded shadow-sm"
                                     src="{{asset( 'storage/'.$student->photo) }}"
                                     alt="Student Photo"
                                     style="max-width: 180px; height: auto;">
                            @else
                                <p class="text-muted">No Photo</p>
                            @endif
                        </div>
                        @endrole
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @role('admin')
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
                            <th>Desc</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @forelse($student->studenthistory as $item)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{number_format($item->payment,0,'',' ')}}</td>
                                <td>{{$item->type_of_money}}</td>
                                <td>{{$item->description ?? '-'}}</td>
                                <td>{{ \Carbon\Carbon::parse($item->date ?? $item->created_at)->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No payment history found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endrole

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

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">Test Results</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Test Name</th>
                            <th>Group</th>
                            <th>Mark</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @forelse($testResults as $result)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$result->test_name}}</td>
                                <td>{{$result->group}}</td>
                                <td>
                                    <span class="badge bg-label-primary">{{$result->get_mark}}</span>
                                </td>
                                <td>{{$result->created_at->format('d M Y')}}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No test results found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
