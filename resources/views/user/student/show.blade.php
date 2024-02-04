@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <div class="max-w-xl mx-auto">
            <a class="btn btn-danger float-right m-2" href="{{ URL::to('/student/pdf',$student->id) }}">Report</a>
            <div class="container" style="display: flex; justify-content: space-between;">
                <div class="container__left">
                    <h1 style="text-align: center">Student`s data</h1>
                    <h3><b>Full Name: </b>{{$student->name}}</h3>
                    <h3><b>Location:</b> {{$student->location}}</h3>
                    <h3><b>Tel </b>{{$student->phone}}</h3>

                    <h4><b>Parents name: </b>{{$student->parents_name}} </h4>
                    <h4><b>Parents Tel </b> {{$student->parents_tel}}</h4>
                    <h4><b>Description:</b> {{($student->description)}}</h4>

                    {{--                    <table class="table">--}}
                    {{--                        <tr>--}}
                    {{--                            <th>id</th>--}}
                    {{--                            <th>name</th>--}}
                    {{--                        </tr>--}}
                    {{--                        <tr>--}}
                    {{--                            <th>salom</th>--}}
                    {{--                        </tr>--}}
                    {{--                    </table>--}}


                    <table class="table">

                        <th>No</th>
                        <th>Paid</th>
                        <th>Date</th>
                        @foreach($student->studenthistory as $item)
                            <tr>
                                <th>{{$loop->index+1}}</th>
                                <th>{{$item->payment}}</th>
                                <th>@if($item->date ==null)
                                        {{$item->created_at.'data'}}
                                    @else
                                        {{$item->date}}
                                    @endif</th>
                            </tr>
                        @endforeach
                    </table>

                    <p>travel of group</p>
                    <table class="table">
                        <tr>
                            <th>No</th>
                            <th>group</th>
                            <th>Date</th>
                        </tr>
                        @foreach($student->studentinformation as $item)
                            <tr>
                                <th>{{$loop->index+1}}</th>
                                <th>{{$item->group->name}}</th>
                                <th>{{$item->created_at}}</th>
                            </tr>
                        @endforeach
                    </table>

                    <p>Attendance</p>

                    <table class="table">
                        <tr>
                            <th> Group</th>
                            <th> Teacher</th>
                            <th> Date</th>

                        </tr>
                        @foreach($attendances as $attendance)
                            <tr>
                                <th>{{$attendance->student->name}}</th>
                                <th>{{$attendance->group->name}}</th>
                                <th>{{$attendance->created_at}}</th>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div class="container__right" style="max-width: 300px; margin-top: 20px;">
                    <img src="{{asset( 'storage/'.$student->photo) }}"
                         style="width: 200px; display: block; margin-left: auto;"
                         alt="internet bilan muammo bor">
                </div>
            </div>
        </div>
    </div>
@endsection
