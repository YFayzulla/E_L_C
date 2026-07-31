@php
    /** @var \App\Models\SmsTemplate $template */
    /** @var array<string,string> $sample */
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Shablon</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="name">Nomi <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $template->name) }}"
                               placeholder="Masalan: Darsga kelmadi" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label" for="event">Turi <span class="text-danger">*</span></label>
                        <select id="event" name="event" class="form-select @error('event') is-invalid @enderror" required>
                            @foreach(\App\Models\SmsTemplate::EVENTS as $key => $label)
                                <option value="{{ $key }}" @selected(old('event', $template->event) === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('event') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="body">Xabar matni <span class="text-danger">*</span></label>
                        <textarea id="body" name="body" rows="5" maxlength="500"
                                  class="form-control @error('body') is-invalid @enderror"
                                  required>{{ old('body', $template->body) }}</textarea>
                        @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text d-flex justify-content-between">
                            <span>Belgilar: <span id="tpl-count">0</span>/500</span>
                            <span id="tpl-parts" class="text-muted"></span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="sort_order">Tartib raqami</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" max="999"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', $template->sort_order ?? 0) }}">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Kichik raqam ro‘yxatda yuqorida turadi.</div>
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                   @checked(old('is_active', $template->is_active ?? true))>
                            <label class="form-check-label" for="is_active">
                                Yoqilgan — SMS oynasida ko‘rinadi
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Jonli ko'rinish: haqiqiy talaba ma'lumotlari bilan --}}
        <div class="card mt-4">
            <div class="card-header">Ko‘rinishi</div>
            <div class="card-body">
                <div class="p-3 rounded" style="background: var(--app-surface-2); border: 1px solid var(--app-border);">
                    <div id="tpl-preview" style="white-space: pre-wrap; font-size: .9rem;"></div>
                </div>
                <div class="form-text mt-2">
                    Namuna sifatida haqiqiy talabaning ma’lumotlari qo‘yildi.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">O‘zgaruvchilar</div>
            <div class="card-body d-flex flex-column">
                <p class="text-muted" style="font-size: .82rem;">
                    Bosing — matnga qo‘shiladi. Yuborishda har bir talabaning
                    o‘z ma’lumotlari bilan almashtiriladi.
                </p>

                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach(\App\Models\SmsTemplate::PLACEHOLDERS as $key => $label)
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary tpl-insert"
                                data-token="{{ '{' . $key . '}' }}"
                                title="{{ $label }}">
                            {{ '{' . $key . '}' }}
                        </button>
                    @endforeach
                </div>

                <ul class="list-unstyled mb-0" style="font-size: .78rem;">
                    @foreach(\App\Models\SmsTemplate::PLACEHOLDERS as $key => $label)
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <code class="text-muted">{{ '{' . $key . '}' }}</code>
                            <span class="text-muted text-end ms-2">{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-auto pt-4 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Saqlash
                    </button>
                    <a href="{{ route('sms-templates.index') }}" class="btn btn-outline-secondary">Bekor qilish</a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var body    = document.getElementById('body');
            var preview = document.getElementById('tpl-preview');
            var count   = document.getElementById('tpl-count');
            var parts   = document.getElementById('tpl-parts');
            var sample  = @json($sample);

            function render() {
                var text = body.value;

                Object.keys(sample).forEach(function (key) {
                    text = text.split('{' + key + '}').join(sample[key]);
                });

                preview.textContent = text.trim() || '— matn kiritilmagan —';
                count.textContent = body.value.length;

                // Latin SMS: 160 chars per part, 153 once it is a multipart.
                var n = body.value.length;
                var segments = n <= 160 ? 1 : Math.ceil(n / 153);
                parts.textContent = segments === 1 ? '1 SMS' : segments + ' ta SMS';
                parts.className = segments > 1 ? 'text-warning' : 'text-muted';
            }

            document.querySelectorAll('.tpl-insert').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var token = btn.dataset.token;
                    var start = body.selectionStart, end = body.selectionEnd;

                    body.value = body.value.slice(0, start) + token + body.value.slice(end);
                    body.focus();
                    body.selectionStart = body.selectionEnd = start + token.length;
                    render();
                });
            });

            body.addEventListener('input', render);
            render();
        })();
    </script>
@endpush
