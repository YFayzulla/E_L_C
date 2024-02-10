@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <h3 class="text-center">{{$group->name}}</h3>
        <!-- Add a form with a date input for filtering -->

        <form action="{{ route('attendance.filter') }}" method="GET">
{{--            @csrf--}}
            <div style="margin:10px">
                <label for="filter_date">Filter by Date:</label>
                <input type="date" id="filter_date" name="filter_date">
                <button type="submit" class="btn-primary" name="task" value="show">Filter</button>
                <button type="submit" class="btn-danger" name="task" value="report">Report</button>
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
                    <th>Tel</th>
                    <th>Teacher</th>
                    <th>Date</th>
                </tr>
                </thead>

                <!-- Table body -->

                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $loop->index+1 }}</td>
                        <td>{{ $item->student->name }}</td>
                        <td>{{ $item->student->phone }}</td>
                        <td>{{ $item->teacher->name }}</td>
                        <td>{{ $item->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
