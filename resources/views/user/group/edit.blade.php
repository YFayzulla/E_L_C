@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="max-w-xl">
            <h1 class="text-center"> Edit group`s date </h1>

            <form action="{{route('group.update',$group->id)}}" method="post" enctype="multipart/form-data">

                @csrf
                @method('PUT')
                <label for="name" class="text-dark">name</label>
                <input id="name" name="name" value="{{old('name')??$group->name}}" type="text" class="form-control">

                @error('name')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="start_time" class="text-dark">start time</label>
                <input id="start_time" name="start_time" value="{{$group->start_time}}" type="text"
                       class="form-control">

                @error('start_time')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="finish_time" class="text-dark">finish time</label>
                <input id="finish_time" name="finish_time" value="{{$group->finish_time}}" type="text"
                       class="form-control">

                @error('finish_time')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="monthly_payment" class="text-dark">cost</label>
                <input id="monthly_payment" name="monthly_payment" value="{{$group->monthly_payment}}" type="number"
                       class="form-control">

                @error('monthly_payment')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="level" class="text-dark">Level</label>
                <select name="level" class="form-control" id="">
                    @foreach($level as $l)
                        <option value="{{$l->name}}">{{$l->name}}</option>
                    @endforeach
                </select>

                @error('level')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <button class="btn btn-warning m-4 "> submit</button>

            </form>
        </div>
    </div>

@endsection
