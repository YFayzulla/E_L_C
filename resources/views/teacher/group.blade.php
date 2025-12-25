@extends('template.master')
@section('content')

    <div class="d-flex justify-content-center">
        <table class="table table-sm w-75">
            @forelse($groups as $group)
                <tr>
                    <th>

                        <a href="{{route('attendance.check', $group->group_id)}}" class="btn btn-outline-primary w-100 text-left">
                            <b>{{ $group->group->name }}</b>
                        </a>

                    </th>
                </tr>
            @empty
                <tr>
                    <td>No groups assigned yet.</td>
                </tr>
            @endforelse
        </table>
    </div>

@endsection