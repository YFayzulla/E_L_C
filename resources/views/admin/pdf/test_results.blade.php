@extends('layouts.pdf')
@section('pdf')

    <h3 style="margin: 0 0 4px;">{{ $groupName }}</h3>
    <div style="margin-bottom: 12px; color: #555;">
        {{ $testName }} · {{ $date?->format('d.m.Y') }}
    </div>

    {{-- Bitta talaba — bitta qator (Test natijalari sahifasi bilan bir xil). --}}
    <table>
        <tr>
            <th>#</th>
            <th>Talaba</th>
            @foreach($skills as $skill => $label)
                <th style="text-align: center;">{{ $label }} /{{ $maxes[$skill] ?? 100 }}</th>
            @endforeach
            @if(count($skills) > 1)
                <th style="text-align: center;">O‘rtacha</th>
                <th style="text-align: center;">Yig‘indi</th>
            @endif
        </tr>

        @foreach($students as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row->name }}</td>
                @foreach($skills as $skill => $label)
                    <td style="text-align: center;">{{ isset($row->marks[$skill]) ? $row->marks[$skill]['mark'] : '—' }}</td>
                @endforeach
                @if(count($skills) > 1)
                    <td style="text-align: center;"><strong>{{ $row->average === null ? '—' : $row->average . '%' }}</strong></td>
                    <td style="text-align: center;">{{ $row->sum }}/{{ $row->sum_max }}</td>
                @endif
            </tr>
        @endforeach
    </table>

@endsection
