@extends('template.pdf')
@section('pdf')
<input type="hidden" value="{{$sum=0}}">
<div class="container" style="display: flex; justify-content: space-between;">
    <div class="table-responsive text-nowrap">
        <table class="table">
            <tr>
                <th>no</th>
                <th>name</th>
                <th>paid</th>
                <th>group</th>
                <th>type</th>
                <th>date</th>
            </tr>
            @foreach($users as $student)
                <tr>
                    <th>{{$loop->index+1}}</th>
                    <th>{{$student->name}}</th>
                    <th>{{$student->payment}}</th>
                    <th>{{$student->group}}</th>
                    <th>{{$student->type_of_money}}</th>
                    <th>{{$student->date}}</th>
                    @php
                        $sum += $student->payment
                    @endphp
                </tr>
            @endforeach
        </table>
    </div>
</div>
<p>Sum in date: {{$sum}} </p>
@endsection
