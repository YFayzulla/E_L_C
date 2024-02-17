@section('content')
@extends('template.master')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="max-w-xl">
            <h1 class="text-center"> Create student </h1>

            <form action="{{route('student.store')}}" method="post" enctype="multipart/form-data">
                @csrf

                <label for="name" class="text-dark">Name</label>
                <input id="name" name="name" value="{{old('name')}}" type="text" class="form-control">

                @error('name')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="passport" class="text-dark"> Passport </label>
                <input id="passport" name="passport" value="{{old('passport')}}" type="text" class="form-control">


                <label for="phone" class="text-dark">Tel</label>
                <input id="phone" name="phone" value="{{old('phone')}}" type="text" class="form-control" placeholder="+998(__)_______">

                @error('phone')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="location" class="text-dark">Location</label>
                <input id="location" name="location" value="{{old('location')}}" type="text" class="form-control">

                @error('location')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="parents_name" class="text-dark">Parents name</label>
                <input id="parents_name" name="parents_name" value="{{old('parents_name')}}" type="text" class="form-control">

                @error('parents_name')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="parents_tel" class="text-dark">Parents tel</label>
                <input id="parents_tel" name="parents_tel" value="{{old('parents_tel')}}" type="text" class="form-control">

                @error('parents_tel')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="should_pay" class="text-dark">Should Pay</label>
                <input id="should_pay" name="should_pay" value="{{old('should_pay')}}" type="number" class="form-control">

                <label for="description" class="text-dark">Description "not necessary"</label>
                <input id="description" name="description" value="{{old('description')}}" type="text" class="form-control">

                <label for="group_id" class="text-dark">Group</label>
                <select class="form-control" name="group_id" >
                    @foreach($groups as $group)
                    <option value="{{$group->id}}">{{$group->name}}</option>
                    @endforeach
                </select>

                @error('group_id')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror


                <label for="photo" class="text-dark"> Photo </label>
                <input id="photo" name="photo" value="{{old('photo')}}" type="file" class="form-control">

                <button type="submit" class="btn btn-warning m-4 "> submit </button>

            </form>
        </div>
    </div>

@endsection
