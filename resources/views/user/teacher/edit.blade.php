@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="max-w-xl">
            <h1 class="text-center"> Edit teacher`s data </h1>

            <form action="{{route('teacher.update',$teacher->id)}}" method="post" enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <label for="name" class="text-dark">Name</label>
                <input id="name" name="name" value="{{$teacher->name}}" type="text" class="form-control">

                @error('name')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="password" class="text-dark">password</label>
                <input id="password" name="password" type="password"  class="form-control">

                @error('password')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror
                <label for="phone" class="text-dark">Phone</label>
                <input id="phone" name="phone" value="{{$teacher->phone}}" type="text" class="form-control">

                @error('phone')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="date_born" class="text-dark">Date born</label>
                <input id="date_born" name="date_born" value="{{$teacher->date_born}}" type="date" class="form-control">

                @error('date_born')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="location" class="text-dark">Location</label>
                <input id="location" name="location" value="{{$teacher->location}}" type="text" class="form-control">

                @error('location')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="passport" class="text-dark">Passport</label>
                <input id="passport" name="passport" value="{{$teacher->passport}}" type="text" class="form-control" >

                {{--                @error('passport')--}}
                {{--                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>--}}
                {{--                @enderror--}}


                <label for="photo" class="text-dark"> Photo</label>
                <input id="photo" name="photo" value="{{old('photo')}}" type="file" class="form-control">



                <button class="btn btn-warning m-4 "> submit</button>

            </form>
        </div>
    </div>

@endsection
