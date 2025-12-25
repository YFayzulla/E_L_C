<div class="d-flex justify-content-between">
    <div class="mt-3">
        <button class="btn btn-secondary create-new btn-primary" tabindex="0" data-bs-toggle="modal" data-bs-target="#basicModal"
                aria-controls="DataTables_Table_0" type="button">
            <span><i class="bx bx-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New Record</span></span>
        </button>

        <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1">Create Finance Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('finance.store') }}" onsubmit="formatBeforeSubmit()">
                            @csrf

                            <div class="row g-2">
                                <div class="col mb-3">
                                    <label for="reason" class="form-label">Reason</label>
                                    <input type="text" id="reason" class="form-control" name="reason" value="{{ old('reason') }}" required/>
                                    @error('reason')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col mb-3">
                                    <label for="payment" class="form-label">Payment</label>
                                    <input type="text" id="payment" class="form-control" name="payment" value="{{ old('payment') }}"  required oninput="formatNumber(this)"/>
                                    @error('payment')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select id="type" class="form-control" name="type" required>
                                        <option value="{{ \App\Models\Finance::CASH }}">Cash</option>
                                        <option value="{{ \App\Models\Finance::CARD }}">Card</option>
                                    </select>
                                    @error('type')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
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
    </div>
</div>

<script>
    function formatNumber(input) {
        let value = input.value.replace(/\s+/g, '');
        if (!isNaN(value)) {
            input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }
    }

    function formatBeforeSubmit() {
        let input = document.getElementById('payment');
        input.value = input.value.replace(/\s+/g, '');
    }
</script>