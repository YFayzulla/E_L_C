@extends('template.master')

@section('title', $parent->name)
@section('subtitle', 'Ota-ona ma’lumotlarini tahrirlash')

@section('content')

    <div class="page-head">
        <a href="{{ route('parents.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Ota-onalar
        </a>
        <div class="page-sub" dir="ltr">+{{ $parent->phone }}</div>
    </div>

    <form action="{{ route('parents.update', $parent->id) }}" method="post">
        @csrf
        @method('PUT')
        @include('admin.parent._form')
    </form>

@endsection
