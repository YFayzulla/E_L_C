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
                    <div class="btn-group">
                        <a class="btn buttons-collection dropdown-toggle btn-label-primary me-2" tabindex="0"
                           aria-controls="DataTables_Table_0" type="button" id="dropdownMenuButton"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span><i class="bx bx-export me-sm-1"></i> <span
                                    class="d-none d-sm-inline-block">Export</span></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="{{ URL::to('/student/pdf') }}"><i
                                        class="bx bxs-file-pdf me-1"></i> Pdf</a></li>
                        </ul>
                    </div>
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
                    <th>phone</th>
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
                        <th>+998 {{$student->phone}}</th>
                        <th>{{$student->parents_tel}}</th>
                        {{--                    <th>@if(Carbon::parse( $student->studentdept->date)->greaterThan(Carbon::parse(now()->format('Y-m-d')) )) <p style="color: #a52834" >{{ 'qarz' }}</p> @else <p style="color: #0f5132">{{ 't`olangan' }}</p> @endif </th>--}}
                        <th>{{ ($student->group->name == "waiters")?'Waiting room': $student->group->name }}</th>
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
                <tfoot>
{{--                <center>--}}
{{--                    {{ $students->links('pagination::bootstrap-5') }}--}}
{{--                --}}
{{--                </tfoot>--}}
            </table>
        </div>
    </div>
@endsection
