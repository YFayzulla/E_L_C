`@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        {{--select delete--}}

        <h1>{{$id}}</h1>

        <a href="{{ URL::to('/assessment/pdf',$id)}}" class="btn btn-danger mb-3 float-end"> Report </a>

        <form action="{{route('deleteMultiple')}}" method="post">
            @csrf
            @method('DELETE')

            <button type="button" id="selectAllBtn" class="btn btn-primary mb-3 me-1">Select All</button>
            <button class="btn btn-danger mb-3 text-white">Delete specified data</button>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-active">

                    <TR>

                        <td>+</td>
                        <th>id</th>
                        <th>name</th>
                        <th>GOT MARK</th>
                        <th>information</th>
                        <th>rec group</th>
                        <th>change group</th>

                    </TR>

                    </thead>

                    {{--                    @dd($groups)--}}

                    @foreach($assessments as $assessment)
                        <tbody id="myTable" class="table-group-divider">
                        <tr>
                            <td>
                                <input type="checkbox" class="checkbox" name="selectedItems[]"
                                       value="{{ $assessment->id }}">
                            </td>
                            <th>{{$loop->index+1}}</th>
                            <th>{{$assessment->student->name}}</th>
                            <th>{{$assessment->new_get_mark}}</th>
                            <th>{{$assessment->for_what}}</th>
                            <th>{{($assessment->rec_group == 'waiters')?'waiting room':$assessment->rec_group }}</th>

                            <th>
                                <button type="button" class="btn-outline-success btn m-2" data-bs-toggle="modal"
                                        data-bs-target="#exampleModal{{$assessment->user_id}}" data-bs-whatever="@mdo"
                                > submit
                                </button>
                            </th>
                        </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        </form>
    </div>

    <!-- Modal -->
    @foreach($assessments as $assessment)
        <div class="modal fade" id="exampleModal{{$assessment->user_id}}" tabindex="-1"
             aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        {{$assessment->student->name}}
                    </div>
                    <div class="modal-body">
                        <form action="{{route('student.change.group',$assessment->user_id)}}" method="post">
                            @csrf
                            {{$assessment->student->name}}

                            <label for="recipient-name" class="form-label">Change group</label>
                            <select name="group_id" class="form-select" id="group-select">
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" data-payment="{{ $group->monthly_payment }}">
                                        {{($group->name == "waiters") ? 'Waiting room' : $group->name }}
                                    </option>
                                @endforeach
                            </select>

                            <label for="payment" class="form-label mt-2">Payment</label>
                            <input type="number" name="should_pay" class="form-control" id="payment-input">

                            <div class="mt-3">
                                <button type="submit" class="btn btn-outline-success m-2">Save</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <script>
        $(document).ready(function () {
            $("#selectAllBtn").click(function () {
                $(".checkbox").prop("checked", true);
            });
        });

    </script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

@endsection

