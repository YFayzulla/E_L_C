@extends('template.pdf')
@section('pdf')

    <div class="max-w-xl">
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <td>No</td>
                    <td>name</td>
                    <td>group</td>
                    <td>payment</td>
                    <td>status</td>
                </tr>
                </thead>
                @foreach($students as $student)
                    <tbody id="myTable" class="table-group-divider">
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th>{{$student->name}}</th>
                        <th>{{$student->group->name}}</th>
                        <th>{{$student->should_pay}}</th>
                        <th>@if( $student->status <= 0 )
                                <p class="text-danger"> debtor </p>
                            @else
                                <p class="text-success"> paid </p>
                            @endif</th>
                    </tr>
                    </tbody>
                @endforeach

            </table>
        </div>
    </div>

@endsection
