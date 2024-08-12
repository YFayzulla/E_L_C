`@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        {{--select delete--}}
{{--        <a href="{{ URL::to('/assessment/pdf',$id)}}" class="btn btn-danger mb-3 float-end"> Report </a>--}}

        <form action="{{route('deleteMultiple')}}" method="post">

            @csrf
            @method('DELETE')

            <button type="button" id="selectAllBtn" class="btn btn-primary mb-3 me-1">Select All</button>
            <button class="btn btn-danger mb-3 text-white">Delete specified data</button>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-active">

                    <TR>

                        <td>+</td>
                        <th>id</th>
                        <th>name</th>
                        <th>GOT MARK</th>
                        <th>information</th>
                        <th>rec group</th>
                        <th>change group</th>

                    </TR>

                    </thead>
                    <tbody>



                    </tbody>
                </table>
            </div>
        </form>
    </div>



    <script>
        $(document).ready(function () {
            $("#selectAllBtn").click(function () {
                $(".checkbox").prop("checked", true);
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

@endsection
`
