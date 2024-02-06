@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <h3 class="text-center">Attendance</h3>

        <div class="table-responsive text-nowrap" style="margin-right: 150px;margin-left: 150px">


            <table class="table table-hover">
                <thead class="table-active">
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>Teacher</th>
                    <th>date</th>
                </tr>
                </thead>
                @foreach($items as $item)
                    <tr>
                        <th>{{$loop->index+1}}</th>
                        <th>{{$item->student->name}}</th>
                        <th>{{$item->teacher->name}}</th>
                        <th>{{$item->created_at}}</th>
                    </tr>

                @endforeach
            </table>



        </div>
    </div>
@endsection
