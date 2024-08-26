@extends('template.master')
@section('content')

    <div class="d-flex justify-content-center">
        <table class="table table-sm w-75">
{{--            @dd("aa")--}}
            @foreach($groups as $group)


                <tr>
                    <th>
                        <a href="{{route('group.attendance', $group->group_id)}}" class="btn btn-outline-primary w-100 text-left">
                            <b>{{ $group->group->name }}</b>
                        </a>
                    </th>
                </tr>
            @endforeach
        </table>
    </div>

@endsection
