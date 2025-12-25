@extends('layouts.pdf')
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
        @forelse($items as $item)
            <tr>
                <td>{{ $loop->index+1 }}</td>
                <td>{{ $item->student->name }}</td>
                <td>{{ $item->teacher->name }}</td>
                <td>{{ $item->created_at }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">No attendance records found for this group.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection