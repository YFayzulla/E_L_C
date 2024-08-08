@extends('template.master')
@section('content')
    {{--    new code about whole table --}}




    <div class="container mt-4">
        <div class="p-4 bg-white shadow-sm rounded-lg">
            <h2 class="text-center mb-4">Attendance
                for {{ Carbon\Carbon::createFromDate($year, $month)->format('F Y') }}</h2>

            <form method="GET" action="{{ route('group.attendance',$group->id) }}" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <select id="month" name="date" class="form-control mr-2">
                            <option value="">Select Month</option>
                            @php
                                $currentYear = date('Y');
                                for ($month = 1; $month <= 12; $month++) {
                                    $monthNum = str_pad($month, 2, '0', STR_PAD_LEFT);
                                    $monthYear = $currentYear . '-' . $monthNum;
                                    $monthName = date('F', mktime(0, 0, 0, $month, 1));
                                    $selected = (date('Y-m') == $monthYear) ? 'selected' : '';
                                    echo "<option value=\"$monthYear\" $selected>$monthName $currentYear</option>";
                                }
                            @endphp
                        </select>
                    </div>
                    <div class="col d-flex">

                        <button type="submit" class="btn btn-primary mr-4">Show</button>
{{--                        test--}}
                        <button class="btn btn-danger">Report test</button>

                    </div>
                </div>
            </form>


            <div class="table-responsive">
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead>
                        <tr>
                            <th>Name</th>
                            @for ($i = 1; $i <= 31; $i++)
                                @php
                                    $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                                    $isToday = ($i == $today); // Check if the loop index is today's date
                                @endphp
                                <th class="{{ $isToday ? 'bg-success' : '' }}">{{ $day }}</th>
                                <!-- Add a class to highlight today's column -->
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
                                        $isDanger = $status === '0' || $status === 0; // Ensuring status is checked as both string and integer
                                        $isPresent = $status === '1' || $status === 1; // Ensuring status is checked as both string and integer
                                    @endphp
                                    <td class="{{ $isDanger ? 'bg-danger text-white' : '' }}">
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
    </div>





    {{--                          old code  for pdf  generate          --}}
    {{--    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">--}}


    {{--        <h3 class="text-center">{{$group->name}} daily  </h3>--}}
    {{--        <!-- Add a form with a date input for filtering -->--}}

    {{--                @dd($items)--}}
    {{--                <form action="{{ route('attendance.filter',$group->id) }}" method="GET">--}}
    {{--                    @csrf--}}

    {{--                    <div style="margin:10px">--}}
    {{--                        <label for="filter_date">Filter by Date:</label>--}}
    {{--                        <input type="date" id="filter_date" name="filter_date">--}}
    {{--                        <button type="submit" class="btn-primary" name="task" value="show">Filter</button>--}}
    {{--                        <button type="submit" class="btn-danger" name="task" value="report">Report</button>--}}
    {{--                    </div>--}}
    {{--                </form>--}}

@endsection
