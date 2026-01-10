@extends('template.master')

@section('content')
    <div class="card shadow-md rounded-lg">
        <div class="card-header bg-light border-bottom">
            <h5 class="card-title mb-0 text-primary">My Absences and Lates</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Lesson</th>
                        <th>Group</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $loop->index + 1 }}</td>
                        <td>{{ $attendance->lesson->name }}</td>
                        <td>{{ $attendance->group->name }}</td>
                        <td>
                            @if($attendance->status == 0)
                                <span class="badge bg-danger">Absent</span>
                            @elseif($attendance->status == 2)
                                <span class="badge bg-warning">Reasonable</span>
                            @endif
                        </td>
                        <td>{{ $attendance->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Congratulations! You have no recorded absences or lates.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
           
        </div>
    </div>
@endsection