@extends('template.master')
@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <!-- First Table -->
            <div class="col-md-6 mt-2">
                <div class="card">
                    <h5 class="card-header">Top 5 Students</h5>
                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <thead>
                            <tr>
                                <td>Name</td>
                                <td>Group</td>
                                <td>Mark</td>
                            </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                            @foreach($topStudents as $topStudent)
                                <tr>
                                    <td>{{ $topStudent->student->name }}</td>
                                    <td>{{ $topStudent->group }}</td>
                                    <td>{{ $topStudent->new_get_mark }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Second Table -->
            <div class="col-md-6 mt-2">
                <div class="card">
                    <h5 class="card-header">Test List</h5>
                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <thead>
                            <tr>
                                <td>Name</td>
                                <td>Group</td>
                                <td>Data</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $item)
                                <tr class="clickable-row" data-href="{{ route('test.show', $item->id) }}">
                                    <td>{{$item->name}}</td>
                                    <td>{{$item->groupName->name}}</td>
                                    <td>{{$item->created_at->format('D-M-Y')}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="card-footer">
                            {{ $data->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const rows = document.querySelectorAll('.clickable-row');
            rows.forEach(function (row) {
                row.addEventListener('click', function () {
                    window.location.href = row.getAttribute('data-href');
                });
            });
        });
    </script>

@endsection






{{--@foreach($data as $item)--}}
{{--    <tr>--}}
{{--        <td>--}}
{{--            <b><a href="{{route('test.show',$item->id)}}"--}}
{{--                  class="mb-0 m-2 text-secondary">{{ $item->name }}</a></b>--}}
{{--        </td>--}}

{{--        <td>--}}
{{--            <h6>{{$item->groupName->name}}</h6>--}}
{{--        </td>--}}

{{--        <td>--}}
{{--            <div class="user-progress d-flex align-items-center gap-1">--}}
{{--                <h6 class="mb-0">{{ $item->created_at->format('d-m-y') }}</h6>--}}
{{--            </div>--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--@endforeach--}}

