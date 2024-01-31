@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">

        <button class="btn btn-warning" data-toggle="modal" data-target="#exampleModal">add group</button>
        {{-- modal --}}
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{route('teacher_group.store',$id)}}" method="post">
                            @csrf
                            @method('PUT')
                            <select class="form-control" name="group_id">
                                @foreach($groups as $group)
                                    <option value="{{$group->id}}">{{$group->name}}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary mt-2">submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden select area initially -->
        <table class="table">
            <tr>
                <th>No</th>
                <th> Group </th>
                <th> Delete</th>
            </tr>

            @foreach($teachers as $teacher)
                <tr>
                    <th>{{$loop->index+1}}</th>
                    <th>{{$teacher->group->name}}</th>
                    <td>
                        <form action="{{route('teacher_group.delete',$teacher->id)}}" method="post"
                              onsubmit="return confirm('are you sure for deleting ');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="" class="btn-outline-danger btn ">
                                <i class='bx bx-trash-alt' ></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach

        </table>

@endsection
