@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <form action="{{route('attendance.submit', $id)}}" method="post">
            @csrf
            <div class="d-flex justify-content-end mb-4">
                <label for="lesson" class="mr-2 align-self-center">Lesson:</label>
                <input type="text" name="lesson" id="lesson" class="form-control w-25">
            </div>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>no</th>
                    <th>name</th>
                    <th class="text-center">status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($students as $student)
                    <tr>
                        <td>{{$loop->index + 1}}</td>
                        <td><b>{{$student->name}}</b></td>
                        <td class="text-center">
                            <input type="checkbox" name="status[{{$student->id}}]" value="on">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
{{--            <div class="d-flex justify-content-end mt-2">--}}
                <button type="submit" class="btn btn-primary">topshirish</button>
{{--            </div>--}}
        </form>
    </div>
@endsection
