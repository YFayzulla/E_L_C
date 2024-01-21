@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <form action="{{route('attendance.submit',$id)}}" method='post'>
            @csrf
            <table class="table">
                <tr>
                    <td>no</td>
                    <td>name</td>
                    <td>status</td>
                </tr>
                @csrf
                @foreach($students as $student)
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th><b>{{$student->student->name}}</b></th>
                        <th>
                            <input type="checkbox" class="float-end" style="padding-left: 20px"
                                   name="status[{{$student->user_id}}]" value="on">
                        </th>
                    </tr>
                @endforeach
            </table>
            <button type="submit" class="btn btn-primary" style="">topshirish</button>
        </form>
    </div>
@endsection
