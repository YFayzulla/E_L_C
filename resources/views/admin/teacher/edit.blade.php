@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-center mb-6">Edit Teacher's Data</h1>

            <form action="{{ route('teacher.update', $teacher->id) }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Essential Information --}}
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold border-b pb-2 mb-4">Essential Information</h2>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label text-dark">Name</label>
                        <input id="name" name="name" value="{{ old('name', $teacher->name) }}" type="text" class="form-control" required>
                        @error('name')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label text-dark">Phone</label>
                        <div class="input-group">
                            <span class="input-group-text">+998</span>
                            <input type="tel" id="phone" name="phone" pattern="[0-9]{9}" maxlength="9" class="form-control"
                                   placeholder="912345678" value="{{ old('phone', substr($teacher->phone, 3)) }}" required>
                        </div>
                        @error('phone')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="percent" class="form-label text-dark">Percent</label>
                        <input id="percent" name="percent" value="{{ old('percent', $teacher->percent) }}" type="number" class="form-control" required>
                        @error('percent')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="group_id" class="form-label text-dark">Groups</label>
                        <select id="group_id" name="group_id[]" class="form-select choices" multiple data-placeholder="Select groups">
                            <option value="">-- Select Groups --</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ $teacher->teacherGroups->contains($group->id) ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Additional Information --}}
                    <div class="md:col-span-2">
                        <h2 class="text-lg font-semibold border-b pb-2 mb-4 mt-6">Additional Information</h2>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label text-dark">New Password <span class="text-muted">(not necessary)</span></label>
                        <input id="password" name="password" type="password" class="form-control">
                        @error('password')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="date_born" class="form-label text-dark">Date of Birth <span class="text-muted">(not necessary)</span></label>
                        <input id="date_born" name="date_born" value="{{ old('date_born', $teacher->date_born) }}" type="date"
                               class="form-control">
                        @error('date_born')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label text-dark">Location <span class="text-muted">(not necessary)</span></label>
                        <input id="location" name="location" value="{{ old('location', $teacher->location) }}" type="text"
                               class="form-control">
                        @error('location')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="passport" class="form-label text-dark">Passport <span class="text-muted">(not necessary)</span></label>
                        <input id="passport" name="passport" value="{{ old('passport', $teacher->passport) }}" type="text"
                               class="form-control">
                        @error('passport')
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
                </div>

                <div class="mt-6 text-center">
                    <button type="submit" class="btn btn-warning btn-lg">Submit</button>
                </div>
            </form>
        </div>
    </div>

@endsection
