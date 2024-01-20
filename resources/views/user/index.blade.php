@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <div class="max-w-xl mx-auto">
            <button class="btn-outline-danger" style="float: right">PDF generate</button>
            <div class="container" style="display: flex; justify-content: space-between;">
                    <table class="table">
                        <tr>
                            <th>no</th>
                            <th>ismi</th>
                            <th>ttelefon raqam</th>
                            <th>tolangan pullar</th>
                            <th>sana</th>
                        </tr>
                    @foreach($users as $student)
                        <tr>
                            <th>{{$loop->index+1}}</th>
                            <th>{{$student->student->name}}</th>
                            <th>{{$student->student->phone}}</th>
                            <th>{{$student->payment}}</th>
                            <th>{{$student->date}}</th>
                        </tr>
                    @endforeach
                    </table>
            </div>
        </div>
    </div>
@endsection
