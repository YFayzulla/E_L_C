@php
    /**
     * SMS yuborish oynasi.
     * @include('partials.student-sms-modal', ['student' => $student])
     *
     * Qabul qiluvchi aniq tanlanadi — otasi, onasi, vasiysi yoki talabaning
     * o'zi. Raqami yo'q variant tanlanmaydigan qilib ko'rsatiladi, shunda
     * "yuborildi" degan xabar yolg'on chiqmaydi.
     */
    $guardians = $student->guardians()->get()->keyBy(fn($g) => $g->pivot->relation ?: 'ota');

    $options = [];

    foreach (['ota' => 'Otasiga', 'ona' => 'Onasiga', 'vasiy' => 'Vasiysiga'] as $relation => $label) {
        $g = $guardians->get($relation);
        $options[$relation] = [
            'label' => $label,
            'name'  => $g?->name,
            'phone' => $g?->phone,
        ];
    }

    $options['student'] = [
        'label' => 'Talabaning o‘ziga',
        'name'  => $student->name,
        'phone' => $student->phone,
    ];

    $available = collect($options)->filter(fn($o) => filled($o['phone']));

    // Shablonlar shu talabaning ma'lumotlari bilan oldindan to'ldirib qo'yiladi,
    // shunda tanlash bilanoq tayyor matn tushadi.
    $smsTemplates = \App\Models\SmsTemplate::active()->ordered()->get();
    $smsContext   = $smsTemplates->isNotEmpty()
        ? \App\Models\SmsTemplate::contextForStudent($student)
        : [];

    $renderedTemplates = $smsTemplates->map(fn($t) => [
        'id'    => $t->id,
        'name'  => $t->name,
        'event' => $t->eventLabel(),
        'body'  => $t->render($smsContext),
    ])->values();
@endphp

<div class="modal fade" id="smsModal" tabindex="-1" aria-hidden="true" aria-labelledby="smsModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('student.sms', $student->id) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="smsModalLabel">
                        <i class="bx bx-message-dots me-1"></i>SMS yuborish
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    @if($available->isEmpty())
                        <div class="empty-state py-4">
                            <i class="bx bx-phone-off"></i>
                            <h6>Raqam yo‘q</h6>
                            <p class="mb-0">
                                Bu talabaning ham, ota-onasining ham telefon raqami kiritilmagan.
                                Avval <a href="{{ route('student.edit', $student->id) }}">ma’lumotlarni to‘ldiring</a>.
                            </p>
                        </div>
                    @else
                        <label class="form-label d-block mb-2">Kimga yuborilsin?</label>

                        @foreach($options as $key => $option)
                            @php $has = filled($option['phone']); @endphp
                            <div class="form-check mb-2 {{ $has ? '' : 'opacity-50' }}">
                                <input class="form-check-input" type="checkbox"
                                       name="audiences[]" value="{{ $key }}"
                                       id="sms-to-{{ $key }}"
                                       {{ $has ? '' : 'disabled' }}
                                       {{ $key === 'ota' && $has ? 'checked' : '' }}>
                                <label class="form-check-label d-flex justify-content-between align-items-center"
                                       for="sms-to-{{ $key }}" style="cursor: {{ $has ? 'pointer' : 'not-allowed' }};">
                                    <span>
                                        {{ $option['label'] }}
                                        @if($option['name'])
                                            <span class="text-muted">— {{ $option['name'] }}</span>
                                        @endif
                                    </span>
                                    <span class="text-muted" style="font-size: .8rem;" dir="ltr">
                                        {{ $has ? '+' . $option['phone'] : 'raqam yo‘q' }}
                                    </span>
                                </label>
                            </div>
                        @endforeach

                        @error('audiences')
                            <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div>
                        @enderror

                        @if($renderedTemplates->isNotEmpty())
                            <div class="mt-3">
                                <label class="form-label" for="sms-template">Tayyor shablon</label>
                                <select id="sms-template" class="form-select">
                                    <option value="">— shablonni tanlang —</option>
                                    @foreach($renderedTemplates as $tpl)
                                        <option value="{{ $tpl['id'] }}">{{ $tpl['name'] }} ({{ $tpl['event'] }})</option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    Tanlanganda matn {{ $student->name }}ning ma’lumotlari bilan to‘ldiriladi.
                                </div>
                            </div>
                        @endif

                        <div class="mt-3">
                            <label class="form-label" for="sms-message">Xabar matni</label>
                            <textarea id="sms-message" name="message" rows="4" maxlength="500"
                                      class="form-control @error('message') is-invalid @enderror"
                                      placeholder="Masalan: Hurmatli ota-ona, farzandingiz bugungi darsga kelmadi."
                            >{{ old('message') }}</textarea>
                            @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text d-flex justify-content-between">
                                <span><span id="sms-count">{{ mb_strlen(old('message', '')) }}</span>/500 belgi</span>
                                <span id="sms-parts" class="text-muted"></span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                    @if($available->isNotEmpty())
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-send me-1"></i>Yuborish
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var picker  = document.getElementById('sms-template');
            var message = document.getElementById('sms-message');
            var count   = document.getElementById('sms-count');
            var parts   = document.getElementById('sms-parts');

            if (!message) {
                return;
            }

            // Already rendered server-side with this student's real values.
            var templates = @json($renderedTemplates);

            function updateCount() {
                var n = message.value.length;
                if (count) count.textContent = n;
                if (!parts) return;

                var segments = n === 0 ? 0 : (n <= 160 ? 1 : Math.ceil(n / 153));
                parts.textContent = segments > 1 ? segments + ' ta SMS' : (segments === 1 ? '1 SMS' : '');
                parts.className = segments > 1 ? 'text-warning' : 'text-muted';
            }

            if (picker) {
                picker.addEventListener('change', function () {
                    if (!picker.value) {
                        return;
                    }

                    var tpl = templates.find(function (t) { return String(t.id) === picker.value; });
                    if (!tpl) {
                        return;
                    }

                    // Never silently discard something already typed.
                    if (message.value.trim() && !confirm('Yozilgan matn shablon bilan almashtirilsinmi?')) {
                        picker.value = '';
                        return;
                    }

                    message.value = tpl.body;
                    message.focus();
                    updateCount();
                });
            }

            message.addEventListener('input', updateCount);
            updateCount();
        })();
    </script>
@endpush
