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
                    @php($i=0)
                    @foreach($students as $student)
                        <input type="hidden" name="student[]" value="{{($student->id)}}">
                        <tbody class="table-border-bottom-0">
                        <tr>
                            <td><i class="fab fa-angular fa-lg text-danger"></i>{{ $loop->index+1 }}</td>
                            <td><i class="fab fa-angular fa-lg text-danger "></i>{{ $student->name }}</td>
                            <th>
                                <input type="text" class="float input-group-merge    justify-content-center"
                                       style="height: 30px;width: 50px"
                                       name="end_mark[]">
                            </th>
                            <th>
                                <input type="text" class="float input-group-merge form-control "
                                       name="reason[]">
                            </th>
                            <th>
                                <select class="form-select form-control" name="recommended[]">

                                    @foreach($groups as $group)
                                        <option value="{{ $group->name }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </th>
                        </tbody>
                        @php($i++)

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
