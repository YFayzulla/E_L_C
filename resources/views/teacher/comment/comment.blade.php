<div class="modal fade" id="groupModal{{ $group->group_id }}" tabindex="-1" aria-labelledby="groupModalLabel{{ $group->group_id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupModalLabel{{ $group->group_id }}">{{ $group->group->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>commenting?</p>
            </div>
            <div class="modal-footer">
{{--                <a href="{{ route('teacher.comment', $group->group_id) }}" class="btn btn-primary">Go to Comments</a>--}}
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
