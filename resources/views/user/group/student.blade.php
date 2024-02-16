`@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="table-responsive text-nowrap">

            <table class="table">
                <thead>
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>T/N</th>
                </tr>
                </thead>
                <tbody>
                @foreach($students as $student)
                    <th>{{$loop->index+1}}</th>
                    <th>{{$student->name}}</th>
                    <th>{{$student->phone}}</th>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
`
