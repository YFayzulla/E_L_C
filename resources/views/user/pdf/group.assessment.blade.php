@extends('template.pdf')
@section('pdf')

    <table class="table table-striped">
        <TR>
            <th>id</th>
            <th>name</th>
            <th>GOT MARK</th>
            <th>information</th>
            <th>rec group</th>
            <th>change group</th>
        </TR>

        @foreach($groups as $group)
            <tr>
                <th>{{$loop->index+1}}</th>
                <th>{{$group->student->name}}</th>
                <th>{{$group->get_mark}}</th>
                <th>{{$group->for_what}}</th>
                <th>{{$group->rec_group}}</th>
            </tr>
        @endforeach


    </table>

@endsection
