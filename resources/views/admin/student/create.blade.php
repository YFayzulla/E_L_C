@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-center mb-6">Create New Student</h1>

            <form action="{{ route('student.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Essential Information --}}
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold border-b pb-2 mb-4">Essential Information</h2>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label text-dark">Full Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" class="form-control" required>
                        @error('name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label text-dark">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+998</span>
                            <input type="tel" id="phone" name="phone" maxlength="9" placeholder="912345678"
                                   value="{{ old('phone') }}" class="form-control" required>
                        </div>
                        @error('phone')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="group_id" class="form-label text-dark">Group</label>
                        <select id="group_id" name="group_id[]" class="form-select choices" multiple required data-placeholder="Select groups">
                            <option value="">-- Select Groups --</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}"
                                        data-payment="{{ $group->monthly_payment }}" {{ (is_array(old('group_id')) && in_array($group->id, old('group_id'))) ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="should_pay" class="form-label text-dark">Monthly Payment</label>
                        <input id="should_pay" name="should_pay" type="text"
                               value="{{ number_format(old('should_pay', 500000), 0, ' ', ' ') }}"
                               class="form-control" required>
                        @error('should_pay')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Additional Information --}}
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold border-b pb-2 mb-4 mt-6">Additional Information</h2>
                    </div>

                    <div class="mb-3">
                        <label for="parents_tel" class="form-label text-dark">Parents Phone <span class="text-muted">(not necessary)</span></label>
                        <div class="input-group">
                            <span class="input-group-text">+998</span>
                            <input type="tel" id="parents_tel" name="parents_tel" maxlength="9" placeholder="912345678"
                                   value="{{ old('parents_tel') }}" class="form-control">
                        </div>
                        @error('parents_tel')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="parents_name" class="form-label text-dark">Parents Name <span class="text-muted">(not necessary)</span></label>
                        <input id="parents_name" name="parents_name" type="text" value="{{ old('parents_name') }}"
                               class="form-control">
                        @error('parents_name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="photo" class="form-label text-dark">Photo <span class="text-muted">(not necessary)</span></label>
                        <input id="photo" name="photo" type="file" class="form-control">
                        @error('photo')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="passport" class="form-label text-dark">Passport <span class="text-muted">(not necessary)</span></label>
                        <input id="passport" name="passport" type="text" value="{{ old('passport') }}" class="form-control">
                        @error('passport')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 md:col-span-2">
                        <label for="location" class="form-label text-dark">Location <span class="text-muted">(not necessary)</span></label>
                        <input id="location" name="location" type="text" value="{{ old('location') }}" class="form-control">
                        @error('location')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 md:col-span-2">
                        <label for="description" class="form-label text-dark">Description <span class="text-muted">(not necessary)</span></label>
                        <textarea id="description" name="description"
                                  class="form-control">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <button type="submit" class="btn btn-warning btn-lg">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const groupSelect = document.getElementById('group_id');
            const shouldPayInput = document.getElementById('should_pay');

            // Function to format number with spaces
            function formatNumberWithSpaces(value) {
                if (!value) return '';
                return value.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }

            // Apply formatting when the user types
            shouldPayInput.addEventListener('input', function () {
                const rawValue = this.value.replace(/\s/g, ''); // Remove spaces
                this.value = formatNumberWithSpaces(rawValue);
            });

            // Remove spaces before form submission
            shouldPayInput.form.addEventListener('submit', function () {
                shouldPayInput.value = shouldPayInput.value.replace(/\s/g, '');
            });
            
            // Note: Automatic payment update based on selection is tricky with multi-select.
            // We'll leave it manual or default for now, or you can implement logic to sum payments or pick the highest.
            // For simplicity and to avoid confusion with multiple groups, we won't auto-update payment here
            // unless you specifically want to sum them up.
        });
    </script>

@endsection
