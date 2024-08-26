<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $finance->id }}">
    <i class="bx bx-trash-alt"></i>
</button>

<div class="modal fade" id="deleteModal{{ $finance->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel{{ $finance->id }}">You can't take it back!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Do you want to delete the {{ $finance->reason }}?
            </div>
            <div class="modal-footer">
                <form action="{{ route('finance.destroy', $finance->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
            </div>
        </div>
    </div>
</div>
