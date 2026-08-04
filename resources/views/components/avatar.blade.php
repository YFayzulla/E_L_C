@props([
    // Either pass a User model, or a name (and optionally a photo path).
    'user'   => null,
    'name'   => null,
    'photo'  => null,
    // xs | sm | md | lg — Sneat's own scale.
    'size'   => 'sm',
    // Green presence dot, for the signed-in person.
    'online' => false,
    // Print the name beside the picture. Off by default so the component can
    // sit in a cell that already prints it.
    'label'  => false,
    // Secondary line under the name — a phone, a group, a role.
    'meta'   => null,
    'href'   => null,
])

@php
    $displayName = trim((string) ($name ?? $user?->name ?? ''));
    $photoPath   = $photo ?? $user?->photo;

    $initials = collect(preg_split('/\s+/u', $displayName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: '?';

    // A stable tint per person: the same name always lands on the same tone,
    // so a column of initials reads as distinct people rather than one block.
    $tones = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
    $tone  = $tones[crc32($displayName) % count($tones)];

    $sizeClass = match ($size) {
        'xs' => 'avatar-xs',
        'md' => '',
        'lg' => 'avatar-lg',
        default => 'avatar-sm',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'avatar-cell text-body text-decoration-none']) }}>
@else
    <span {{ $attributes->merge(['class' => 'avatar-cell']) }}>
@endif

    <span class="avatar {{ $sizeClass }} {{ $online ? 'avatar-online' : '' }}">
        @if($photoPath)
            <img src="{{ asset('storage/' . $photoPath) }}"
                 alt="{{ $displayName }}"
                 class="rounded-circle w-100 h-100"
                 style="object-fit: cover;"
                 loading="lazy">
        @else
            <span class="avatar-initial rounded-circle bg-label-{{ $tone }}">{{ $initials }}</span>
        @endif
    </span>

    @if($label || $meta)
        <span class="avatar-cell-text">
            @if($label)
                <span class="d-block text-truncate">{{ $displayName }}</span>
            @endif
            @if($meta)
                <small class="d-block text-truncate text-muted">{{ $meta }}</small>
            @endif
        </span>
    @endif

@if($href)
    </a>
@else
    </span>
@endif
