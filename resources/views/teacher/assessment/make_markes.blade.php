@extends('template.master')
@section('content')

    <div class="card">
        <div class="table-responsive text-nowrap">
            <form action="{{route('assessment.update',$id)}}" method='post'>
                @csrf
                @method('PUT')
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>T/R</th>
                        <th>name</th>
                        <th>
                            mark
                        </th>
                        <th>reason</th>
                        <th>recommendation</th>
                    </tr>
                    </thead>
                    @foreach($students as $student)
                        @php($i=0)
                        <tbody class="table-border-bottom-0">
                        <input type="hidden" name="student" value="{{$student->id}}" >
                        <tr>
                            <td><i class="fab fa-angular fa-lg text-danger"></i>{{ $loop->index+1 }}</td>
                            <td><i class="fab fa-angular fa-lg text-danger "></i>{{ $student->student->name }}</td>
                            <th>
                                <input type="text" class="float input-group-merge    justify-content-center"
                                       style="height: 30px;width: 50px"
                                       name="end_mark[{{$i}}]">
                            </th>
                            <th>
                                <input type="text" class="float input-group-merge form-control "
                                       name="reason[{{$i}}]">
                            </th>
                            <th>

{{--                                <select class="float input-group-merge form-control" name="recommended[]">--}}

{{--                                    @foreach($groups as $group)--}}
{{--                                        <option value="{{ $group->name }}">{{ $group->name }}</option>--}}
{{--                                    @endforeach--}}

{{--                                </select>--}}
                            </th>
                        </tbody>
                    @endforeach
                </table>
                <button type="submit" class="btn btn-primary m-2 position-absolute">topshirish</button>
            </form>
        </div>
    </div>

@endsection

{{--    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">--}}
{{--        <form action="{{route('assessment.update',$id)}}" method='post'>--}}
{{--            @csrf--}}
{{--            @method('PUT')--}}
{{--            <table class="table">--}}
{{--                <tr>--}}
{{--                    <td>no</td>--}}
{{--                    <td>name</td>--}}
{{--                    <td class="float">status</td>--}}
{{--                </tr>--}}
{{--                @csrf--}}
{{--                @foreach($students as $student)--}}
{{--                    <tr>--}}
{{--                        <th>{{$loop->index+1}}</th>--}}
{{--                        <th><b>{{$student->student->name}}</b></th>--}}
{{--                        <th>--}}
{{--                            <input type="text" class="float input-group-merge" style="height: 30px;width: 50px"--}}
{{--                                   name="end_mark[{{$student->user_id}}]">--}}
{{--                        </th>--}}
{{--                    </tr>--}}
{{--                @endforeach--}}
{{--            </table>--}}
{{--            <button type="submit" class="btn btn-primary" >topshirish</button>--}}
{{--        </form>--}}
{{--    </div>--}}
{{--@endsection--}}
