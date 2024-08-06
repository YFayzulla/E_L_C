@extends('template.master')

@section('content')
    <div class="container mt-4">
        <div class="p-4 bg-white shadow-sm rounded-lg">
            <h2 class="text-center mb-4">Attendance for {{ Carbon\Carbon::createFromDate($year, $month)->format('F Y') }}</h2>

            <form method="GET" action="{{ route('attendance.list') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <input type="month" name="date" value="{{ $year }}-{{ $month }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Show</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                    <tr>
                        <th>Name</th>
                        @for ($i = 1; $i <= 31; $i++)
                            <th>{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</th>
                        @endfor
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($data as $userName => $days)
                        <tr>
                            <td>{{ $userName }}</td>
                            @for ($i = 1; $i <= 31; $i++)
                                @php
                                    $day = str_pad($i, 2, '0', STR_PAD_LEFT);

                                    $status = $days[$day] ?? '1';
//                                    dd($days[$day],$status);
                                    $isDanger = $status === '0' || $status === 0; // Ensuring status is checked as both string and integer
                                    $isPresent = $status === '1' || $status === 1; // Ensuring status is checked as both string and integer
                                @endphp
                                <td class="{{ $isDanger ? 'bg-danger text-black' : '' }}">
                                    @if ($isPresent)
                                        <span class="text-danger font-weight-bold">X</span>
                                    @else
                                        {{ $status }}
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
