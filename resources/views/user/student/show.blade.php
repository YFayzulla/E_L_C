@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <div class="max-w-xl mx-auto">
            <h1 style="text-align: center">O`quvchi malumotlari</h1>
            <h3><b>F.I.O </b>{{$student->name}}</h3>
            <h3><b>Yashash manzili</b>  {{$student->location}}</h3>
            <h3><b>Telefon raqami </b>{{$student->phone}}</h3>

            <h4><b>Ota-Onasi: </b>{{$student->parents_name}},Tel raqami {{$student->parents_tel}} </h4>
            <h4><b>Qoshimcha malumotlar:</b> {{($student->description)}}</h4>
            @foreach($student->studentinformation as $inform)
                <tr>
                    <td> <h5>{{$loop->index+1}} - bosqich: Guruh: {{$inform->group->name}} status:{{$inform->level}} date {{$inform->created_at}}</h5> </td>
                </tr>
            @endforeach
            <img src="{{asset( 'storage/'.$student->photo) }}"
                 style="width: 300px; display: block; margin-left: auto;"
                 alt="internet bilan muammo bor">
        </div>
    </div>
@endsection
