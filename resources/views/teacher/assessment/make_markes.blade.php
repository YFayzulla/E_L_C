@extends('template.master')
@section('content')

    <div class="card">
        <div class="text-nowrap">
            <form action="{{route('assessment.update',$id)}}" method='post'>
                @csrf
                @method('PUT')

                <div class="row mb-4">
                    <!-- Left-aligned label and input -->
                    <div class="col-md-6 d-flex align-items-center">
                        <label for="lesson" class="mr-2"></label>
                        <input type="text" name="lesson" id="lesson" class="form-control w-50 m-3" placeholder="Test Name">
                    </div>
                </div>

                <table class="table table-responsive">
                    <thead>
                    <tr>
                        <th>T/R</th>
                        <th>Name</th>
                        <th>Mark</th>
                        <th>Reason</th>
                        <th>Recommendation</th>
                    </tr>
                    </thead>
                    @php($i=0)
                    @foreach($students as $student)
                        <input type="hidden" name="student[]" value="{{($student->id)}}">
                        <tbody class="table-border-bottom-0">
                        <tr>
                            <td>{{ $loop->index+1 }}</td>
                            <td>{{ $student->name }}</td>
                            <td>
                                <input type="number" class="float input-group-merge justify-content-center"
                                       style="height: 30px;width: 50px"
                                       name="end_mark[]">
                            </td>
                            <td>
                                <input type="text" class="float input-group-merge form-control"
                                       name="reason[]">
                            </td>
                            <td>
                                <select class="form-select form-control" name="recommended[]">
                                    @foreach($groups as $group)
                                        <option value="{{ $group->name }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        </tbody>
                        @php($i++)
                    @endforeach
                </table>

                <!-- Right-aligned submit button at the bottom -->
                <div class="d-flex justify-content-end mb-4">
                    <button type="submit" class="btn btn-primary m-3">Submit</button>
                </div>

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
