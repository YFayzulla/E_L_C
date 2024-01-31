@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <div class="max-w-xl mx-auto">
            <a class="btn btn-danger float-right m-2" href="{{ URL::to('/student/pdf',$student->id) }}">Report</a>
            <div class="container" style="display: flex; justify-content: space-between;">
                <div class="container__left">
                    <h1 style="text-align: center">O`quvchi malumotlari</h1>
                    <h3><b>F.I.O </b>{{$student->name}}</h3>
                    <h3><b>Yashash manzili</b> {{$student->location}}</h3>
                    <h3><b>Telefon raqami </b>{{$student->phone}}</h3>

                    <h4><b>Ota-Onasi: </b>{{$student->parents_name}},Tel raqami {{$student->parents_tel}} </h4>
                    <h4><b>Qoshimcha malumotlar:</b> {{($student->description)}}</h4>
                    <h3 style="text-align: center"></h3>

{{--                    <table class="table">--}}
{{--                        <tr>--}}
{{--                            <th>id</th>--}}
{{--                            <th>name</th>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <th>salom</th>--}}
{{--                        </tr>--}}
{{--                    </table>--}}



                    <table class="table">

                        <th>No</th>
                        <th>tolagan summa</th>
                        <th>to`langan sana</th>

                        @foreach($student->studenthistory as $item)
                            <tr>
                                <th>{{$loop->index+1}}</th>
                                <th>{{$item->payment}}</th>
                                <th>@if($item->date ==null)
                                        {{$item->created_at.'data'}}
                                    @else
                                        {{$item->date}}
                                    @endif</th>
                            </tr>
                        @endforeach
                    </table>

                    <p>Davomat</p>

                    <table class="table">
                        <tr>
                            <th> guruhi</th>
                            <th> O`qituvchisi</th>
                            <th> sana</th>

                        </tr>
                        @foreach($attendances as $attendance)
                            <tr>
                                <th>{{$attendance->student->name}}</th>
                                <th>{{$attendance->group->name}}</th>
                                <th>{{$attendance->created_at}}</th>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div class="container__right" style="max-width: 300px; margin-top: 20px;">
                    <img src="{{asset( 'storage/'.$student->photo) }}"
                         style="width: 200px; display: block; margin-left: auto;"
                         alt="internet bilan muammo bor">
                </div>
            </div>
        </div>
    </div>
@endsection
