@extends('user.master')
@section('content')
    <div class="card">
        <div class="card-header">Klinikani yangilash</div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('clinic.update', $clinic->id) }}">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Klinika nomi</label>
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
                                placeholder="Yulduz ko'z klinikasi"
                                aria-label="Yulduz ko'z klinikasi"
                                aria-describedby="basic-icon-default-fullname2"
                                value="{{ $clinic->name }}"
                            />
                        </div>
                        @error('name')
                        <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                        @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Klinika manzili</label>
                    <div class="col-sm-10">
                        <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-map"></i
                    ></span>
                            <input
                                name="address"
                                type="text"
                                class="form-control"
                                id="basic-icon-default-fullname"
                                placeholder="Gastronom orqa tomoni"
                                aria-label="Gastronom orqa tomoni"
                                aria-describedby="basic-icon-default-fullname2"
                                value="{{ $clinic->address }}"
                            />
                        </div>
                        @error('address')
                        <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                        @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Klinika telefon raqami</label>
                    <div class="col-sm-10">
                        <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-phone"></i
                    ></span>
                            <input
                                name="phone"
                                type="text"
                                class="form-control"
                                id="basic-icon-default-fullname"
                                placeholder="+998 91 277 96 93"
                                aria-label="+998 91 277 96 93"
                                aria-describedby="basic-icon-default-fullname2"
                                value="{{$clinic->phone}}"
                            />
                        </div>
                        @error('phone')
                        <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                        @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">URL</label>
                    <div class="col-sm-10">
                        <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                ><i class="bx bx-link"></i
                    ></span>
                            <input
                                name="url"
                                type="text"
                                class="form-control"
                                id="basic-icon-default-fullname"
                                placeholder="http://amusoft.uz"
                                aria-label="http://amusoft.uz"
                                aria-describedby="basic-icon-default-fullname2"
                                value="{{$clinic->url}}"
                            />
                        </div>
                        @error('url')
                        <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                        @enderror
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Manzilni xaritadan tanlang</label>
                    <div class="col-sm-10">
                        <div class="input-group input-group-merge">
                            <a
                                class="btn btn-info"
                                onclick="initModal()"
                                data-bs-toggle="modal"
                                data-bs-target="#modalMap"
                            >
                                <i class="bx bx-map"></i>
                                Manzilni tanlash
                            </a>
                        </div>
                        @error('url')
                        <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">O'zgartirish</button>
            </form>
        </div>
    </div>
@endsection
