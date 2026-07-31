@extends('template.master')

@section('title', 'Guruhlar')
@section('subtitle', 'Guruhlar ro‘yxati, o‘qituvchilari va talabalar soni')

@section('content')

    <div class="page-head justify-content-end">
        <div class="d-flex gap-2 flex-wrap">
            @role('admin')
                <a href="{{ URL::to('/group/pdf') }}" class="btn btn-outline-secondary">
                    <i class="bx bxs-file-pdf me-1"></i> PDF hisobot
                </a>
            @endrole
            <a href="{{ route('group.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Yangi guruh
            </a>
        </div>
    </div>

    @include('admin.group._filters')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>Topildi: <strong>{{ $groups->total() }}</strong> ta guruh</span>
            <span class="text-muted" style="font-size: .82rem;">Kutish zali ro‘yxatga kirmaydi</span>
        </div>

        @if($groups->isEmpty())
            <div class="empty-state">
                <i class="bx bx-search-alt"></i>
                <h6>Filtrga mos guruh topilmadi</h6>
                <p class="mb-3">Qidiruv shartlarini o‘zgartiring yoki filtrni tozalang.</p>
                <a href="{{ route('group.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-x me-1"></i> Tozalash
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 3rem;">#</th>
                        <th>Guruh</th>
                        <th>O‘qituvchi</th>
                        <th>Xona</th>
                        <th>Dars vaqti</th>
                        <th class="text-center">Talabalar</th>
                        <th class="text-end">Oylik to‘lov</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($groups as $group)
                        <tr>
                            <td class="text-muted">{{ $groups->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('group.students', $group->id) }}"
                                   class="fw-semibold text-decoration-none">{{ $group->name }}</a>
                                @if($group->isFinished())
                                    <span class="badge bg-label-secondary ms-1">Tugagan</span>
                                    @if($group->finished_at)
                                        <div class="text-muted" style="font-size: .72rem;">
                                            {{ $group->finished_at->format('d.m.Y') }} da yakunlangan
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @forelse($group->teachers as $teacher)
                                    <span class="badge bg-label-primary me-1 mb-1 d-inline-block">{{ $teacher->name }}</span>
                                @empty
                                    <span class="text-muted">— biriktirilmagan</span>
                                @endforelse
                            </td>
                            <td>
                                @if($group->room)
                                    <span class="badge bg-label-info">{{ $group->room->room }}-xona</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted">
                                @if($group->start_time || $group->finish_time)
                                    {{ $group->start_time ?: '—' }} – {{ $group->finish_time ?: '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-{{ $group->members_count > 0 ? 'success' : 'warning' }}">
                                    {{ $group->members_count }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format($group->monthly_payment, 0, '.', ' ') }} so‘m
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('group.students', $group->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Talabalar">
                                        <i class="bx bx-group"></i>
                                    </a>
                                    <a href="{{ route('group.attendance', $group->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Davomat">
                                        <i class="bx bx-check-square"></i>
                                    </a>
                                    <a href="{{ route('group.edit', $group->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Tahrirlash">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>

                                    {{-- Yakunlash: guruh "tugagan" bo'ladi va boshqa faol
                                         guruhda o'qimayotgan talabalari "bitirgan" holatiga
                                         o'tadi (to'lovlar ro'yxatidan chiqadi). --}}
                                    @if($group->id !== \App\Models\Group::WAITING_ROOM_ID)
                                        @if($group->isFinished())
                                            <form action="{{ route('group.reopen', $group->id) }}" method="post"
                                                  onsubmit="return confirm('«{{ $group->name }}» qayta ochilsinmi? Shu guruh bilan bitirgan deb belgilangan talabalar yana faol bo‘ladi.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                        title="Qayta ochish">
                                                    <i class="bx bx-refresh"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('group.finish', $group->id) }}" method="post"
                                                  onsubmit="return confirm('«{{ $group->name }}» yakunlansinmi? Boshqa faol guruhda o‘qimayotgan talabalari «bitirgan» deb belgilanadi va to‘lovlar ro‘yxatidan chiqadi.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                        title="Guruhni yakunlash">
                                                    <i class="bx bx-flag"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                    @php
                                        $deleteWarning = '«' . $group->name . '» guruhi o‘chirilsinmi?'
                                            . "\n\nGuruhning BARCHA davomat yozuvlari ham butunlay o‘chib ketadi — tiklab bo‘lmaydi."
                                            . "\n" . $group->members_count . ' ta talaba guruhdan chiqariladi.';
                                    @endphp
                                    <form action="{{ route('group.destroy', $group->id) }}" method="post"
                                          onsubmit="return confirm({{ \Illuminate\Support\Js::from($deleteWarning) }});">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="O‘chirish">
                                            <i class="bx bx-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($groups->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $groups->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
