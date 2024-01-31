@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">

        @php
            use Illuminate\Support\Carbon;
        @endphp

        <h1 class="text-center">O`quvchilar</h1>

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
                <th>Ismi</th>
                <th>Telefon</th>
                <th>Ota-onasining telefon raqami</th>
                {{--                <th>oylik to`lov</th>--}}
                <th>guruh</th>
                {{--                <th class="">action</th>--}}
            </tr>
            </thead>
            @foreach($students as $student)
                <tbody id="myTable" class="table-group-divider">
                <tr>
                    <th>{{$loop->index+1}}</th>
                    <th>{{$student->name}}</th>
                    <th>{{$student->phone}}</th>
                    <th>{{$student->parents_tel}}</th>
                    <th>{{$student->group->name}}</th>
                    <th>
                        <button type="button" class="btn-outline-success btn m-2" data-bs-toggle="modal"
                                data-bs-target="#exampleModal{{$student->id}}" data-bs-whatever="@mdo">xulosa</button>

                        <div class="modal fade" id="exampleModal{{$student->id}}" tabindex="-1"
                             aria-labelledby="exampleModalLabel"
                             aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                    </div>
                                    <div class="modal-body">
                                        <form action="{{route('student.change.group',$student->id)}}" method="post">
                                            @csrf
                                            <label for="recipient-name"
                                                   class="col-form-label"> boshqa guruhga o`tirish </label>
                                            <select name="group" class="form-control">
                                                @foreach($groups as $group)
                                                    <option value="{{$group->id}}">{{$group->name}}</option>
                                                @endforeach
                                            </select>

                                            <button type="submit" class="btn btn-outline-primary m-2">save
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </th>
                </tr>
                </tbody>
            @endforeach
        </table>
        {{--        {{ $students->links() }}--}}
        <script>
            @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: '{{session('success')}}',
                showConfirmButton: false,
                timer: 1500
            })
            @endif
        </script
@endsection
