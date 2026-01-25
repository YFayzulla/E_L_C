@extends('template.master')

@section('content')
    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-xl">
            <h1 class="text-center">Create Group</h1>

            <form action="{{ route('group.store') }}" method="post" enctype="multipart/form-data"
                  onsubmit="formatBeforeSubmit()">
                @csrf

                <label for="name" class="text-dark">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" type="text" class="form-control" required>
                @error('name')
                <div class="alert alert-danger" role="alert">This field is required</div>
                @enderror

                <label for="start_time" class="text-dark">Start Time</label>
                <input type="text" id="start_time" name="start_time" value="{{ old('start_time') }}"
                       class="form-control">
                @error('start_time')
                <div class="alert alert-danger" role="alert">This field is required</div>
                @enderror

                <label for="finish_time" class="text-dark">Finish Time</label>
                <input id="finish_time" type="text" name="finish_time" value="{{ old('finish_time') }}"
                       class="form-control">
                @error('finish_time')
                <div class="alert alert-danger" role="alert">This field is required</div>
                @enderror

                <label for="monthly_payment" class="text-dark">Cost</label>
                <input id="monthly_payment" name="monthly_payment" type="text"
                       class="form-control" required oninput="formatNumber(this)">
                @error('monthly_payment')
                <div class="alert alert-danger" role="alert">This field is required</div>
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

        // function timeInputHandler(e) {
        //     let input = e.target;
        //     let value = input.value;
        //
        //     // Basic formatting: allow only numbers, add colon, limit length
        //     value = value.replace(/[^0-9:]/g, '').replace(/(\d{2})(\d)/, '$1:$2').slice(0, 5);
        //
        //     // Validate hour part
        //     if (value.length >= 2) {
        //         let hour = value.slice(0, 2);
        //         if (parseInt(hour, 10) > 23) {
        //             value = '23' + value.slice(2);
        //         }
        //     }
        //     // Validate minute part
        //     if (value.length === 5) {
        //         let minute = value.slice(3, 5);
        //         if (parseInt(minute, 10) > 59) {
        //             value = value.slice(0, 3) + '59';
        //         }
        //     }
        //
        //     input.value = value;
        //
        // }
    </script>
@endsection
