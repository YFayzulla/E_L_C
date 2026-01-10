@extends('template.master')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0">Groups</h5>
                <div class="dt-action-buttons text-end pt-3 pt-md-0">
                    <div class="dt-buttons btn-group flex-wrap">
                        @role('admin')
                        <div class="btn-group">
                            <button class="btn buttons-collection dropdown-toggle btn-label-primary me-2" tabindex="0"
                                    aria-controls="DataTables_Table_0" type="button" id="dropdownMenuButton"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <span><i class="bx bx-export me-sm-1"></i> <span
                                            class="d-none d-sm-inline-block">Export</span></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="{{ URL::to('/group/pdf') }}"><i
                                                class="bx bxs-file-pdf me-1"></i> Pdf</a></li>
                            </ul>
                        </div>
                        @endrole
                        <a href="{{ route('group.create') }}" class="btn btn-secondary create-new btn-primary"
                           tabindex="0"
                           aria-controls="DataTables_Table_0">
                            <span><i class="bx bx-plus me-sm-1"></i> <span
                                        class="d-none d-sm-inline-block">Add New Group</span></span>
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
                        <th>start time</th>
                        <th>finish time</th>
                        <th>cost</th>
                        <th class="text-center">action</th>
                    </tr>
                    </thead>
                    <tbody id="myTable" class="table-border-bottom-0">
                    @forelse($groups as $group)
                        <tr>
                            <th>{{ $loop->iteration }}</th>
                            <th>{{ $group->name }}</th>
                            <th>{{ $group->start_time }}</th>
                            <th>{{ $group->finish_time }}</th>
                            <th>{{ number_format($group->monthly_payment, 0, '.', ' ') }}</th>
                            <th class="d-flex justify-content-center text-center">
                                <a href="{{ route('group.students', $group->id) }}" class="btn btn-outline-primary m-1">
                                    <i class="bx bx-show-alt"></i>
                                </a>
                                <a href="{{ route('group.attendance', $group->id) }}" class="btn btn-outline-info m-1">
                                    <i class="bx bx-check-square"></i>
                                </a>
                                <a href="{{ route('group.edit', $group->id) }}" class="btn-outline-warning btn m-1">
                                    <i class='bx bx-edit-alt'></i>
                                </a>
                                <form action="{{ route('group.destroy', $group->id) }}" method="post"
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
                            <td colspan="6" class="text-center">No groups found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            
         
        </div>
    </div>
@endsection
