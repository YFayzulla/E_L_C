@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">

    <button id="toggleButton">Toggle Select Area</button>

    <!-- Hidden select area initially -->
    <select id="hiddenSelect">
        <option value="option1" type="hidden">Option 1</option>
        <option value="option2">Option 2</option>
        <option value="option3">Option 3</option>
    </select>
    </div>
    <script>
        $(document).ready(function() {
            // Function to toggle the visibility of the select area
            $("#toggleButton").click(function() {
                $("#hiddenSelect").toggle();
            });
        });
    </script>

@endsection
