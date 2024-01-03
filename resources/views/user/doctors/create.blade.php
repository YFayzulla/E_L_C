@extends('user.master')
@section('content')
    <!-- Basic with Icons -->
    <div class="col-xxl">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Malumot qo'shish</h5>
                <small class="text-muted float-end">Malumotlarni to'ldirishingiz mumkin.</small>
            </div>
            <div class="card-body demo-vertical-spacing demo-only-element">
                <form method="post" action="{{ route('doctor.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Doktor ismi</label>
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
                                    value="{{old('name')}}"
                                />
                            </div>
                            @error('name')
                            <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Doktor familiyasi</label>
                        <div class="col-sm-10">
                            <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-clinic"></i
                    ></span>
                                <input
                                    name="surname"
                                    type="text"
                                    class="form-control"
                                    id="basic-icon-default-fullname"
                                    aria-describedby="basic-icon-default-fullname2"
                                    value="{{old('surname')}}"
                                />
                            </div>
                            @error('surname')
                            <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">passport seriya</label>
                        <div class="col-sm-10">
                            <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-clinic"></i
                    ></span>
                                <input
                                    name="passport"
                                    type="text"
                                    class="form-control"
                                    id="basic-icon-default-fullname"
                                    aria-describedby="basic-icon-default-fullname2"
                                    value="{{old('passport')}}"
                                />
                            </div>
                            @error('passport')
                            <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Telefon raqam</label>
                        <div class="col-sm-10">
                            <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-clinic"></i
                    ></span>
                                <input
                                    name="phone"
                                    type="text"
                                    class="form-control"
                                    id="basic-icon-default-fullname"
                                    aria-describedby="basic-icon-default-fullname2"
                                    value="{{old('phone')}}"
                                />
                            </div>
                            @error('phone')
                            <div class="alert alert-danger" role="alert">Telefon raqam togri toldirilishi kerak</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Parol</label>
                        <div class="col-sm-10">
                            <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-clinic"></i
                    ></span>
                                <input
                                    name="password"
                                    type="text"
                                    class="form-control"
                                    id="basic-icon-default-fullname"
                                    aria-describedby="basic-icon-default-fullname2"
                                    value="{{old('password')}}"
                                />
                            </div>
                            @error('password')
                            <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Doktor rasimi</label>
                        <div class="col-sm-10">
                            <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-clinic"></i
                    ></span>
                                <input
                                    name="file"
                                    type="file"
                                    class="form-control"
                                    id="basic-icon-default-fullname"
                                    aria-describedby="basic-icon-default-fullname2"
                                />
                            </div>

                            @error('image')
                            <div class="alert alert-danger" role="alert">rasim yuklash majburiy</div>
                            @enderror

                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">xona tanlang</label>
                        <div class="col-sm-10">
                            <div class="input-group select2-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-clinic"></i
                    ></span>
                                <select
                                    name="room_id"
                                    type="text"
                                    class="form-control"
                                    id="basic-icon-default-fullname"
                                    aria-describedby="basic-icon-default-fullname2"
                                    value="{{old('name')}}"
                                >
                                    @foreach($rooms as $room )

                                        <option value="{{$room->id}}">{{$room->name}} </option>

                                    @endforeach

                                </select>
                            </div>
                            @error('room_id')
                            <div class="alert alert-danger" role="alert">Xona mavjud  emas!</div>
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
@endsection
