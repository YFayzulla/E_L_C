@extends('template.master')

@section('title', 'Yangi SMS shabloni')
@section('subtitle', 'Ota-onalarga yuboriladigan tayyor matn')

@section('content')

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('sms-templates.index') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div class="page-sub">Matnda {o‘zgaruvchi} ishlatsangiz, yuborishda talabaning ma’lumoti qo‘yiladi</div>
        </div>
    </div>

    <form action="{{ route('sms-templates.store') }}" method="post">
        @csrf
        @include('admin.sms-template._form')
    </form>

@endsection
