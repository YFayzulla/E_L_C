`@extends('template.master')
@section('content')
    <div class="card">

        @php
            use Illuminate\Support\Carbon;
        @endphp

        <h1 class="text-center"> Students who failed </h1>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>Tel</th>
                    <th>Parents tel</th>
                    {{--                <th>oylik to`lov</th>--}}
                    <th>group</th>
                    <th class="">action</th>
                </tr>
                </thead>
                @foreach($students as $student)
                    <tbody id="myTable" class="table-group-divider">
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th>{{$student->name}}</th>
                        <th>{{$student->phone}}</th>
                        <th>{{$student->parents_tel}}</th>
                        <th>{{($student->group->name == 'waiters')?'Waiting room' : $student->group->name }}</th>
                        <th>
                            <button type="button" class="btn-outline-success btn m-2" data-bs-toggle="modal"
                                    data-bs-target="#exampleModal{{$student->id}}" data-bs-whatever="@mdo"
                            > conclusion
                            </button>
                            {{--Modal--}}
                            <div class="modal fade" id="exampleModal{{$student->id}}" tabindex="-1"
                                 aria-labelledby="exampleModalLabel"
                                 aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{route('student.change.group',$student->id)}}" method="post">
                                                @csrf
                                                <label for="recipient-name"
                                                       class="col-form-label"> sitting in another group </label>
                                                <select name="group_id" class="form-control">
                                                    @foreach($groups as $g)
                                                        <option value="{{$g->id}}">{{$g->name}}</option>
                                                    @endforeach
                                                </select>


                                                <label for="payment" class="form-label mt-2">Payment</label>
                                                <input type="number" name="should_pay" class="form-control"
                                                       id="payment-input">
                                                <button type="submit" class="btn btn-outline-primary m-2">save
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </th>
                    </tr>
                    </tbody>
                @endforeach
            </table>
        </div>

@endsection
`
