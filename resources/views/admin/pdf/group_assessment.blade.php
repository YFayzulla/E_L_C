@extends('layouts.pdf')
@section('pdf')

    <table class="table table-striped">
        <TR>
            <th>id</th>
            <th>name</th>
            <th>group</th>
            <th>GOT MARK</th>
            <th>information</th>
            <th>rec group</th>
        </TR>

        @forelse($groups as $group)
            <tr>
                <th>{{$loop->index+1}}</th>
                <th>{{$group->student->name}}</th>
                <th>{{$group->group}}</th>
                <th>{{$group->get_mark}}</th>
                <th>{{$group->for_what}}</th>
                <th>{{$group->rec_group}}</th>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No assessment data available for this group.</td>
            </tr>
        @endforelse


    </table>

@endsection