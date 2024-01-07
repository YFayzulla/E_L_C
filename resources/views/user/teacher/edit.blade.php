@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="max-w-xl">
            <h1 class="text-center"> O`qituvchi malumotlarini o`zgartirish </h1>

            <form action="{{route('teacher.update',$teacher->id)}}" method="post" enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <label for="name" class="text-dark">Ismi</label>
                <input id="name" name="name" value="{{$teacher->name}}" type="text" class="form-control">

                @error('name')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="password" class="text-dark">Parol</label>
                <input id="password" name="password" type="password" class="form-control">

                @error('password')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror
                <label for="phone" class="text-dark">Telefon raqam</label>
                <input id="phone" name="phone" value="{{$teacher->phone}}" type="text" class="form-control">

                @error('phone')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas va yoki kiritilgan raqam takrorlangan</div>
                @enderror


                <label for="passport" class="text-dark">Pasport seria</label>
                <input id="passport" name="passport" value="{{$teacher->passport}}" type="text" class="form-control" >

                {{--                @error('passport')--}}
                {{--                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>--}}
                {{--                @enderror--}}


                <label for="photo" class="text-dark"> Rasim</label>
                <input id="photo" name="photo" value="{{old('photo')}}" type="file" class="form-control">

                @error('photo')
                <div class="alert alert-danger" role="alert">rasm yuklanish shart!</div>
                @enderror

                <button class="btn btn-warning m-4 "> Yuklash</button>

            </form>
        </div>
    </div>

@endsection
