@extends('template.master')

@section('title', 'Natijalarim')
@section('subtitle', 'Oylik va o‘tish testlari bo‘yicha ballaringiz')

@section('content')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>Test natijalari</span>
            @if($results->total() > 0)
                <span class="badge bg-label-primary">{{ $results->total() }} ta</span>
            @endif
        </div>

        @if($results->isEmpty())
            <div class="empty-state">
                <i class="bx bx-clipboard"></i>
                <h6>Natija yo‘q</h6>
                <p class="mb-0">Hozircha test natijalari kiritilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Test</th>
                        <th class="d-none d-sm-table-cell">Maqsad</th>
                        <th>Ball</th>
                        <th class="text-end">Sana</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($results as $result)
                        @php $mark = (int) $result->get_mark; @endphp
                        <tr>
                            <td>{{ $result->test_name ?? 'Test' }}</td>
                            <td class="d-none d-sm-table-cell text-muted">{{ $result->skillLabel() }}</td>
                            <td>
                                <span class="badge bg-label-{{ $mark >= 80 ? 'success' : ($mark >= 60 ? 'warning' : 'danger') }}">
                                    {{ $result->get_mark }}
                                </span>
                            </td>
                            <td class="text-end text-muted">{{ $result->created_at?->format('d.m.Y') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($results->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $results->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
