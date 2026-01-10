@extends('template.master')
@section('content')

<div class="container mt-4">
    <div class="accordion" id="groupsAccordion">
        @forelse($groups as $groupTeacher)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $groupTeacher->group->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $groupTeacher->group->id }}" aria-expanded="false" aria-controls="collapse{{ $groupTeacher->group->id }}">
                        <b>{{ $groupTeacher->group->name }}</b>
                        <span class="badge bg-primary rounded-pill ms-2">{{ $groupTeacher->group->students->count() }} students</span>
                    </button>
                </h2>
                <div id="collapse{{ $groupTeacher->group->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $groupTeacher->group->id }}" data-bs-parent="#groupsAccordion">
                    <div class="accordion-body">
                        @if($groupTeacher->group->students->isNotEmpty())
                            <ul class="list-group">
                                @foreach($groupTeacher->group->students as $student)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $student->name }}
                                        <div>
                                            <a href="{{ route('student.show', $student->id) }}" class="btn btn-sm btn-outline-primary me-2">
                                                <i class="bx bx-show"></i> View
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#commentModal{{ $student->id }}">
                                                <i class="bx bx-message-detail"></i> Comment
                                            </button>
                                        </div>
                                    </li>

                                    <!-- Comment Modal -->
                                    <div class="modal fade" id="commentModal{{ $student->id }}" tabindex="-1" aria-labelledby="commentModalLabel{{ $student->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('teacher.student.comment', $student->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="group_name" value="{{ $groupTeacher->group->name }}">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="commentModalLabel{{ $student->id }}">Comment for {{ $student->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="comment{{ $student->id }}" class="form-label">Comment</label>
                                                            <textarea class="form-control" id="comment{{ $student->id }}" name="comment" rows="3" required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save Comment</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </ul>
                        @else
                            <p>This group has no students.</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">You are not assigned to any groups.</div>
        @endforelse
    </div>
</div>

@endsection
