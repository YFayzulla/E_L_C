@extends('template.master')
@section('content')

    <div class="card">

        @php
            use Illuminate\Support\Carbon;
        @endphp

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Students</h5>
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
                            {{-- XATO TUZATILDI: URL to'g'irlandi --}}
                            <li><a class="dropdown-item" href="{{ URL::to('/student/pdf-list') }}"><i
                                            class="bx bxs-file-pdf me-1"></i> Pdf</a></li>
                        </ul>
                    </div>
                    @endrole
                    <a href="{{route('student.create')}}" class="btn btn-secondary create-new btn-primary" tabindex="0"
                       aria-controls="DataTables_Table_0">
                        <span><i class="bx bx-plus me-sm-1"></i> <span
                                    class="d-none d-sm-inline-block">Add New Student</span></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>id</th>
                    <th>name</th>
                    <th>tel</th>
                    <th>Parents tel</th>
                    <th>group</th>
                    <th>payment</th>
                    <th class="text-center">action</th>
                </tr>
                </thead>
                <tbody id="myTable" class="table-border-bottom-0">
                @forelse($students as $student)
                    <tr>
                        <th>{{$loop->iteration + ($students->currentPage() - 1) * $students->perPage()}}</th>
                        <th>{{$student->name}}</th>
                        <th>+998 {{$student->phone}}</th>
                        <th>{{$student->parents_tel}}</th>
                        <th>{{$student->studentsGroup()}}</th>
                        <th>{{$student->should_pay}}</th>
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
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No students found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end m-3">
            {{ $students->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection