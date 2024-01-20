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
            <table class="table table-bordered">
                <tr>
                    <th>no</th>
                    <th>ismi</th>
                    <th>ttelefon raqam</th>
                    <th>tolangan pullar</th>
                    <th>sana</th>
                </tr>
                {{$sum=0}}
                @foreach($users as $student)
                    <tr>

                        <th>{{$loop->index+1}}</th>
                        <th>{{$student->student->name}}</th>
                        <th>{{$student->student->phone}}</th>
                        <th>{{$student->payment}}</th>
                        <th>{{$student->date}}</th>
                    </tr>
                    @php
                    $sum+= $student->payment
                    @endphp
                @endforeach
            </table>
            <p>shu kunda to`langan umumiy summa : {{$sum}} </p>
        </div>
    </div>
</div>
</body>
</html>
