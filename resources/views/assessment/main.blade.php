@extends('template.master')
@section('content')
{{--    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">--}}
{{--        <div class="row">--}}
{{--            <div class="card">--}}
{{--                <h5 class="card-header">Active Students</h5>--}}
{{--                <div class="table-responsive text-nowrap">--}}
{{--                    <table class="table">--}}
{{--                        <thead>--}}
{{--                        <tr>--}}
{{--                            <td>Name</td>--}}
{{--                            <td>Group</td>--}}
{{--                            <td>Mark</td>--}}
{{--                        </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody class="table-border-bottom-0">--}}
{{--                        @foreach($topStudents as $topStudent)--}}
{{--                            <tr>--}}
{{--                                <td>{{ $topStudent->student->name }}</td>--}}
{{--                                <td>{{ $topStudent->group }}</td>--}}
{{--                                <td>{{ $topStudent->get_mark }}</td>--}}
{{--                            </tr>--}}
{{--                        @endforeach--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--    </div>--}}


    <!-- Second Table -->

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <div class="row">
            <div class="card">
                <h5 class="card-header">Test List</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <td>Test Name</td>
                            <td>Data</td>
                            <td>show</td>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">

                        @foreach($data as $item)
                            <tr>
                                <td>{{$item->name}}</td>
                                <td>{{$item->created_at->format('d-m-Y')}}</td>
                                <td>
                                    <a href="{{route('test.show', $item->id)}}" class="btn btn-info"><i
                                                class="bx bx-show-alt"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection