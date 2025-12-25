@extends('template.master')
@section('content')

    <div class="row">

        <div class="col-md-6 col-xl-6 mb-3">
            <a href="{{ route('group.show', 1) }}" class="text-decoration-none">
                <div class="card bg-primary text-white">
                    <div class="card bg-info text-white">
                        <div class="card-header">Room 1</div>
                        <div class="card-body">
                            <h5 class="card-title text-white"></h5>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-6 mb-3">
            <a href="{{ route('group.show', 2) }}" class="text-decoration-none">
                <div class="card bg-primary text-white">
                    <div class="card bg-success text-white">
                        <div class="card-header">Room 2</div>
                        <div class="card-body">
                            <h5 class="card-title text-white"></h5>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-6 mb-3">
            <a href="{{ route('group.show', 3) }}" class="text-decoration-none">
                <div class="card bg-primary text-white">
                    <div class="card bg-danger text-white">
                        <div class="card-header">Room 3</div>
                        <div class="card-body">
                            <h5 class="card-title text-white"></h5>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-6 mb-3">
            <a href="{{ route('group.show', 4) }}" class="text-decoration-none">
                <div class="card bg-primary text-white">
                    <div class="card-header">Room 4</div>
                    <div class="card-body">
                        <h5 class="card-title text-white"></h5>
                    </div>
                </div>
            </a>
        </div>

    </div>

@endsection
