@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-center mb-6">Edit Student Data</h1>

            <form action="{{ route('student.update', $student->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Essential Information --}}
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold border-b pb-2 mb-4">Essential Information</h2>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label text-dark">Full Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $student->name) }}"
                               class="form-control" required>
                        @error('name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label text-dark">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+998</span>
                            <input type="tel" id="phone" name="phone" maxlength="9" placeholder="912345678"
                                   value="{{ old('phone', substr($student->phone, -9)) }}" class="form-control"
                                   required>
                        </div>
                        @error('phone')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="group_id" class="form-label text-dark">Group</label>
                        <select id="group_id" name="group_id[]" class="form-select choices" multiple required
                                data-placeholder="Select groups">
                            <option value="">-- Select Groups --</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}"
                                        data-payment="{{ $group->monthly_payment }}" {{ $student->groups->contains($group->id) ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="group_payments_container" class="mb-3 md:col-span-2" style="display:block">
                        {{-- Per-group payment inputs will be injected here by JS --}}
                    </div>

                    {{-- should_pay removed: per-group payments are used instead --}}

                    {{-- Additional Information --}}
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold border-b pb-2 mb-4 mt-6">Additional Information</h2>
                    </div>


                    <div class="mb-3">
                        <label for="parents_name" class="form-label text-dark">Parents Name <span class="text-muted">(not necessary)</span></label>
                        <input id="parents_name" name="parents_name" type="text"
                               value="{{ old('parents_name', $student->parents_name) }}"
                               class="form-control">
                        @error('parents_name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="parents_tel" class="form-label text-dark">Parents Phone <span class="text-muted">(not necessary)</span></label>
                        <div class="input-group">
                            <input type="text" id="parents_tel" name="parents_tel"
                                   value="{{ old('parents_tel', $student->parents_tel,) }}"
                                   class="form-control">
                        </div>
                        @error('parents_tel')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="photo" class="form-label text-dark">Photo <span
                                    class="text-muted">(not necessary)</span></label>
                        <input id="photo" name="photo" type="file" class="form-control">
                        @error('photo')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="birth_date" class="form-label text-dark">Birth Date <span class="text-muted">(not necessary)</span></label>
                        <input id="birth_date" name="birth_date" type="date"
                               value="{{ old('birth_date', $student->date_born) }}" class="form-control">
                        @error('birth_date')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label text-dark">New Password <span class="text-muted">(not necessary)</span></label>
                        <input id="password" name="password" type="password" class="form-control">
                        @error('password')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 md:col-span-2">
                        <label for="location" class="form-label text-dark">Location <span class="text-muted">(not necessary)</span></label>
                        <input id="location" name="location" type="text"
                               value="{{ old('location', $student->location) }}" class="form-control">
                        @error('location')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 md:col-span-2">
                        <label for="description" class="form-label text-dark">Description <span class="text-muted">(not necessary)</span></label>
                        <textarea id="description" name="description"
                                  class="form-control">{{ old('description', $student->description) }}</textarea>
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
            const paymentsContainer = document.getElementById('group_payments_container');

            function formatNumberWithSpaces(value) {
                if (!value) return '';
                return value.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }

            // Strip spaces from group_payment inputs before submit
            const formEl = document.querySelector('form[action="{{ route('student.update', $student->id) }}"]');
            if (formEl) {
                formEl.addEventListener('submit', function () {
                    const gpInputs = paymentsContainer.querySelectorAll('input[name^="group_payment"]');
                    gpInputs.forEach(i => i.value = i.value.replace(/\s/g, ''));
                });
            }

            // Build per-group payment inputs when groups are selected.
            function renderGroupPayments() {
                const selected = Array.from(groupSelect.selectedOptions).filter(o => o.value);
                paymentsContainer.innerHTML = '';

                selected.forEach(opt => {
                    const gid = opt.value;
                    const name = opt.textContent.trim();
                    const defaultPayment = opt.dataset.payment || '';
                    const inputName = `group_payment[${gid}]`;

                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-2';

                    const label = document.createElement('label');
                    label.className = 'form-label text-dark';
                    label.textContent = `${name} payment`;

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.name = inputName;
                    // Prefill from old input, or pivot payment if available, or group's default
                    const existingPayments = {!! json_encode(old('group_payment', $student->groups->pluck('pivot.payment', 'id')->toArray())) !!};
                    input.value = (existingPayments && existingPayments[gid]) ? formatNumberWithSpaces(existingPayments[gid]) : formatNumberWithSpaces(defaultPayment);
                    input.className = 'form-control group-payment-input';

                    wrapper.appendChild(label);
                    wrapper.appendChild(input);
                    paymentsContainer.appendChild(wrapper);

                    input.addEventListener('input', function () {
                        const rawVal = this.value.replace(/\s/g, '');
                        this.value = formatNumberWithSpaces(rawVal);
                    });
                });
            }

            // Initial render
            renderGroupPayments();
            groupSelect.addEventListener('change', renderGroupPayments);
        });
    </script>

@endsection
