{{--
    Belgilangan talabalarni boshqa guruhga ko'chirish paneli.

    Jadvaldagi checkbox'lar formaga `form="bulkMoveForm"` atributi bilan
    ulanadi — jadvalni forma ichiga o'rash shart emas.

    @param \Illuminate\Support\Collection $groups   tanlash mumkin bo'lgan guruhlar
    @param int|null $fromGroupId                    shu guruhdan chiqariladi (Kutish zali o'zi chiqariladi)
    @param \App\Models\Group|null $waitingRoom      berilsa, ro'yxat boshida "Kutish zali" chiqadi
--}}
<form id="bulkMoveForm" action="{{ route('group.students.move') }}" method="post"
      class="d-flex align-items-center flex-wrap gap-2 px-4 py-3 border-bottom"
      onsubmit="return document.querySelectorAll('.js-move-check:checked').length > 0
          ? confirm('Belgilangan talabalar tanlangan guruhga ko‘chirilsinmi?')
          : (alert('Avval talabalarni belgilang.'), false);">
    @csrf
    <input type="hidden" name="from_group_id" value="{{ $fromGroupId ?? '' }}">

    <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="moveCheckAll">
        <label class="form-check-label" for="moveCheckAll">Hammasini belgilash</label>
    </div>

    <span class="text-muted ms-2" style="font-size: .85rem;">
        Belgilandi: <strong id="moveCheckedCount">0</strong>
    </span>

    <div class="d-flex align-items-center gap-2 ms-md-auto flex-wrap">
        <select name="target_group_id" class="form-select form-select-sm" style="width: auto; min-width: 14rem;" required
                aria-label="Qaysi guruhga ko‘chirish">
            <option value="">— Qaysi guruhga —</option>
            @if(! empty($waitingRoom))
                <option value="{{ $waitingRoom->id }}">⏳ Kutish zali</option>
                <optgroup label="Guruhlar">
            @endif
            @foreach($groups as $option)
                <option value="{{ $option->id }}">{{ $option->name }}</option>
            @endforeach
            @if(! empty($waitingRoom))
                </optgroup>
            @endif
        </select>
        <button type="submit" class="btn btn-sm btn-primary text-nowrap">
            <i class="bx bx-transfer me-1"></i> Belgilanganlarni ko‘chirish
        </button>
    </div>
</form>

<script>
    (function () {
        const all = document.getElementById('moveCheckAll');
        const count = document.getElementById('moveCheckedCount');
        const boxes = () => [...document.querySelectorAll('.js-move-check')];
        const refresh = () => {
            const checked = boxes().filter(b => b.checked).length;
            count.textContent = checked;
            all.checked = checked > 0 && checked === boxes().length;
        };

        all.addEventListener('change', () => {
            boxes().forEach(b => { b.checked = all.checked; });
            refresh();
        });
        document.addEventListener('change', (e) => {
            if (e.target.classList?.contains('js-move-check')) refresh();
        });
    })();
</script>
