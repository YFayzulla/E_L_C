@extends('layouts.pdf')
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
            @forelse($users as $student)
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
            @empty
                <tr>
                    <td colspan="6" class="text-center">No payment records found for the selected period.</td>
                </tr>
            @endforelse
        </table>
    </div>
</div>
<p>Sum in date: {{$sum}} </p>
@endsection