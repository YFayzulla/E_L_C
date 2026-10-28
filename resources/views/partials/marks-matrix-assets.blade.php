{{--
    Baholash jadvali (oylik test va ko'nikmalar): ustun tepasidagi
    checkbox'lar, o'rtacha va yig'indi, ixcham ko'rinish.

    Jadvalga kerak bo'lgan belgilar:
      table.marks-table, tbody#myTable tr[data-student]
      input.js-skill-toggle[value=<skill>]   — ustun belgisi (name="skills[]")
      th.js-skill-head[data-skill]           — ustun sarlavhasi
      input.js-score[data-skill]             — ball katagi
      input.js-agg-toggle[data-agg=average|sum], .js-average, .js-sum

    @param string $formId  saqlash formasi (birorta ustun belgilanmasa yuborilmaydi)
--}}

{{-- Jadval odatiy ekranga (1280px) gorizontal scroll'siz sig'ishi uchun
     kataklar oralig'i va ball maydonlari ixcham. Boshqa jadvallar
     .grade-table ni o'zgarishsiz ishlatadi. --}}
<style>
    html[data-theme] .marks-table th,
    html[data-theme] .marks-table td { padding: .45rem .4rem; }
    html[data-theme] .marks-table th:first-child,
    html[data-theme] .marks-table td:first-child { padding-left: 1rem; width: 13rem; max-width: 13rem; }
    /* Uzun ism ikki qatorga o'tadi — ustunni kengaytirmaydi. */
    html[data-theme] .marks-table td:first-child .text-truncate { white-space: normal; }
    html[data-theme] .marks-table thead th { font-size: .72rem; white-space: nowrap; }
    html[data-theme] .marks-table thead label { justify-content: center; }
    html[data-theme] .marks-table .grade-input { max-width: 4.2rem; min-width: 3.6rem; margin: 0 auto; padding: .35rem .25rem; }
    html[data-theme] .marks-table td { text-align: center; }
    html[data-theme] .marks-table td:last-child { text-align: left; }
</style>

<script>
    (function () {
        const rows = document.querySelectorAll('#myTable tr[data-student]');
        const skillToggles = [...document.querySelectorAll('.js-skill-toggle')];
        const aggToggles = [...document.querySelectorAll('.js-agg-toggle')];

        const selectedSkills = () => skillToggles.filter(t => t.checked).map(t => t.value);
        const aggEnabled = (name) => aggToggles.some(t => t.dataset.agg === name && t.checked);

        function paint(el, value, tone) {
            if (!el) { return; }
            el.textContent = value === null ? '—' : value;
            el.className = el.className.replace(/\bbg-label-\w+/g, '').trim()
                + ' bg-label-' + (value === null ? 'secondary' : tone);
        }

        // Bo'sh kataklar hisobga kirmaydi — server ham shunday qiladi.
        function recalc(row) {
            const active = selectedSkills();
            const values = [...row.querySelectorAll('.js-score')]
                .filter(i => active.includes(i.dataset.skill))
                .map(i => i.value.trim())
                .filter(v => v !== '')
                .map(Number)
                .filter(n => Number.isFinite(n));

            const has = values.length > 0;
            const sum = values.reduce((a, b) => a + b, 0);
            const mean = has ? Math.round(sum / values.length) : null;

            paint(row.querySelector('.js-average'),
                aggEnabled('average') ? mean : null,
                mean >= 80 ? 'success' : mean >= 60 ? 'warning' : 'danger');
            paint(row.querySelector('.js-sum'),
                aggEnabled('sum') && has ? sum : null,
                'primary');
        }

        function applySkillState() {
            const active = selectedSkills();
            document.querySelectorAll('.js-score').forEach(input => {
                const on = active.includes(input.dataset.skill);
                // disabled maydon serverga yuborilmaydi — belgilanmagan
                // ustun saqlanmaydi.
                input.disabled = !on;
                input.closest('td').style.opacity = on ? '' : '.35';
            });
            document.querySelectorAll('.js-skill-head').forEach(th => {
                th.style.opacity = active.includes(th.dataset.skill) ? '' : '.55';
            });
            rows.forEach(recalc);
        }

        skillToggles.forEach(t => t.addEventListener('change', applySkillState));
        aggToggles.forEach(t => t.addEventListener('change', () => rows.forEach(recalc)));

        rows.forEach(row => {
            row.querySelectorAll('.js-score').forEach(input => {
                input.addEventListener('input', () => recalc(row));
            });
        });

        document.getElementById(@json($formId))?.addEventListener('submit', (event) => {
            if (selectedSkills().length === 0) {
                event.preventDefault();
                alert('Kamida bitta ko‘nikma ustunini belgilang.');
            }
        });

        applySkillState();
    })();
</script>
