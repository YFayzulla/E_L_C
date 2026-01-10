@extends('template.master')
@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Waiting Room</h5>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Parents' Phone</th>
                    <th>Current Status</th>
                    <th class="text-center">Action</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @forelse($students as $student)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $student->name }}</strong></td>
                        <td>{{ $student->phone }}</td>
                        <td>{{ $student->parents_tel }}</td>
                        <td>
                            {{-- Talaba guruhsiz bo'lgani uchun "Waiting" statusini ko'rsatamiz --}}
                            <span class="badge bg-label-warning">Waiting</span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
                                    data-bs-target="#assignGroupModal{{$student->id}}">
                                Assign to Group
                            </button>

                            {{-- Modal --}}
                            <div class="modal fade" id="assignGroupModal{{$student->id}}" tabindex="-1"
                                 aria-labelledby="assignGroupModalLabel{{$student->id}}"
                                 aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="assignGroupModalLabel{{$student->id}}">Assign {{ $student->name }} to a group</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{ route('student.change.group', $student->id) }}" method="post">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="group-select-{{$student->id}}" class="col-form-label">Select Group:</label>
                                                    <select name="group_id[]" id="group-select-{{$student->id}}" class="form-select choices" multiple data-placeholder="Select groups">
                                                        @forelse($groups as $group)
                                                            <option value="{{ $group->id }}">
                                                                {{ $group->name }} (Room: {{ $group->room_id ?? 'N/A' }})
                                                            </option>
                                                        @empty
                                                            <option value="" disabled>No groups available</option>
                                                        @endforelse
                                                    </select>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No students in the waiting room.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

    </div>

@endsection