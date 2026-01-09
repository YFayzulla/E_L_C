@extends('template.master')
@section('content')

    <div class="card">
        <form action="{{ route('assessment.update', $id) }}" method="post">
            @csrf
            @method('PUT')
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <label for="lesson" class="mr-2"></label>
                <input type="text" name="lesson" id="lesson" class="form-control w-50 m-3" placeholder="Test Name"
                       required>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>T/R</th>
                        <th>Name</th>
                        <th>Mark</th>
                        <th>Desc</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @forelse($students as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student->name }}</td>
                            <td>
                                <input type="number" class="form-control" style="width: 70px;" name="end_mark[]"
                                       required>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="reason[]">
                            </td>
                        </tr>
                        <input type="hidden" name="student[]" value="{{ $student->id }}">
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No students found in this group.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="modal-footer mt-4">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>

@endsection