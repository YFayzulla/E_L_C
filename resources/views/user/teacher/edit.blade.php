@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="max-w-xl">
            <h1 class="text-center"> O`qituvchi malumotlarini o`zgartirish </h1>

            <form action="{{route('teacher.store')}}" method="post" enctype="multipart/form-data">
                @csrf

                <label for="name" class="text-dark">Ismi</label>
                <input id="name" name="name" value="{{old('name')}}" type="text" class="form-control">

                @error('name')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="password" class="text-dark">Parol</label>
                <input id="password" name="password" value="{{old('password')}}" type="text" class="form-control">

                @error('password')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="passport" class="text-dark">Pasport seria</label>
                <input id="passport" name="passport" value="{{old('passport')}}" type="text" class="form-control">

                {{--                @error('passport')--}}
                {{--                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>--}}
                {{--                @enderror--}}

                <label for="phone" class="text-dark">Telefon raqam</label>
                <input id="phone" name="phone" value="{{old('phone')}}" type="text" class="form-control">

                @error('phone')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="parents_name" class="text-dark">Ota-onasining ismi</label>
                <input id="parents_name" name="parents_name" value="{{old('parents_name')}}" type="text"
                       class="form-control">

                @error('parents_name')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="parents_tel" class="text-dark">Ota-onasining Telefon raqami</label>
                <input id="parents_tel" name="parents_tel" value="{{old('parents_tel')}}" type="text"
                       class="form-control">

                @error('parents_tel')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

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
