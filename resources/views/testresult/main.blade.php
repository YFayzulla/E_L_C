@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg">

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">

                <!-- Top Students -->
                <div class="col-lg-8 order-0">
                    <div class="card">
                        <div class="d-flex align-items-end row">
                            <div class="col-sm-12 m-2">
                                <h5 class="card-title m-0 me-2 text-center"><b>Top 5 </b> students</h5>

                                <table class="table">
                                    <tr>
                                        <td>Name</td>
                                        <td>Group</td>
                                        <td>Mark</td>
                                    </tr>

                                    @foreach($topStudents as $topStudent)

                                        <tr>
                                            <td>
                                                {{ $topStudent->student->name }}
                                            </td>
                                            <td>
                                                {{ $topStudent->group }}
                                            </td>
                                            {{--                                            <td>--}}
                                            {{--                                                {{ $topStudent->who_checked }}--}}
                                            {{--                                            </td>--}}
                                            <td>
                                                {{ $topStudent->get_mark }}
                                            </td>
                                        </tr>

                                    @endforeach

                                </table>
                                <div class="card-body">
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- /Top Students -->

                <!-- Latest Tests -->

                <div class="col-md-6 col-lg-4 order-2 mb-4">
                    <div class="card d-flex flex-column">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title m-0 me-2">Latest Test</h5>
                        </div>
{{--                        @dd($data)--}}

                        @foreach($data as $item)
                            <ul class="p-0 m-0">
                                <li class="d-flex mb-4 pb-1">
                                    <div
                                        class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <b><a href="#"
                                                  class="mb-0 text-secondary text-">{{$item->name}}</a></b>
                                            <strong> - </strong>
                                        </div>
                                        <div class="user-progress d-flex align-items-center gap-1">
                                            <h6 class="mb-0">- {{$item->created_at->format('d-m-y')}}</h6>
                                        </div>
                                    </div>
                                </li>
                                {{--                                {{ $data->links('pagination::bootstrap-5') }}--}}
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- /Latest Tests -->
        </div>
    </div>
    </div>

@endsection
