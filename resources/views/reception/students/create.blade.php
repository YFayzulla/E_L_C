@extends('template.master')

@section('title', 'Yangi kelgan')
@section('subtitle', 'Reception daftariga yangi o‘quvchini yozish')

@section('content')
    <div class="page-head justify-content-end">
        <a href="{{ route('reception.students.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Ro‘yxatga qaytish
        </a>
    </div>

    <form action="{{ route('reception.students.store') }}" method="POST">
        @csrf
        @include('reception.students._form')
    </form>
@endsection
