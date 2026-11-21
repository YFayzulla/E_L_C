@extends('template.master')

@section('title', 'Olimpiadalar')
@section('subtitle', 'Talabalarni saralab olish va chegirma asosida qabul qilish')

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('olympiads.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Yangi olimpiada
        </a>
    </div>

    <div class="card">
        @if($olympiads->isEmpty())
            <div class="empty-state">
                <i class="bx bx-trophy"></i>
                <h6>Olimpiada yo‘q</h6>
                <p class="mb-0">Birinchi olimpiadani yarating: bo‘limlar, ishtirokchilar va natijalar.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Olimpiada</th>
                        <th>Sana</th>
                        <th>Holat</th>
                        <th class="text-center">Ishtirokchi</th>
                        <th class="text-center">Qabul qilindi</th>
                        <th class="text-center">Guruhga biriktirildi</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($olympiads as $olympiad)
                        <tr>
                            <td>
                                <a href="{{ route('olympiads.show', $olympiad) }}" class="fw-semibold">{{ $olympiad->name }}</a>
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ collect($olympiad->sections)->pluck('label')->implode(' · ') }}
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $olympiad->held_on?->format('d.m.Y') ?? '—' }}</td>
                            <td><span class="badge bg-label-{{ $olympiad->statusTone() }}">{{ $olympiad->statusLabel() }}</span></td>
                            <td class="text-center">{{ $olympiad->participants_count }}</td>
                            <td class="text-center">{{ $olympiad->accepted_count }}</td>
                            <td class="text-center">{{ $olympiad->enrolled_count }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
