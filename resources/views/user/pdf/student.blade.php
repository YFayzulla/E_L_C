@extends('template.pdf')
@section('pdf')
    <input type="hidden" value="{{$sum=0}}">
    <div class="container" style="display: flex; justify-content: space-between;">
        <div class="table-responsive text-nowrap">
            <table class="table">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone Number</th>
                    <th>Parents tel</th>
                    <th>Parents name</th>
                    {{--                <th>oylik to`lov</th>--}}
                    <th>Group</th>
                    <th>Should Pay</th>
                </tr>
                @foreach($student as $students)
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        {{--                    @dd($student->name)--}}
                        <th>{{$students->name}}</th>
                        <th>{{$students->phone}}</th>
                        <th>{{$students->parents_tel}}</th>
                        <th>{{$students->parents_name}}</th>
                        {{--                    <th>@if(Carbon::parse( $student->studentdept->date)->greaterThan(Carbon::parse(now()->format('Y-m-d')) )) <p style="color: #a52834" >{{ 'qarz' }}</p> @else <p style="color: #0f5132">{{ 't`olangan' }}</p> @endif </th>--}}
                        <th>{{($students->group->name=='waiters')?'Waiting room':$students->group->name}}</th>
                        <th>{{$students->should_pay}}</th>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
