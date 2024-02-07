@extends('template.pdf')
@section('pdf')

    <table class="table table-striped">
        <TR>
            <th>id</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Location</th>
            <th>Date born</th>
        </TR>

        @foreach($teacher as $item)
            <tr>
                <th>{{$loop->index+1}}</th>
                <th>{{$item->name}}</th>
                <th>{{$item->phone}}</th>
                <th>{{$item->location}}</th>
                <th>{{$item->date_born}}</th>
            </tr>
        @endforeach


    </table>

@endsection
