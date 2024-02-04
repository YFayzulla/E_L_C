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
<input type="hidden" value="{{$sum=0}}">
<div class="container" style="display: flex; justify-content: space-between;">
    <div class="table-responsive text-nowrap">
        <table class="table">
            <tr>
                <th>no</th>
                <th>name</th>
                <th>tel</th>
                <th>paid</th>
                <th>date</th>
            </tr>
            @foreach($users as $student)
                <tr>
                    <th>{{$loop->index+1}}</th>
                    <th>{{$student->student->name}}</th>
                    <th>{{$student->student->phone}}</th>
                    <th>{{$student->payment}}</th>
                    <th>{{$student->date}}</th>
                    @php
                        $sum += $student->payment
                    @endphp
                </tr>
            @endforeach
        </table>
    </div>
</div>
<p>Sum in date: {{$sum}} </p>
</body>
</html>
