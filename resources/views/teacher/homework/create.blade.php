@extends('template.master')

@section('title', 'Yangi uy vazifasi')
@section('subtitle', 'Guruhingiz uchun topshiriq yarating')

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('homework.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Ro‘yxatga qaytish
        </a>
    </div>

    <form action="{{ route('homework.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('teacher.homework._form', ['homework' => null])
    </form>

@endsection
