@extends('template.master')
@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Teachers</h5>
            <div class="dt-action-buttons text-end pt-3 pt-md-0">
                <div class="dt-buttons btn-group flex-wrap">
                    @role('admin')
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
                    @endrole
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
                    <th>Groups</th>
                    <th>action</th>
                </tr>
                </thead>
                <tbody id="myTable" class="table-border-bottom-0">
                @foreach($teachers as $teacher)
                    <tr>
                        <td>{{$loop->index+1}}</td>
                        <td>{{$teacher->name}}</td>
                        <td>+{{$teacher->phone}}</td>
                        <td>
                            @foreach($teacher->teacherGroups as $group)
                                <span class="badge bg-label-primary me-1">{{ $group->name }}</span>
                            @endforeach
                        </td>
                        <td class="d-flex">

                            <a href="{{route('teacher.edit',$teacher->id)}}" class="btn-outline-warning btn m-1">
                                <i class='bx bx-edit-alt'></i>
                            </a>
                            <form action="{{route('teacher.destroy',$teacher->id)}}" method="post"
                                  onsubmit="return confirm('are you sure for deleting ');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="" class="btn-outline-danger btn m-1">
                                    <i class='bx bx-trash-alt'></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
     
    </div>

@endsection
