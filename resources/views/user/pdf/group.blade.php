@extends('template.pdf')
@section('pdf')

    <table class="table table-striped">
        <TR>
            <th>id</th>
            <th>Name</th>
            <th>opened date</th>
            <th>start time</th>
            <th>finish time</th>
            <th>level</th>
            <th>cost</th>
        </TR>

        @foreach($group as $item)
            <tr>
                <th>{{$loop->index+1}}</th>
                <th>{{$item->name}}</th>
                <th>{{$item->created_at}}</th>
                <th>{{$item->start_time}}</th>
                <th>{{$item->finish_time}}</th>
                <th>{{$item->level}}</th>
                <th>{{$item->monthly_payment}}</th>
            </tr>
        @endforeach


    </table>

@endsection
