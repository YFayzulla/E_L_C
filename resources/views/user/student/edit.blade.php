@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="max-w-xl">
            <h1 class="text-center"> Edit student`s data </h1>

            <form action="{{route('student.update',$student->id)}}" method="post" enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <label for="name" class="text-dark">name</label>
                <input id="name" name="name" value="{{$student->name}}" type="text" class="form-control">

                @error('name')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror


                <label for="phone" class="text-dark">Tel</label>
                <input id="phone" name="phone" value="{{$student->phone}}" type="text" class="form-control">

                @error('phone')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror


                <label for="passport" class="text-dark">Passport</label>
                <input id="passport" name="passport" value="{{$student->passport}}" type="text" class="form-control">



                <label for="parents_name" class="text-dark">Parents name</label>
                <input id="parents_name" name="parents_name" value="{{$student->parents_name}}" type="text"
                       class="form-control">

                @error('parents_name')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                @enderror

                <label for="parents_tel" class="text-dark">Parents tel</label>
                <input id="parents_tel" name="parents_tel" value="{{$student->parents_tel}}" type="text"
                       class="form-control">

                @error('parents_tel')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="location" class="text-dark">Location</label>
                <input id="location" name="location" value="{{$student->location}}" type="text" class="form-control">

                @error('location')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="description" class="text-dark">Description</label>
                <input id="description" name="description" value="{{$student->description}}" type="text"
                       class="form-control">

                <label for="should_pay" class="text-dark">Should pay</label>
                <input id="should_pay" name="should_pay" value="{{$student->should_pay}}" type="text"
                       class="form-control">

                <label for="should_pay" class="text-dark">Group</label>

                <select class="form-control" name="group_id">
                    @foreach($groups as $group)
                        <option value="{{$group->id}}">{{$group->name}}</option>
                    @endforeach
                </select>


                @error('should_pay')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <label for="photo" class="text-dark"> Photo </label>
                <input id="photo" name="photo" value="{{old('photo')}}" type="file" class="form-control">


                <button class="btn btn-warning m-4 "> Submit </button>

            </form>
        </div>
    </div>

@endsection
