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
                        <input id="name" name="name" type="text" value="{{ old('name', $student->name) }}" class="form-control" required>
                        @error('name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label text-dark">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+998</span>
                            <input type="tel" id="phone" name="phone" maxlength="9" placeholder="912345678"
                                   value="{{ old('phone', substr($student->phone, -9)) }}" class="form-control" required>
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
                                        data-payment="{{ $group->monthly_payment }}" {{ $student->groups->contains($group->id) ? 'selected' : '' }}>
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
                               value="{{ number_format(old('should_pay', $student->should_pay), 0, ' ', ' ') }}"
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
                                   value="{{ old('parents_tel', substr($student->parents_tel, -9)) }}" class="form-control">
                        </div>
                        @error('parents_tel')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="parents_name" class="form-label text-dark">Parents Name <span class="text-muted">(not necessary)</span></label>
                        <input id="parents_name" name="parents_name" type="text" value="{{ old('parents_name', $student->parents_name) }}"
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
                        <input id="passport" name="passport" type="text" value="{{ old('passport', $student->passport) }}" class="form-control">
                        @error('passport')
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
                        <input id="location" name="location" type="text" value="{{ old('location', $student->location) }}" class="form-control">
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
            const shouldPayInput = document.getElementById('should_pay');

            function formatNumberWithSpaces(value) {
                if (!value) return '';
                return value.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }

            shouldPayInput.addEventListener('input', function () {
                const rawValue = this.value.replace(/\s/g, '');
                this.value = formatNumberWithSpaces(rawValue);
            });

            shouldPayInput.form.addEventListener('submit', function () {
                shouldPayInput.value = shouldPayInput.value.replace(/\s/g, '');
            });
        });
    </script>

@endsection
