@extends('template.master')

@section('title', 'SMS shablonlari')
@section('subtitle', 'Ota-onalarga yuboriladigan tayyor xabar matnlari')

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('sms-templates.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Yangi shablon
        </a>
    </div>

    {{-- Eskiz holati. Sozlanmagan bo'lsa SMS umuman ketmaydi, shuning uchun
         buni yashirmasdan eng tepada ko'rsatamiz. --}}
    @php $eskiz = app(\App\Services\MessageService::class)->status(); @endphp

    <div class="alert alert-{{ $eskiz['level'] === 'success' ? 'success' : ($eskiz['level'] === 'danger' ? 'danger' : 'warning') }} d-flex align-items-start gap-2">
        <i class="bx {{ $eskiz['ok'] ? 'bx-check-circle' : 'bx-error-circle' }} fs-5 lh-1 mt-1"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold mb-1">SMS xizmati: {{ $eskiz['title'] }}</div>
            <div style="font-size: .875rem;">{{ $eskiz['detail'] }}</div>
            @unless($eskiz['ok'])
                <div class="mt-2" style="font-size: .82rem;">
                    <code>.env</code> fayliga <code>ESKIZ_EMAIL</code> va <code>ESKIZ_PASSWORD</code> qo‘shing,
                    so‘ng tekshiring:
                    <code>php artisan sms:check</code>
                </div>
            @endunless
        </div>
    </div>

    <div class="filter-card">
        <form method="GET" action="{{ route('sms-templates.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="q">Qidirish</label>
                <input type="text" id="q" name="q" class="form-control"
                       value="{{ request('q') }}" placeholder="Nomi yoki matn bo‘yicha">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="event">Turi</label>
                <select id="event" name="event" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach(\App\Models\SmsTemplate::EVENTS as $key => $label)
                        <option value="{{ $key }}" @selected(request('event') === $key)>
                            {{ $label }}@if(($counts[$key] ?? 0) > 0) ({{ $counts[$key] }})@endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bx bx-search-alt me-1"></i> Qidirish
                </button>
                <a href="{{ route('sms-templates.index') }}" class="btn btn-outline-secondary">Tozalash</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if($templates->isEmpty())
            <div class="empty-state">
                <i class="bx bx-message-square-dots"></i>
                <h6>Shablon topilmadi</h6>
                <p class="mb-3">
                    {{ request()->hasAny(['q', 'event']) ? 'Filtrga mos shablon yo‘q.' : 'Hali shablon qo‘shilmagan.' }}
                </p>
                <a href="{{ route('sms-templates.create') }}" class="btn btn-sm btn-primary">
                    <i class="bx bx-plus me-1"></i> Yangi shablon
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 14rem;">Nomi</th>
                        <th>Matn</th>
                        <th class="text-center" style="width: 6rem;">Belgi</th>
                        <th class="text-center" style="width: 7rem;">Holati</th>
                        <th class="text-end" style="width: 9rem;">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($templates as $template)
                        <tr class="{{ $template->is_active ? '' : 'opacity-50' }}">
                            <td>
                                <div class="fw-semibold">{{ $template->name }}</div>
                                <span class="badge bg-label-secondary mt-1">
                                    <i class="bx {{ $template->eventIcon() }} me-1"></i>{{ $template->eventLabel() }}
                                </span>
                            </td>
                            <td>
                                <div class="text-muted" style="font-size: .85rem; white-space: normal;">
                                    {{ $template->body }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-{{ mb_strlen($template->body) > 160 ? 'warning' : 'info' }}">
                                    {{ mb_strlen($template->body) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('sms-templates.toggle', $template->id) }}" method="post">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-{{ $template->is_active ? 'success' : 'secondary' }}">
                                        {{ $template->is_active ? 'Yoqilgan' : 'O‘chirilgan' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('sms-templates.edit', $template->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Tahrirlash">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>
                                    <form action="{{ route('sms-templates.destroy', $template->id) }}" method="post"
                                          onsubmit="return confirm('«{{ $template->name }}» shabloni o‘chirilsinmi?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="O‘chirish">
                                            <i class="bx bx-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-body pt-3 pb-0">
                <p class="text-muted mb-0" style="font-size: .78rem;">
                    <i class="bx bx-info-circle me-1"></i>
                    Yoqilgan shablonlar talaba sahifasidagi <strong>SMS yuborish</strong> oynasida
                    chiqadi — tanlaganda matn o‘sha talabaning ma’lumotlari bilan to‘ldirilgan holda keladi.
                    160 belgidan oshgan xabar operator tomonidan bir nechta SMS deb hisoblanadi.
                </p>
            </div>
        @endif
    </div>

@endsection
