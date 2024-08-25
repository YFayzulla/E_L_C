@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">

        <h1 class="text-center">Teachers</h1>

        <ul class="nav nav-pills flex-column flex-md-row mb-3">
            <li class="nav-item me-2 mt-2">
                <a class="btn btn-outline-success" href="{{ route('teacher.create') }}">
                    <i class="bx bx-plus"></i>
                </a>
            </li>
            <li class="nav-item me-2 mt-2">
                <a class="btn btn-danger" href="{{ URL::to('/teacher/pdf') }}">
                    Report
                </a>
            </li>
        </ul>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Date born</th>
                    <th>photo</th>
                    <th class="">action</th>
                </tr>
                </thead>
                @foreach($teachers as $teacher)
                    <tbody id="myTable" class="table-group-divider">
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th>{{$teacher->name}}</th>
                        <th>+{{$teacher->phone}}</th>
                        <th>{{$teacher->location}}</th>
                        <th>{{$teacher->date_born}}</th>
                        <td>
                            <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                                <li
                                    data-bs-toggle="modal"
                                    data-bs-target="#imageModal{{$teacher->id}}"
                                    data-bs-placement="top"
                                    class="avatar avatar-md pull-up"
                                >
                                    <img src="{{ asset('storage/'.$teacher->photo) }}" class="rounded-circle" alt="??"/>
                                </li>
                            </ul>

                        </td>
                        <th class="d-flex">

                            <a href="{{route('teacher.edit',$teacher->id)}}" class="btn-outline-warning btn m-1">
                                <i class='bx bx-edit-alt'></i>
                            </a>
                            <a href="{{route('teacher.show',$teacher->id)}}" class="btn-outline-primary btn m-1">
                                <i class='bx bx-show'></i>
                            </a>

                            <form action="{{route('teacher.destroy',$teacher->id)}}" method="post"
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
                    <div class="modal fade" id="imageModal{{$teacher->id}}" tabindex="-1" role="dialog"
                         aria-labelledby="imageModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Закрыть"></button>
                                </div>
                                <div class="modal-body">
                                    <img src="{{ asset('storage/'.$teacher->photo) }}" class="img-fluid"
                                         alt="Modal Image">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </table>
        </div>
@endsection
