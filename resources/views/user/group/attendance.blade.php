@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <h3 class="text-center">Attendance</h3>
        <!-- Add a form with a date input for filtering -->

        <form action="{{ route('attendance.filter') }}" method="GET">
            <div style="margin:10px">
            <label for="filter_date">Filter by Date:</label>
            <input type="date" id="filter_date" name="filter_date">
            <button type="submit" class="btn-primary">Filter</button>
            </div>
        </form>


        <!-- Display the attendance records -->
        <div class="table-responsive">
            <table class="table table-hover">
                <!-- Table headers -->
                <thead class="table-active">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Teacher</th>
                    <th>Date</th>
                </tr>
                </thead>

                <!-- Table body -->

                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->student->name }}</td>
                        <td>{{ $item->teacher->name }}</td>
                        <td>{{ $item->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
