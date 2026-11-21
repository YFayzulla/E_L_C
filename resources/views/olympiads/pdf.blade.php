@extends('layouts.pdf')
@section('pdf')
    @php
        $sections = collect($olympiad->sections ?? []);
        $maxTotal = $olympiad->maxTotal();
    @endphp
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        th, td { padding: 5px; }
    </style>

    <h3 style="margin: 0 0 4px;">{{ $olympiad->name }}</h3>
    <div style="margin-bottom: 12px; color: #555;">
        {{ $olympiad->held_on?->format('d.m.Y') ?? '' }} · {{ $participants->count() }} ta ishtirokchi
    </div>

    <table>
        <tr>
            <th>O‘rin</th>
            <th>Ishtirokchi</th>
            @foreach($sections as $section)
                <th style="text-align: center;">{{ $section['label'] }} /{{ $section['max'] }}</th>
            @endforeach
            <th style="text-align: center;">Jami</th>
            <th style="text-align: center;">%</th>
            <th>Holat</th>
            <th style="text-align: center;">Chegirma</th>
            <th>Guruh</th>
        </tr>
        @foreach($participants as $p)
            @php $scores = (array) ($p->scores ?? []); @endphp
            <tr>
                <td>{{ $p->rank ?? '—' }}</td>
                <td>{{ $p->name }}</td>
                @foreach($sections as $section)
                    <td style="text-align: center;">{{ $scores[$section['key']] ?? '—' }}</td>
                @endforeach
                <td style="text-align: center;">{{ $p->total !== null ? $p->total . '/' . $maxTotal : '—' }}</td>
                <td style="text-align: center;">{{ $p->percent !== null ? $p->percent . '%' : '—' }}</td>
                <td>{{ $p->isEnrolled() ? 'Biriktirildi' : $p->statusLabel() }}</td>
                <td style="text-align: center;">{{ $p->discount_percent ? $p->discount_percent . '%' : '—' }}</td>
                <td>{{ $p->group?->name ?? '—' }}</td>
            </tr>
        @endforeach
    </table>
@endsection
