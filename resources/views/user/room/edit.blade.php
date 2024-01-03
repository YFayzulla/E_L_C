@extends('user.master')
@section('content')
    <!-- Basic with Icons -->
    <div class="col-xxl">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Xona qo'shish</h5>
                <small class="text-muted float-end">Malumotlarni to'ldirishingiz mumkin.</small>
            </div>
            <div class="card-body demo-vertical-spacing demo-only-element">

                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('room.update',$room->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Xona nomi yoki soni</label>
                        <div class="col-sm-10">
                            <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-clinic"></i
                    ></span>
                                <input
                                    name="name"
                                    type="text"
                                    class="form-control"
                                    id="basic-icon-default-fullname"
                                    aria-describedby="basic-icon-default-fullname2"
                                    value="{{$room->name}}"
                                />
                            </div>
                            @error('name')
                            <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Xona nomi yoki soni</label>
                        <div class="col-sm-10">
                            <div class="input-group select2-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-clinic"></i
                    ></span>
                                <select
                                    name="clinic_id"
                                    type="text"
                                    class="form-control"
                                    id="basic-icon-default-fullname"
                                    aria-describedby="basic-icon-default-fullname2"
                                    value="{{old('name')}}"
                                >
                                    @foreach($clinics as $clinic )

                                        <option class="" value="{{$clinic->id}}">{{$clinic->name}} </option>

                                    @endforeach

                                </select>
                            </div>
                            @error('clinic_id')
                            <div class="alert alert-danger" role="alert">klinika mavjud  emas!</div>
                            @enderror
                        </div>
                    </div>


                    <div class="row justify-content-end mb-5">
                        <div class="col-sm-10">
                            <button type="submit" class="btn btn-primary">Qo'shish</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="modalMap" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Manzilni tanlang</h5>
                </div>
                <div class="modal-body">
                    <div id="map"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Yopish
                    </button>
                    <button type="button" class="btn btn-primary">Saqlash</button>
                </div>
            </div>
        </div>
    </div>
@endsection
