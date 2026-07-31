@extends('template.pdf')
@section('pdf')

    <h3 style="margin:0 0 4px 0;">O‘qituvchilar ro‘yxati</h3>
    <p style="margin:0 0 14px 0; font-size:12px; color:#666;">
        ALPHA o‘quv markazi &middot; {{ now()->format('d.m.Y H:i') }} &middot; jami {{ count($teacher) }} ta
    </p>

    <table>
        <thead>
        <tr>
            <th style="width:34px;">#</th>
            <th>Ism familiya</th>
            <th style="width:110px;">Telefon</th>
            <th>Guruhlar</th>
            <th style="width:88px;">Tug‘ilgan sana</th>
            <th>Manzil</th>
            <th style="width:52px;">Ulush</th>
        </tr>
        </thead>
        <tbody>
        @forelse($teacher as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->name }}</td>
                <td>+{{ $item->phone }}</td>
                <td>{{ $item->teacherGroups->pluck('name')->implode(', ') ?: '—' }}</td>
                <td>{{ $item->date_born ? \Carbon\Carbon::parse($item->date_born)->format('d.m.Y') : '—' }}</td>
                <td>{{ $item->location ?: '—' }}</td>
                <td>{{ $item->percent !== null && $item->percent !== '' ? $item->percent . '%' : '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center;">O‘qituvchilar topilmadi.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

@endsection
