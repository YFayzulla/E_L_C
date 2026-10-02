@extends('template.master')

@section('title', $student->name)
@section('subtitle', 'Reception yozuvini tahrirlash')

@section('content')
    <div class="page-head justify-content-end">
        <a href="{{ route('reception.students.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Ro‘yxatga qaytish
        </a>
    </div>

    <form action="{{ route('reception.students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('reception.students._form')
    </form>
@endsection
