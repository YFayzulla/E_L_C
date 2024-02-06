@extends('template.pdf')
@section('pdf')

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
                <td>{{ $loop->index+1 }}</td>
                <td>{{ $item->student->name }}</td>
                <td>{{ $item->teacher->name }}</td>
                <td>{{ $item->created_at }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
