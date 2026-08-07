@extends('template.master')

@section('title', 'Oylik test')
@section('subtitle', 'Oylik test uchun guruhni tanlang')

@section('content')

    <div class="card">
        @if($groups->isEmpty())
            <div class="empty-state">
                <i class="bx bx-list-check"></i>
                <h6>Guruh yo‘q</h6>
                <p class="mb-0">Sizga hali oylik test uchun guruh biriktirilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Guruh</th>
                        <th class="text-end" style="width: 8rem;">Amal</th>
                    </tr>
                    </thead>
                    {{-- id="myTable": navbardagi tezkor qidiruv shu jadvalni filtrlaydi --}}
                    <tbody id="myTable">
                    @foreach($groups as $group)
                        <tr>
                            <td class="fw-semibold">{{ $group->group->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('assessment.show', $group->group_id) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    Test o‘tkazish <i class="bx bx-right-arrow-alt ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection