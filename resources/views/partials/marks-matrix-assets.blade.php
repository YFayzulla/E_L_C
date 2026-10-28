{{--
    Baholash jadvali (oylik test va ko'nikmalar): ustun tepasidagi
    checkbox'lar, o'rtacha va yig'indi, ixcham ko'rinish.

    Jadvalga kerak bo'lgan belgilar:
      table.marks-table, tbody#myTable tr[data-student]
      input.js-skill-toggle[value=<skill>]   — ustun belgisi (name="skills[]")
      input.js-skill-max[data-skill]         — ustunning maksimal balli (name="max[<skill>]")
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

        // Ustun sarlavhasidagi maksimal ball (bo'lmasa 100).
        const maxFor = (skill) => {
            const input = document.querySelector(`.js-skill-max[data-skill="${skill}"]`);
            const value = parseInt(input ? input.value : '', 10);
            return Number.isFinite(value) && value > 0 ? value : 100;
        };

        function paint(el, value, tone) {
            if (!el) { return; }
            el.textContent = value === null ? '—' : value;
            el.className = el.className.replace(/\bbg-label-\w+/g, '').trim()
                + ' bg-label-' + (value === null ? 'secondary' : tone);
        }

        // Bo'sh kataklar hisobga kirmaydi — server ham shunday qiladi.
        // O'rtacha — foizda (50/50 = 100%), yig'indi — xom: "olingan / maksimum".
        function recalc(row) {
            const active = selectedSkills();
            const marks = [...row.querySelectorAll('.js-score')]
                .filter(i => active.includes(i.dataset.skill) && i.value.trim() !== '')
                .map(i => ({ value: Number(i.value), max: maxFor(i.dataset.skill) }))
                .filter(m => Number.isFinite(m.value));

            const has = marks.length > 0;
            const sum = marks.reduce((a, m) => a + m.value, 0);
            const sumMax = marks.reduce((a, m) => a + m.max, 0);
            const mean = has
                ? Math.round(marks.reduce((a, m) => a + m.value * 100 / m.max, 0) / marks.length)
                : null;

            paint(row.querySelector('.js-average'),
                aggEnabled('average') && has ? mean + '%' : null,
                mean >= 80 ? 'success' : mean >= 60 ? 'warning' : 'danger');
            paint(row.querySelector('.js-sum'),
                aggEnabled('sum') && has ? sum + '/' + sumMax : null,
                'primary');
        }

        // Maksimum o'zgarsa — kataklar chegarasi va hisob yangilanadi.
        document.querySelectorAll('.js-skill-max').forEach(input => {
            input.addEventListener('input', () => {
                document.querySelectorAll(`.js-score[data-skill="${input.dataset.skill}"]`)
                    .forEach(score => { score.max = maxFor(input.dataset.skill); });
                rows.forEach(recalc);
            });
        });

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
            document.querySelectorAll('.js-skill-max').forEach(input => {
                input.disabled = !active.includes(input.dataset.skill);
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
