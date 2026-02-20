@extends('template.master')
@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">

        <div class="max-w-xl">
            <h1 class="text-center"> Edit group's data </h1>

            <form action="{{route('group.update',$group->id)}}" method="post" enctype="multipart/form-data" onsubmit="formatBeforeSubmit()">

                @csrf
                @method('PUT')

                <label for="name" class="text-dark">Name</label>
                <input id="name" name="name" value="{{old('name')??$group->name}}" type="text" class="form-control">

                @error('name')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <label for="start_time" class="text-dark">Start Time</label>
                <input id="start_time" name="start_time" value="{{$group->start_time}}" type="text" class="form-control">

                @error('start_time')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <label for="finish_time" class="text-dark">Finish Time</label>
                <input id="finish_time" name="finish_time" value="{{$group->finish_time}}" type="text" class="form-control" >

                @error('finish_time')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <label for="monthly_payment" class="text-dark">Cost</label>
                <input id="monthly_payment" name="monthly_payment" value="{{ number_format($group->monthly_payment, 0, '', ' ') }}" type="text" class="form-control" oninput="formatNumber(this)">

                @error('monthly_payment')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <button class="btn btn-warning m-4">Submit</button>

            </form>
        </div>
    </div>

    <script>
        function formatNumber(input) {
            let value = input.value.replace(/\s+/g, '');
            if (!isNaN(value)) {
                input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }
        }

        function formatBeforeSubmit() {
            let input = document.getElementById('monthly_payment');
            input.value = input.value.replace(/\s+/g, '');
        }
    </script>
@endsection
