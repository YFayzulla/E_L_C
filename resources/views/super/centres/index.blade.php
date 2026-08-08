@extends('template.master')

@section('title', 'O‘quv markazlari')
@section('subtitle', 'Platforma boshqaruvi')

@section('content')

    <div class="page-head">
        <div>
            <div class="page-sub mb-0">
                Har bir markaz o‘z subdomenida yashaydi va faqat o‘z ma’lumotini ko‘radi.
            </div>
        </div>
        <a href="{{ route('super.centres.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Yangi markaz
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>Markazlar</span>
            <span class="badge bg-label-secondary">{{ $centres->count() }} ta</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Markaz</th>
                    <th>Manzil</th>
                    <th>Holat</th>
                    <th class="text-center">Admin</th>
                    <th class="text-center">O‘qituvchi</th>
                    <th class="text-center">Talaba</th>
                    <th>SMS</th>
                    <th class="text-end">Amal</th>
                </tr>
                </thead>
                <tbody>
                @forelse($centres as $centre)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($centre->logoUrl())
                                    <img src="{{ $centre->logoUrl() }}" alt=""
                                         style="width: 32px; height: 32px; object-fit: contain;">
                                @else
                                    <span class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            {{ mb_strtoupper(mb_substr($centre->name, 0, 1)) }}
                                        </span>
                                    </span>
                                @endif
                                <div style="min-width: 0;">
                                    <div class="fw-semibold text-truncate">{{ $centre->name }}</div>
                                    <div class="text-muted" style="font-size: .78rem;">#{{ $centre->id }} · {{ $centre->certificate_prefix }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ $centre->url('/') }}" class="text-decoration-none">
                                {{ $centre->host() }} <i class="bx bx-link-external"></i>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-label-{{ $centre->isActive() ? 'success' : ($centre->status === \App\Models\Centre::STATUS_SUSPENDED ? 'warning' : 'secondary') }}">
                                {{ $centre->statusLabel() }}
                            </span>
                        </td>
                        <td class="text-center">{{ $counts[$centre->id]['admin'] }}</td>
                        <td class="text-center">{{ $counts[$centre->id]['teacher'] }}</td>
                        <td class="text-center">{{ $counts[$centre->id]['student'] }}</td>
                        <td>
                            @if(filled($centre->sms_email))
                                <span class="badge bg-label-info">o‘z hisobi</span>
                            @else
                                <span class="text-muted" style="font-size: .8rem;">global .env</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if(! $centre->waiting_room_group_id)
                                <span class="badge bg-label-danger me-1" title="Kutish zali yo‘q — talaba qabul qilish ishlamaydi">
                                    <i class="bx bx-error"></i>
                                </span>
                            @endif
                            <a href="{{ route('super.centres.edit', $centre->id) }}" class="btn-icon" title="Sozlamalar">
                                <i class="bx bx-edit"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bx bx-building-house"></i>
                                <h6>Hali bironta markaz yo‘q</h6>
                                <p class="mb-0">«Yangi markaz» tugmasi bilan birinchisini oching.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Platforma egalari</div>
                <div class="card-body">
                    <p class="text-muted" style="font-size: .84rem;">
                        Super-admin markazlarni ochadi va to‘xtatadi. U hech qaysi
                        markazning ma’lumot admini emas va hech qaysi markazga a’zo emas.
                    </p>

                    <ul class="list-unstyled mb-3">
                        @foreach($owners as $owner)
                            <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <x-avatar :user="$owner" label />
                                <form method="POST" action="{{ route('super.owners.toggle') }}"
                                      onsubmit="return confirm('{{ $owner->name }} super-adminlikdan olinsinmi?');">
                                    @csrf
                                    <input type="hidden" name="phone" value="{{ $owner->phone }}">
                                    <input type="hidden" name="grant" value="0">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Olib tashlash</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>

                    <form method="POST" action="{{ route('super.owners.toggle') }}" class="row g-2 align-items-end">
                        @csrf
                        <input type="hidden" name="grant" value="1">
                        <div class="col-sm-7">
                            <label class="form-label" for="owner_phone">Telefon raqami</label>
                            <input type="text" id="owner_phone" name="phone" class="form-control"
                                   placeholder="998901234567" required>
                        </div>
                        <div class="col-sm-5">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                Super-admin qilish
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Eslatma</div>
                <div class="card-body" style="font-size: .86rem;">
                    <p>
                        <strong>DNS.</strong> Wildcard yozuvi bo‘lishi shart:
                        <code>*.{{ config('app.domain') }}</code> — usiz yangi markazning
                        subdomeni ochilmaydi.
                    </p>
                    <p>
                        <strong>APP_DEFAULT_CENTRE.</strong>
                        @if(filled(config('app.default_centre')))
                            Hozir <code>{{ config('app.default_centre') }}</code> — markaz nomlanmagan
                            har qanday host o‘sha markazga tushadi. Bu ko‘chish davri uchun;
                            haqiqiy subdomenlarga o‘tgach uni bo‘shating, shunda apex
                            kirish va markaz tanlash sahifasiga aylanadi.
                        @else
                            Bo‘sh — apex kirish va markaz tanlash sahifasi.
                        @endif
                    </p>
                    <p class="mb-0">
                        <strong>To‘xtatish.</strong> To‘xtatilgan markaz 503, arxivlangani 404
                        qaytaradi. Ma’lumot hech qaysi holatda o‘chirilmaydi.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
