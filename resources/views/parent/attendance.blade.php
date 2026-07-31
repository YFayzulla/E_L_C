@extends('template.master')

@section('title', 'Davomat')
@section('subtitle', 'Farzandlaringiz qoldirgan darslar')

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('parent.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Farzandlarim
        </a>
    </div>

    <div class="card">
        @if($attendances->isEmpty())
            <div class="empty-state">
                <i class="bx bx-check-circle"></i>
                <h6>Hammasi joyida</h6>
                <p class="mb-0">Qoldirilgan dars qayd etilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover" id="myTable">
                    <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Farzand</th>
                        <th>Guruh</th>
                        <th>Dars</th>
                        <th class="text-end">Holat</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($attendances as $item)
                        <tr>
                            <td>{{ $item->created_at?->format('d.m.Y') }}</td>
                            <td>
                                <a href="{{ route('parent.child', $item->user_id) }}" class="fw-semibold">
                                    {{ $item->user->name ?? '—' }}
                                </a>
                            </td>
                            <td>{{ $item->group->name ?? '—' }}</td>
                            <td class="text-muted">{{ $item->lesson->name ?? '—' }}</td>
                            <td class="text-end">
                                @if((int) $item->status === 2)
                                    <span class="badge bg-label-warning">Kechikdi</span>
                                @else
                                    <span class="badge bg-label-danger">Kelmadi</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

@endsection
