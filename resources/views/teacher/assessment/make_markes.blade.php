@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <form action="{{route('assessment.update',$id)}}" method='post'>
            @csrf
            @method('PUT')
            <table class="table">
                <tr>
                    <td>no</td>
                    <td>name</td>
                    <td class="float">status</td>
                </tr>
                @csrf
                @foreach($students as $student)
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th><b>{{$student->student->name}}</b></th>
                        <th>
                            <input type="text" class="float input-group-merge" style="height: 30px;width: 50px"
                                   name="end_mark[{{$student->user_id}}]">
                        </th>
                    </tr>
                @endforeach
            </table>
            <button type="submit" class="btn btn-primary" >topshirish</button>
        </form>
    </div>
@endsection
