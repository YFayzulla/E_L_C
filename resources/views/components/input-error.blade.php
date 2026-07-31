@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'list-unstyled text-danger mb-0']) }} style="font-size: .8rem;">
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
