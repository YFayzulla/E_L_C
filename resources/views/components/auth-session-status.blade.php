@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success py-2']) }} role="status">
        {{ $status }}
    </div>
@endif
