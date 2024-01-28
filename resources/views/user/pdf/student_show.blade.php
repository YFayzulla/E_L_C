<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
<div class="p-4 m-4 sm:p-8 bg-white shadow sm:rounded-lg ">
    <div class="max-w-xl mx-auto">
        <div class="container" style="display: flex; justify-content: space-between;">
            <div class="container__left">
                <h1 style="text-align: center">O`quvchi malumotlari</h1>
                <h3><b>F.I.O </b>{{$student->name}}</h3>
                <h3><b>Yashash manzili</b> {{$student->location}}</h3>
                <h3><b>Telefon raqami </b>{{$student->phone}}</h3>

                <h4><b>Ota-Onasi: </b>{{$student->parents_name}},Tel raqami {{$student->parents_tel}} </h4>
                <h4><b>Qoshimcha malumotlar:</b> {{($student->description)}}</h4>
                <h3 style="text-align: center"></h3>
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

                <table class="table">
                    <tr>
                        <th>O`qituvchi</th>
                        <th>o`qigan guruh</th>
                        <th>olgan baxosi</th>
                    </tr>
                    @foreach($student->assessment as $assessment)
                        <tr>
                            <th>{{$assessment->teacher}}</th>
                            <th>{{$assessment->group}}</th>
                            <th>{{$assessment->get_mark}}</th>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

    </div>
</div>
</body>
</html>
