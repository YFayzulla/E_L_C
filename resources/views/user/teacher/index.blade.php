@extends('template.master')
@section('content')
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Teachers</h5>
            <div class="dt-action-buttons text-end pt-3 pt-md-0">
                <div class="dt-buttons btn-group flex-wrap">
                    <div class="btn-group">
                        <a class="btn buttons-collection dropdown-toggle btn-label-primary me-2" tabindex="0"
                           aria-controls="DataTables_Table_0" type="button" id="dropdownMenuButton"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span><i class="bx bx-export me-sm-1"></i> <span
                                    class="d-none d-sm-inline-block">Export</span></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="{{ URL::to('/teacher/pdf') }}"><i
                                        class="bx bxs-file-pdf me-1"></i> Pdf</a></li>
                        </ul>
                    </div>
                    <a href="{{ route('teacher.create') }}" class="btn btn-secondary create-new btn-primary"
                       tabindex="0"
                       aria-controls="DataTables_Table_0">
                        <span><i class="bx bx-plus me-sm-1"></i> <span
                                class="d-none d-sm-inline-block">Add New Teacher</span></span>
                    </a>
                </div>
            </div>
        </div>

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
                    <tbody id="myTable" class="table-group-divider table-border-bottom-0">
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
