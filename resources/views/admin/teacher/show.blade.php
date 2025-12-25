@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        {{-- modal --}}
        <div class="col-lg-4 col-md-6">
            <div class="mt-3">
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#basicModal">
                    Add group
                </button>

                <!-- Modal -->
                <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"
                                ></button>
                            </div>
                            <div class="modal-body">
                                <form action="{{route('teacher_group.store',$id)}}" method="post">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                    <label for="exampleFormControlSelect1" class="form-label">Groups</label>
                                    <select class="form-select" name="group_id" id="exampleFormControlSelect1">
                                        @forelse($groups as $group)
                                            <option value="{{$group->id}}">{{$group->name}}</option>
                                        @empty
                                            <option value="">No groups available</option>
                                        @endforelse
                                    </select>
                                    </div>
                                    <div class="modal-footer mt-3">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                            Close
                                        </button>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden select area initially -->
        <div class="table-responsive text-nowrap mt-2">
            <table class="table">
                <tr>
                    <th>No</th>
                    <th> Group</th>
                    <th> Delete</th>
                </tr>

                @forelse($teachers as $teacher)
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th>{{$teacher->group->name}}</th>
                        <td>
                            <form action="{{route('teacher_group.delete',$teacher->id)}}" method="post"
                                  onsubmit="return confirm('are you sure for deleting ');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="" class="btn-outline-danger btn ">
                                    <i class='bx bx-trash-alt'></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No groups assigned to this teacher.</td>
                    </tr>
                @endforelse

            </table>
        </div>
@endsection