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
                                    <li class="list-group-item">{{ $student->name }}</li>
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
