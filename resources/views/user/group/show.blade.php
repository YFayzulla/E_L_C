@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
{{--select delete--}}

        <form action="{{route('deleteMultiple')}}" method="post">
        <button type="button" id="selectAllBtn" class="btn btn-primary mb-3 me-1">Select All</button>
        <button class="btn btn-danger mb-3 text-white">Delete specified data</button>
        <a href="{{ URL::to('/assessment/pdf',$id)}}" class="btn btn-danger mb-3 float-end"> Report </a>
            @csrf
            @method('DELETE')
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

                    @foreach($groups as $group)
                        <tr>
                            <td>
                                <input type="checkbox" class="checkbox" name="selectedItems[]"
                                       value="{{ $group->id }}">
                            </td>
                            <th>{{$loop->index+1}}</th>
                            <th>{{$group->student->name}}</th>
                            <th>{{$group->get_mark}}</th>
                            <th>{{$group->for_what}}</th>
                            <th>{{$group->rec_group}}</th>
                            <th>

                                <button type="button" class="btn-outline-success btn m-2" data-bs-toggle="modal"
                                        data-bs-target="#exampleModal{{$group->user_id}}" data-bs-whatever="@mdo"
                                > xulosa
                                </button>
                                {{--Modal--}}
                                <div class="modal fade" id="exampleModal{{$group->user_id}}" tabindex="-1"
                                     aria-labelledby="exampleModalLabel"
                                     aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                            </div>
                                            <div class="modal-body">
                                                <form action="{{route('student.change.group',$group->user_id)}}"
                                                      method="post">
                                                    @csrf
                                                    <label for="recipient-name"
                                                           class="col-form-label"> boshqa guruhga o`tirish </label>
                                                    <select name="group_id" class="form-control">
                                                        @foreach($guruxlar as $g)
                                                            <option value="{{$g->id}}">{{$g->name}}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-outline-success m-2">save
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </th>
                        </tr>
                    @endforeach
                </table>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            $("#selectAllBtn").click(function () {
                $(".checkbox").prop("checked", true);
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

@endsection
