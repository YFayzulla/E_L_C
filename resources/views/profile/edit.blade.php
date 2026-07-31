@extends('template.master')

@section('title', 'Profil')
@section('subtitle', 'Hisob ma’lumotlari va xavfsizlik')

@section('content')

    @php
        $user = auth()->user();
        $roleLabels = ['admin' => 'Administrator', 'user' => "O'qituvchi", 'student' => 'Talaba', 'parent' => 'Ota-ona'];
        $roleLabel = $roleLabels[$user->getRoleNames()->first()] ?? 'Foydalanuvchi';
    @endphp

    <div class="row g-4">

        {{-- Identity card --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3" style="width: 88px; height: 88px;">
                        @if($user->photo)
                            <img src="{{ asset('storage/' . $user->photo) }}" alt=""
                                 class="rounded-circle w-100 h-100" style="object-fit: cover;">
                        @else
                            <span class="avatar-initial rounded-circle bg-label-primary d-inline-flex align-items-center justify-content-center"
                                  style="font-size: 1.75rem; width: 88px; height: 88px;">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 2)) }}
                            </span>
                        @endif
                    </div>

                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <span class="badge bg-label-primary mb-3">{{ $roleLabel }}</span>

                    <ul class="list-unstyled text-start mb-0 mt-3">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Telefon</span>
                            <span class="fw-semibold" dir="ltr">+{{ $user->phone }}</span>
                        </li>
                        @if($user->location)
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Manzil</span>
                                <span class="fw-semibold">{{ $user->location }}</span>
                            </li>
                        @endif
                        <li class="d-flex justify-content-between py-2">
                            <span class="text-muted">Ro‘yxatdan o‘tgan</span>
                            <span class="fw-semibold">{{ $user->created_at?->format('d.m.Y') }}</span>
                        </li>
                    </ul>

                    <p class="text-muted mt-3 mb-0" style="font-size: .8rem;">
                        Profil rasmini o‘quv markazi administratori yangilaydi.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">Shaxsiy ma’lumotlar</div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Parolni o‘zgartirish</div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Visible to every role, matching the previous behaviour. --}}
            <div class="card">
                <div class="card-header text-danger">Xavfli hudud</div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>

@endsection
