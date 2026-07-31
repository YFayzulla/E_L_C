@extends('template.master')

@section('title', 'Yangi ota-ona')
@section('subtitle', 'Ota-ona hisobini yaratish va farzandlarni biriktirish')

@section('content')

    <div class="page-head">
        <a href="{{ route('parents.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Ota-onalar
        </a>
        <div class="page-sub">Hisob yaratilgach, ota-ona telefon raqami va parol bilan kiradi</div>
    </div>

    <form action="{{ route('parents.store') }}" method="post">
        @csrf
        @include('admin.parent._form', ['parent' => null])
    </form>

@endsection
