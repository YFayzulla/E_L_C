@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">

        @php
            use Illuminate\Support\Carbon;
        @endphp

        <h1 class="text-center">Students</h1>

        <a href="{{route('student.create')}}" type="button" class="btn-outline-success btn m-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="currentColor"
                 class="bi bi-plus-lg" viewBox="0 0 16 16">
                <path fill-rule="evenodd"
                      d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z"/>
            </svg>
        </a>

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
    {{--        {{ $students->links() }}--}}
    </div>
@endsection
