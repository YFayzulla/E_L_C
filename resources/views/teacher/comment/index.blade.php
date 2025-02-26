@extends('template.master')

@section('content')

    <div class="d-flex justify-content-center">
        <table class="table table-sm w-75">
            @foreach($groups as $group)
                <tr>
                    <th>
                        <!-- Button to trigger modal -->
                        <button class="btn btn-outline-primary w-100 text-left" data-bs-toggle="modal"
                                data-bs-target="#groupModal{{ $group->group_id }}">
                            <b>{{ $group->group->name }}</b>
                        </button>
                    </th>
                </tr>

                <!-- Modal for each group -->
                <div class="modal fade" id="groupModal{{ $group->group_id }}" tabindex="-1"
                     aria-labelledby="groupModalLabel{{ $group->group_id }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"
                                    id="groupModalLabel{{ $group->group_id }}">{{ $group->group->name }} </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @if($group->group->students->isNotEmpty())

                                    <form action="{{route('comment.store')}}" method="post">
                                        @csrf
                                        <label for=""> student </label>
                                        <select name="student_id" id="" class="form-control m-2">
                                            @foreach($group->group->students as $student)
                                                <option value="{{$student->id}}">{{ $student->name }}</option>
                                            @endforeach
                                        </select>
                                        @else
                                            <p class="text-muted">No students found in this group.</p>
                                        @endif
                                        <label for=""> comment </label>
                                        <input type="text" class="form-control m-2" name="comment" required>
                                        <button class="btn btn-primary" type="submit">Go to
                                            Comments
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                        </button>
                                    </form>
                            </div>
                            <div class="modal-footer">
                            </div>
                        </div>
                    </div>
                </div>

            @endforeach
        </table>
    </div>

@endsection
