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
                <th>Ota-onasining telefon raqami</th>
                <th>Ota-onasining ismi</th>
                <th>image</th>
                <th class="">action</th>
            </tr>
            </thead>
            @foreach($teachers as $teacher)
                <tbody id="myTable" class="table-group-divider">
                <tr>
                    <th>{{$loop->index+1}}</th>
                    <th>{{$teacher->name}}</th>
                    <th>{{$teacher->phone}}</th>
                    <th>{{$teacher->parents_tel}}</th>
                    <th>{{$teacher->parents_name}}</th>
                    <th><img src="{{asset('storage/'.$teacher->photo)}}" alt="" width="40px"></th>
                    <th class="d-flex">
                        <a href="{{route('teacher.edit',$teacher->id)}}" class="btn-outline-warning btn m-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                 class="bi bi-pencil-fill" viewBox="0 0 16 16">
                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
                            </svg>
                        </a>
                        <form action="{{route('teacher.destroy',$teacher->id)}}" method="post"
                              onsubmit="return confirm('are you sure for deleting ');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="" class="btn-outline-danger btn m-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                     class="bi bi-trash-fill" viewBox="0 0 16 16">
                                    <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/>
                                </svg>
                            </button>
                        </form>
                    </th>
                </tr>
                </tbody>
            @endforeach
        </table>

    <script>
        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: '{{session('success')}}',
            showConfirmButton: false,
            timer: 1500
        })
        @endif


    </script>

@endsection
