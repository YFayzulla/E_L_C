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

                    <p>Yakuniy nazorat</p>

                    <table class="table">
                        <tr>
                            <th>sana</th>
                            <th>o`qigan guruh</th>
                            <th>olgan baxosi</th>
                            <th>malumot</th>
                            <th>tafsiya qilingan gurux</th>
                        </tr>
                        @foreach($student->assessment as $assessment)
                            <tr>
                                <th>{{$assessment->created_at}}</th>
                                <th>{{$assessment->group}}</th>
                                <th>{{$assessment->get_mark}}</th>
                                <th>{{$assessment->for_what}}</th>
                                <th> @if($assessment->rec_group == 0) o`ta olmadi @else {{ $assessment->rec_group }} @endif</th>


                            </tr>
                        @endforeach
                    </table>
                                    <button type="button" class="btn-outline-success btn m-2 float-end" data-bs-toggle="modal"
                                            data-bs-target="#exampleModal{{$student->id}}" data-bs-whatever="@mdo"
                                    > xulosa
                                    </button>
                                    {{--Modal--}}
                                    <div class="modal fade" id="exampleModal{{$student->id}}" tabindex="-1"
                                         aria-labelledby="exampleModalLabel"
                                         aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                </div>
                                                <div class="modal-body">
                                                    <form action="{{route('student.change.group',$student->id)}}" method="post">
                                                        @csrf
                                                        <label for="recipient-name"
                                                               class="col-form-label"> boshqa guruhga o`tirish </label>
                                                        <select name="group" class="form-control">
                                                            @foreach($groups as $group)
                                                            <option value="{{$group->id}}">{{$group->name}}</option>
                                                            @endforeach
                                                        </select>

                                                        <button type="submit" class="btn btn-outline-primary m-2">save
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
