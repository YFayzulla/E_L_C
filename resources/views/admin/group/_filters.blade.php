@php
    /** Server-side filters for the groups index — GET only, values round-trip via the query string. */
    $q            = request('q');
    $teacherId    = request('teacher_id');
    $roomId       = request('room_id');
    $hasStudents  = request('has_students');
    $sort         = request('sort', 'name-asc');
    $isFiltered   = filled($q) || filled($teacherId) || filled($roomId) || filled($hasStudents)
                    || ($sort && $sort !== 'name-asc');

    $sortOptions = [
        'name-asc'             => 'Nomi (A → Z)',
        'name-desc'            => 'Nomi (Z → A)',
        'members_count-desc'   => 'Talabalar soni (ko‘pdan)',
        'members_count-asc'    => 'Talabalar soni (kamdan)',
        'monthly_payment-desc' => 'Oylik to‘lov (yuqoridan)',
        'monthly_payment-asc'  => 'Oylik to‘lov (pastdan)',
        'created_at-desc'      => 'Avval yangilari',
        'created_at-asc'       => 'Avval eskilari',
    ];
@endphp

<div class="filter-card">
    <form action="{{ route('group.index') }}" method="get" class="row g-3 align-items-end">

        <div class="col-md-4 col-lg-3">
            <label class="form-label" for="filter-q">Guruh nomi</label>
            <input type="text" id="filter-q" name="q" class="form-control"
                   value="{{ $q }}" placeholder="Masalan: Beginners A1">
        </div>

        <div class="col-md-4 col-lg-2">
            <label class="form-label" for="filter-teacher">O‘qituvchi</label>
            <select id="filter-teacher" name="teacher_id" class="form-select">
                <option value="">Barchasi</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((string) $teacherId === (string) $teacher->id)>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 col-lg-2">
            <label class="form-label" for="filter-room">Xona</label>
            <select id="filter-room" name="room_id" class="form-select">
                <option value="">Barchasi</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" @selected((string) $roomId === (string) $room->id)>
                        {{ $room->room }}-xona
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 col-lg-2">
            <label class="form-label" for="filter-has-students">Bandligi</label>
            <select id="filter-has-students" name="has_students" class="form-select">
                <option value="">Barchasi</option>
                <option value="1" @selected($hasStudents === '1')>Talabalari bor</option>
                <option value="0" @selected($hasStudents === '0')>Bo‘sh</option>
            </select>
        </div>

        <div class="col-md-6 col-lg-3">
            <label class="form-label" for="filter-sort">Saralash</label>
            <select id="filter-sort" name="sort" class="form-select">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-search me-1"></i> Qidirish
            </button>
            @if($isFiltered)
                <a href="{{ route('group.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-x me-1"></i> Tozalash
                </a>
            @endif
        </div>
    </form>
</div>
