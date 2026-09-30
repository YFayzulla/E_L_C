@extends('template.master')

@section('title', 'Yangi xodim')
@section('subtitle', 'Xodim hisobini yaratish va kerak bo‘lsa guruhlarni biriktirish')

@section('content')

    <div class="page-head">
        <a href="{{ route('teacher.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Xodimlar
        </a>
        <div class="page-sub">Hisob yaratilgach, xodim telefon raqami va parol bilan kiradi</div>
    </div>

    <form action="{{ route('teacher.store') }}" method="post" enctype="multipart/form-data">
        @csrf
        @include('admin.teacher._form', ['teacher' => null])
    </form>

@endsection
