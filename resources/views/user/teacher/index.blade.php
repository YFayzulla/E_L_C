@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <h1 class="text-center">O`qituvchilar</h1>

        <a href="{{route('teacher.create')}}" type="button" class="btn-outline-success btn m-2">
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
                <th>Ismi</th>
                <th>Telefon raqami</th>
                <th>Turar joyi</th>
                <th>tugulgan sanas</th>
                <th>rasimi</th>
                <th class="">action</th>
            </tr>
            </thead>
            @foreach($teachers as $teacher)
                <tbody id="myTable" class="table-group-divider">
                <tr>
                    <th>{{$loop->index+1}}</th>
                    <th>{{$teacher->name}}</th>
                    <th>{{$teacher->phone}}</th>
                    <th>{{$teacher->location}}</th>
                    <th>{{$teacher->date_born}}</th>
                    <th><img src="{{asset("storage/".$teacher->photo)}}"  width="40px" alt="??"></th>
                    <th class="d-flex">
                        <a href="{{route('teacher.show',$teacher->id)}}" class="btn-outline-warning btn m-2">
                            <i class='bx bx-show' ></i>
                        </a>

                        <a href="{{route('teacher.edit',$teacher->id)}}" class="btn-outline-warning btn m-2">
                            <i class='bx bx-edit-alt' ></i>
                        </a>

                        <form action="{{route('teacher.destroy',$teacher->id)}}" method="post"
                              onsubmit="return confirm('are you sure for deleting ');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="" class="btn-outline-danger btn m-2">
                                <i class='bx bx-trash-alt' ></i>
                            </button>
                        </form>
                    </th>
                </tr>
                </tbody>
            @endforeach
        </table>

    <script>
@endsection
