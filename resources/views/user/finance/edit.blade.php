<button type="button" class="btn btn-outline-warning me-2" data-bs-toggle="modal" data-bs-target="#editModal{{ $finance->id }}">
    <i class="bx bx-edit-alt"></i>
</button>

<div class="modal fade" id="editModal{{ $finance->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel1">Edit Finance Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('finance.update', $finance->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-2">
                        <div class="col mb-3">
                            <label for="reason{{ $finance->id }}" class="form-label">Reason</label>
                            <input type="text" id="reason{{ $finance->id }}" class="form-control" name="reason" value="{{ $finance->reason }}" required />
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col mb-3">
                            <label for="payment{{ $finance->id }}" class="form-label">Payment</label>
                            <input type="number" id="payment{{ $finance->id }}" class="form-control" name="payment" value="{{ $finance->payment }}" required />
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col mb-3">
                            <label for="type{{ $finance->id }}" class="form-label">Type</label>
                            <select id="type{{ $finance->id }}" class="form-control" name="type" required>
                                <option value="{{ \App\Models\Finance::CASH }}" {{ $finance->type == \App\Models\Finance::CASH ? 'selected' : '' }}>Cash</option>
                                <option value="{{ \App\Models\Finance::CARD }}" {{ $finance->type == \App\Models\Finance::CARD ? 'selected' : '' }}>Card</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
