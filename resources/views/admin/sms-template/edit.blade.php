@extends('template.master')

@section('title', $template->name)
@section('subtitle', 'SMS shablonini tahrirlash')

@section('content')

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('sms-templates.index') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div>
                <div class="page-sub">
                    <i class="bx {{ $template->eventIcon() }} me-1"></i>{{ $template->eventLabel() }}
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('sms-templates.update', $template->id) }}" method="post">
        @csrf
        @method('PUT')
        @include('admin.sms-template._form')
    </form>

@endsection
