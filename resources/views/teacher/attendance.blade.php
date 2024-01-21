@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <div class="d-flex">
            <table class="table">
                <tr>
                    <td>no</td>
                    <td>name</td>
                    <td>status</td>
                </tr>
                <form action="" method='post'>
                @foreach($students as $student)
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th><b>{{$student->student->name}}</b></th>
                        <th>
                            <input type="checkbox" class="float-end" style="padding-left: 20px"
                                   name="status[{{$student->id}}]" value="on">
                        </th>
                    </tr>
                @endforeach
                    <button type="submit">topshirish</button>
                </form>
            </table>
        </div>
    </div>
@endsection
