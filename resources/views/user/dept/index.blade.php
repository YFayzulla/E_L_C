@extends('template.master')
@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Payment</h5>
            <div class="dt-action-buttons text-end pt-3 pt-md-0">
                <div class="dt-buttons btn-group flex-wrap">
                    <div class="btn-group">
                        <a class="btn buttons-collection dropdown-toggle btn-label-primary me-2" tabindex="0"
                           aria-controls="DataTables_Table_0" type="button" id="dropdownMenuButton"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span><i class="bx bx-export me-sm-1"></i> <span
                                    class="d-none d-sm-inline-block">Export</span></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="{{URL::to('/student/payment/phdf') }}"><i
                                        class="bx bxs-file-pdf me-1"></i> Pdf</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-xl">
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <td>No</td>
                        <td>name</td>
                        <td>group</td>
                        <td>payment</td>
                        <td>status</td>
                        <td>pay</td>
                    </tr>
                    </thead>

                    @foreach($students as $student)
                        <tbody id="myTable" class="table-group-divider">
                        <tr>
                            <th>{{$loop->index+1}}</th>
                            <th>{{$student->name}}</th>
                            <th>{{($student->group->name == 'waiters')?'Waiting room' : $student->group->name }}</th>
                            <th>{{ is_numeric($student->should_pay) ? number_format($student->should_pay, 0, '', ' ') : "payment didnt call" }}</th>
                            <th>@if( $student->status <= 0 )
                                    <p class="text-danger"> debtor </p>
                                @else
                                    <p class="text-success"> paid </p>
                                @endif</th>
                            <th>
                                <a class="btn btn-outline-primary m-1"
                                   href="{{ route('student.show',$student->id) }}"><i
                                        class="bx bx-show-alt"></i></a>

                                <button type="button" class="btn-outline-success btn m-2" data-bs-toggle="modal"
                                        data-bs-target="#exampleModal{{$student->id}}" data-bs-whatever="@mdo"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                         class="bi bi-coin" viewBox="0 0 16 16">
                                        <path
                                            d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9H5.5zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518l.087.02z"/>
                                        <path
                                            d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path
                                            d="M8 13.5a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11zm0 .5A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"/>
                                    </svg>
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
                                                <form action="{{route('dept.update',$student->id)}}" method="post">
                                                    @csrf
                                                    @method('PUT')
                                                    {{--                                                    <div class="table-responsive">--}}
                                                    <label for="recipient-name"
                                                           class="col-form-label">{{$student->name}} is going to pay,
                                                        monthly payment {{$student->should_pay}}</label>
                                                    <P>@if(!empty($student->studentdept->payed))
                                                            paid {{$student->studentdept->payed}} this
                                                            date {{$student->studentdept->date}}
                                                    @else


                                                    @endif
                                                    @if($student->status < 0)
                                                        <p>
                                                            the student has a debt of {{abs($student->status)}} month
                                                        </p>
                                                    @endif
                                                    {{--                                                    </div>--}}
                                                    <div class="mb-3 d-flex">
                                                        <input type="number" class="form-control me-1"
                                                               value="@if($student->studentdept->payed != null){{$student->studentdept->dept  -  $student->studentdept->payed}}@endif"
                                                               name="payment"
                                                               id="recipient-name">
                                                        <select name="money_type" id=""
                                                                class="form-select me-1">
                                                            <option value="cash">cash</option>
                                                            <option value="electronic">electronic</option>
                                                        </select>
                                                        <input type="date" class="form-control" name="date_paid"
                                                               id="recipient-name">
                                                        <button type="submit"
                                                                class="btn btn-outline-primary m-2">
                                                            save
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </th>
                        </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection
