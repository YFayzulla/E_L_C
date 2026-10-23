@extends('template.master')
@section('title', 'Bildirishnomalar')
@section('content')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Bildirishnomalar</h5>
            <form action="{{ route('notifications.read-all') }}" method="post" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-check-double me-1"></i> Hammasini o‘qildi
                </button>
            </form>
        </div>

        @if($notifications->isEmpty())
            <div class="empty-state">
                <i class="bx bx-bell"></i>
                <h6>Bildirishnoma yo‘q</h6>
                <p class="mb-0">O‘qituvchi talabani ko‘chirsa yoki yangi talaba yozilsa, shu yerda ko‘rinadi.</p>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach($notifications as $item)
                    <a href="{{ route('notifications.open', $item->id) }}"
                       class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $item->isUnread() ? 'bg-label-primary' : '' }}">
                        <i class="bx {{ $item->icon() }} fs-4 mt-1"></i>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="d-flex justify-content-between gap-2 flex-wrap">
                                <span class="fw-semibold">{{ $item->title }}</span>
                                <small class="text-muted">{{ $item->created_at->format('d.m.Y H:i') }}</small>
                            </div>
                            @if($item->body)
                                <div class="text-muted" style="font-size: .88rem;">{{ $item->body }}</div>
                            @endif
                            @if($item->actor)
                                <small class="text-muted"><i class="bx bx-user me-1"></i>{{ $item->actor->name }}</small>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="card-footer">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
