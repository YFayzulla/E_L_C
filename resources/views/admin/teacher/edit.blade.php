@extends('template.master')

@section('title', $teacher->name)
@section('subtitle', 'O‘qituvchi ma’lumotlarini tahrirlash')

@section('content')

    <div class="page-head">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('teacher.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> O‘qituvchilar
            </a>
            <a href="{{ route('teacher.show', $teacher->id) }}" class="btn btn-outline-secondary">
                <i class="bx bx-id-card me-1"></i> Kartochka
            </a>
        </div>
        <div class="page-sub" dir="ltr">+{{ $teacher->phone }}</div>
    </div>

    <form action="{{ route('teacher.update', $teacher->id) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.teacher._form')
    </form>

@endsection
