@section('content')
    @extends('template.master')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">


        <div class="max-w-xl">
            <h1 class="text-center"> Create New Teacher </h1>

            <form action="{{route('teacher.store')}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="mt-2">
                    <label for="name" class="text-dark">Name</label>
                    <input id="name" name="name" value="{{old('name')}}" type="text" class="form-control">
                </div>
                @error('name')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror
                <div class="mt-2">
                    <label for="password" class="text-dark">Password</label>
                    <input id="password" name="password" value="{{old('password')}}" type="password"
                           class="form-control">
                </div>
                @error('password')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror

                <div class="mt-2">
                    <label for="phone" class="text-dark">Number</label>
                    <input id="phone" name="phone" value="{{old('phone')}}" type="text" class="form-control"
                           placeholder="+998(__)_______">
                </div>
                @error('phone')
                <div class="alert alert-danger" role="alert">This place should be written and
                    not repeated!
                </div>
                @enderror
                <div class="mt-2">
                    <label for="date_born" class="text-dark">Date born</label>
                    <input id="date_born" name="date_born" value="{{old('date_born')}}" type="date"
                           class="form-control">
                </div>
                @error('date_born')
                <div class="alert alert-danger" role="alert">This place should be written</div>
                @enderror
                <div class="mt-2">
                    <label for="location" class="text-dark">Location</label>
                    <input id="location" name="location" value="{{old('location')}}" type="text" class="form-control">
                </div>
                @error('location')
                <div class="alert alert-danger" role="alert">This place should be written!</div>
                @enderror
                <div class="mt-2">
                    <label for="passport" class="text-dark">Passport </label>
                    <input id="passport" name="passport" value="{{old('passport')}}" type="text" class="form-control">
                </div>
                {{--                @error('passport')--}}
                {{--                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>--}}
                {{--                @enderror--}}

                <div class="mt-2">
                    <label for="photo" class="text-dark"> Photo</label>
                    <input id="photo" name="photo" value="{{old('photo')}}" type="file" class="form-control">
                </div>
                @error('photo')
                <div class="alert alert-danger" role="alert">should upload photo!</div>
                @enderror
                <div class="mt-2">
                    <button class="btn btn-warning m-4 "> Submit</button>
                </div>
            </form>
        </div>
    </div>

@endsection
