@extends('layouts.pdf')
@section('pdf')
    <input type="hidden" value="{{$sum=0}}">
    <div class="container" style="display: flex; justify-content: space-between;">
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Parents tel</th>
                        <th>Parents name</th>
                        <th>Group</th>
                        <th>Should Pay</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($student as $item)
                    <tr>
                        <td>{{$loop->index+1}}</td>
                        <td>{{$item->name}}</td>
                        <td>{{$item->phone}}</td>
                        <td>{{$item->parents_tel}}</td>
                        <td>{{$item->parents_name}}</td>
                        {{-- Group nomlarini vergul bilan ajratib chiqarish --}}
                        <td>{{ $item->groups->pluck('name')->implode(', ') ?: 'No Group' }}</td>
                        <td>{{ number_format($item->should_pay, 0, '.', ' ') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No student data available.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection