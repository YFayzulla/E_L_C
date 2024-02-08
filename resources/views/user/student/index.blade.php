@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">

        @php
            use Illuminate\Support\Carbon;
        @endphp

        <h1 class="text-center">Students</h1>

        <ul class="nav nav-pills flex-column flex-md-row mb-3">
            <li class="nav-item me-2 mt-2">
                <a class="btn btn-outline-success" href="{{route('student.create')}}">
                    <i class="bx bx-plus"></i>
                </a>
            </li>
            <li class="nav-item me-2 mt-2">
                <a class="btn btn-danger" href="{{ URL::to('/student/pdf') }}">
                    Report
                </a>
            </li>
        </ul>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>id</th>
                    <th>name</th>
                    <th>tel</th>
                    <th>Parents tel</th>
                    {{--                <th>oylik to`lov</th>--}}
                    <th>group</th>
                    <th class="">action</th>
                </tr>
                </thead>
                @foreach($students as $student)
                    <tbody id="myTable" class="table-group-divider">
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        {{--                    @dd($student->name)--}}
                        <th>{{$student->name}}</th>
                        <th>{{$student->phone}}</th>
                        <th>{{$student->parents_tel}}</th>
                        {{--                    <th>@if(Carbon::parse( $student->studentdept->date)->greaterThan(Carbon::parse(now()->format('Y-m-d')) )) <p style="color: #a52834" >{{ 'qarz' }}</p> @else <p style="color: #0f5132">{{ 't`olangan' }}</p> @endif </th>--}}
                        <th>{{$student->group->name}}</th>
                        <th class="d-flex">
                            <a href="{{route('student.edit',$student->id)}}" class="btn-outline-warning btn m-1">
                                <i class='bx bx-edit-alt'></i>
                            </a>
                            <a class="btn btn-outline-primary m-1" href="{{ route('student.show',$student->id) }}"><i
                                    class="bx bx-show-alt"></i></a>

                            <form action="{{route('student.destroy',$student->id)}}" method="post"
                                  onsubmit="return confirm('are you sure for deleting ');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="" class="btn-outline-danger btn m-1">
                                    <i class='bx bx-trash-alt'></i>

                                </button>
                            </form>
                        </th>
                    </tr>
                    </tbody>
                @endforeach
            </table>
        </div>
    </div>
@endsection
