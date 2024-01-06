@section('content')
@extends('template.master')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="max-w-xl">
            <h1 class="text-center"> Guruh qo`shish </h1>

            <form action="{{route('group.store')}}" method="post" enctype="multipart/form-data">
                @csrf

                <label for="name" class="text-dark">Guruh nomi</label>
                <input id="name" name="name" value="{{old('name')}}" type="text" class="form-control">

                @error('name')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="start_time" class="text-dark">Guruhning boshlanish vaqti</label>
                <input id="start_time" name="start_time" value="{{old('start_time')}}" type="text" class="form-control">

                @error('start_time')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="finish_time" class="text-dark">Guruhning tugash vaqti</label>
                <input id="finish_time" name="finish_time" value="{{old('finish_time')}}" type="text" class="form-control">

                @error('finish_time')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="monthly_payment" class="text-dark">Guruh narxi</label>
                <input id="monthly_payment" name="monthly_payment" value="{{old('monthly_payment')}}" type="text" class="form-control">

                @error('monthly_payment')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <button class="btn btn-warning m-4 "> Yuklash </button>

            </form>
        </div>
    </div>

@endsection
