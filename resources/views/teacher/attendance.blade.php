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
                @foreach($students as $student)
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th><b>{{$student->student->name}}</b></th>
                        <th>
                            <input type="checkbox" name="status">
                        </th>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
