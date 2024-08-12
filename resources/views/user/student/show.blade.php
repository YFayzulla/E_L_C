@extends('template.master')
@section('content')

    <div class="d-flex align-items-center justify-content-between">
        <h4 class="py-3 mb-4">Student`s data</h4>
        <ul class="nav nav-pills flex-column flex-md-row mb-3">
            <li class="nav-item">
                <a class="btn btn-danger" href="{{ URL::to('/student/pdf',$student->id) }}">Report</a>
            </li>
        </ul>
    </div>

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg table-responsive">
        <div class="max-w-xl mx-auto">
            <div class="container" style="display: flex; justify-content: space-between;">
                <div class="container__left">
                    <h3><b>Full Name: </b>{{$student->name}}</h3>
                    <h3><b>Location:</b> {{$student->location}}</h3>
                    <h3><b>Tel </b>{{$student->phone}}</h3>
                    <h4><b>Parents name: </b>{{$student->parents_name}} </h4>
                    <h4><b>Parents Tel </b> {{$student->parents_tel}}</h4>
                    <h4><b>Description:</b> {{($student->description)}}</h4>
                    <h5><b>Last Test Result:</b>{{$student->mark}}</h5>
                </div>

                <div class="container__right" style="max-width: 300px; margin-top: 20px;">
                    <img src="{{asset( 'storage/'.$student->photo) }}"
                         style="width: 200px; display: block; margin-left: auto;"
                         alt="">
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-6 mt-4">
            <div class="card">
                <h5 class="card-header">Payment history</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table table-dark">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Paid</th>
                            <th>type</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($student->studenthistory as $item)
                            <tr>
                                <th>{{$loop->index+1}}</th>
                                <th>{{$item->payment}}</th>
                                <th>{{$item->type_of_money}}</th>
                                <th>@if($item->date ==null)
                                        {{$item->created_at.'data'}}
                                    @else
                                        {{$item->date}}
                                    @endif</th>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mt-4">
            <div class="card">
                <h5 class="card-header">Travel of group</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table table-dark">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>group</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($student->studentinformation as $item)

                            <tr>
                                <th>{{$loop->index+1}}</th>
                                <th>{{$item->group}}</th>
                                <th>{{$item->created_at}}</th>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
