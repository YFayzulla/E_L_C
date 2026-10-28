@extends('template.master')

@section('title', 'Natijalarim')
@section('subtitle', 'Oylik va o‘tish testlari bo‘yicha ballaringiz')

@section('content')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>Test natijalari</span>
            @if($tests->isNotEmpty())
                <span class="badge bg-label-primary">{{ $tests->count() }} ta</span>
            @endif
        </div>

        @include('partials.test-results-table', ['tests' => $tests])
    </div>
@endsection
