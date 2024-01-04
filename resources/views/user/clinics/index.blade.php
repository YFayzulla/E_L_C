@php use Carbon\Carbon; @endphp
@extends('user.master')
@section('content')
    <!-- Striped Rows -->
    <div class="card">
        @unless(count($clinics) == 0)
            <div class="card-header">
                <div class="row content-end">
                    <div class="col-4">
                        <h5>Klinikalar ro'yxati</h5>
                    </div>
                    <div class="col-md-4 text-end offset-md-4">
                        <a href="{{ route('clinic.create') }}" class="btn-primary p-2 rounded">
                            Klinika qo'shish
                        </a>
                    </div>
                </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success mt-3" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            <div class="table-responsive text-nowrap">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>T/R</th>
                        <th>Klinika nomi</th>
                        <th>Manzil</th>
                        <th>Telefon</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>
                            <div class="d-flex justify-content-center">
                                URL
                            </div>
                        </th>
                        <th>
                            <div class="d-flex justify-content-center">
                                Joylashuv
                            </div>
                        </th>
                        <th>
                            <div class="d-flex justify-content-center">
                                Amallar
                            </div>
                        </th>
                    </tr>
                    </thead>
                    @foreach ($clinics as $clinic)
                        <tbody class="table-border-bottom-0">
                            <tr>
                                <td><i class="fab fa-angular fa-lg text-danger"></i>{{ $loop->iteration }}</td>
                                <td><i class="fab fa-angular fa-lg text-danger"></i> <strong>{{ $clinic->name }}</strong></td>
                                <td><i class="fab fa-angular fa-lg text-danger"></i>{{ $clinic->address }}</td>
                                <td><i class="fab fa-angular fa-lg text-danger"></i>{{ $clinic->phone }}</td>
                                <td><i class="fab fa-angular fa-lg text-danger"></i>{{ $clinic->status }}</td>
                                <td><i class="fab fa-angular fa-lg text-danger"></i>{{ $clinic->plan_id }}</td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a class="btn btn-warning me-2" href="{{ $clinic->url }}"><i
                                                class="bx bx-link me-1"></i> URL</a>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a class="btn btn-warning me-2" style="color: white"><i
                                                class="bx bx-current-location me-1"></i> Joylashuv</a>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a class="btn btn-warning me-2" href="{{ route('room.index') }}"><i
                                                class="bx bx-show-alt me-1"></i></a>
                                    <a class="btn btn-warning me-2" href="{{ route('clinic.edit', $clinic->id) }}"><i
                                                                                    class="bx bx-edit-alt me-1"></i></a>

                                        <form action="{{ route('clinic.delete', $clinic->id) }}" method="POST"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Klinikani o\'chirishni xohlaysizmi?')">
                                                <i class="bx bx-trash me-1"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
                @else
                    <div class="card-header">
                        <div class="row content-end">
                            <div class="col-4">
                                <h5>Klinika mavjud emas</h5>
                            </div>
                            <div class="col-md-4 text-end offset-md-4">
                                <a href="{{ route('clinic.create') }}" class="btn-primary p-2 rounded">
                                    Klinika qo'shish
                                </a>
                            </div>
                        </div>
                    </div>
                @endunless
            </div>
    </div>
    <!--/ Striped Rows -->
@endsection
