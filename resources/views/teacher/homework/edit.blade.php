@extends('template.master')

@section('title', 'Vazifani tahrirlash')
@section('subtitle', $homework->title)

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('homework.show', $homework->id) }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Vazifaga qaytish
        </a>
    </div>

    <form action="{{ route('homework.update', $homework->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('teacher.homework._form')
    </form>

@endsection
