@extends('template.master')

@section('title', 'Yangi o‘qituvchi')
@section('subtitle', 'O‘qituvchi hisobini yaratish va guruhlarni biriktirish')

@section('content')

    <div class="page-head">
        <a href="{{ route('teacher.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> O‘qituvchilar
        </a>
        <div class="page-sub">Hisob yaratilgach, o‘qituvchi telefon raqami va parol bilan kiradi</div>
    </div>

    <form action="{{ route('teacher.store') }}" method="post" enctype="multipart/form-data">
        @csrf
        @include('admin.teacher._form', ['teacher' => null])
    </form>

@endsection
