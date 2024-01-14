@extends('template.master')
@section('content')

    <div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
        <div class="max-w-xl mx-auto">
            <h1 style="text-align: center">O`quvchi malumotlari</h1>
            <h3>F.I.O {{$student->name}}</h3>
            <h3>Yashash manzili {{$student->location}}</h3>
            <h3>Telefon raqami {{$student->phone}}</h3>

            <h4>Ota-Onasi: {{$student->parents_name}},Tel raqami {{$student->parents_tel}} </h4>
            <h4>Qoshimcha malumotlar: {{($student->studentinformation->description)}}</h4>
            <img src="{{asset( 'storage/'.$student->photo) }}"
                 style="width: 300px; display: block; margin-left: auto;"
                 alt="internet bilan muammo bor">
        </div>
    </div>
@endsection
