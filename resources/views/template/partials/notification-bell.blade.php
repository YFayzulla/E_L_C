@php
    $bellUser = auth()->user();
    $bellUnread = \App\Models\StaffNotification::for($bellUser)->unread()->count();
    $bellLatest = \App\Models\StaffNotification::for($bellUser)->latest()->limit(6)->get();
@endphp

<li class="nav-item dropdown">
    <button class="btn-icon position-relative" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
            aria-expanded="false" title="Bildirishnomalar" aria-label="Bildirishnomalar">
        <i class="bx bx-bell"></i>
        @if($bellUnread > 0)
            <span class="badge rounded-pill bg-danger position-absolute"
                  style="top: -2px; right: -4px; font-size: .62rem; padding: .25em .45em;">
                {{ $bellUnread > 99 ? '99+' : $bellUnread }}
            </span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-end p-0" style="width: min(360px, calc(100vw - 2rem));">
        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
            <span class="fw-semibold">Bildirishnomalar</span>
            @if($bellUnread > 0)
                <form action="{{ route('notifications.read-all') }}" method="post" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm p-0">Hammasini o‘qildi</button>
                </form>
            @endif
        </div>

        @forelse($bellLatest as $item)
            <a href="{{ route('notifications.open', $item->id) }}"
               class="dropdown-item d-flex gap-2 py-2 border-bottom text-wrap {{ $item->isUnread() ? 'bg-label-primary' : '' }}"
               style="white-space: normal;">
                <i class="bx {{ $item->icon() }} mt-1"></i>
                <span class="flex-grow-1" style="min-width: 0;">
                    <span class="d-block fw-semibold" style="font-size: .85rem;">{{ $item->title }}</span>
                    @if($item->body)
                        <span class="d-block text-muted" style="font-size: .78rem;">{{ \Illuminate\Support\Str::limit($item->body, 120) }}</span>
                    @endif
                    <span class="d-block text-muted" style="font-size: .72rem;">{{ $item->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <div class="px-3 py-4 text-center text-muted" style="font-size: .85rem;">
                Hozircha bildirishnoma yo‘q.
            </div>
        @endforelse

        <a href="{{ route('notifications.index') }}" class="dropdown-item text-center py-2" style="font-size: .85rem;">
            Barchasini ko‘rish
        </a>
    </div>
</li>
