@php
    /**
     * Small helper so every entry stays a one-liner.
     * Pass the route names that should light the item up.
     */
    $isActive = fn(...$routes) => request()->routeIs(...$routes) ? 'active' : '';
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <div class="app-brand">
        {{-- No w-100: on mobile the close button shares this row, and a
             full-width link pushes it onto a line of its own. --}}
        <a href="{{ route('dashboard') }}" class="app-brand-link d-flex align-items-center justify-content-center">
            <img src="{{ \App\Models\Centre::brandLogo() }}" alt="{{ \App\Models\Centre::brandName() }}" class="brand-logo"
                 style="max-width: 150px; width: 100%; height: auto; object-fit: contain;">
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <ul class="menu-inner py-1">

        {{-- ================= ADMIN ================= --}}
        @role('admin')
        <li class="menu-header small">Umumiy</li>

        <li class="menu-item {{ $isActive('dashboard') }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bxs-dashboard"></i>
                <div>Boshqaruv paneli</div>
            </a>
        </li>

        <li class="menu-header small">O'quv jarayoni</li>

        <li class="menu-item {{ $isActive('teacher.index', 'teacher.create', 'teacher.edit', 'teacher.show') }}">
            <a href="{{ route('teacher.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-chalkboard"></i>
                <div>O'qituvchilar</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('group.index', 'group.create', 'group.edit', 'group.attendance', 'group.students') }}">
            <a href="{{ route('group.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div>Guruhlar</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('student.index', 'student.create', 'student.edit', 'student.show') }}">
            <a href="{{ route('student.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user-voice"></i>
                <div>Talabalar</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('parents.index', 'parents.create', 'parents.edit') }}">
            <a href="{{ route('parents.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-heart"></i>
                <div>Ota-onalar</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('attendance.overview', 'attendance.log') }}">
            <a href="{{ route('attendance.overview') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                <div>Davomat</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('test', 'test.show') }}">
            <a href="{{ route('test') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-clipboard"></i>
                <div>Oylik test</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('homework.admin.index') }}">
            <a href="{{ route('homework.admin.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-task"></i>
                <div>Uy vazifalari</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('progress.index', 'progress.group', 'progress.student', 'skills.report') }}">
            <a href="{{ route('progress.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-line-chart"></i>
                <div>O'zlashtirish</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('certificates.index', 'certificates.create') }}">
            <a href="{{ route('certificates.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-award"></i>
                <div>Sertifikatlar</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('sms-templates.index', 'sms-templates.create', 'sms-templates.edit') }}">
            <a href="{{ route('sms-templates.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-message-square-dots"></i>
                <div>SMS shablonlari</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('waiters.index') }}">
            <a href="{{ route('waiters.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-hourglass"></i>
                <div>Kutish xonasi</div>
            </a>
        </li>

        <li class="menu-header small">Moliya</li>

        <li class="menu-item {{ $isActive('dept.index', 'dept.create', 'dept.edit', 'dept.show') }}">
            <a href="{{ route('dept.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-wallet"></i>
                <div>To'lovlar</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('finance.other') }}">
            <a href="{{ route('finance.other') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-line-chart"></i>
                <div>Xarajatlar</div>
            </a>
        </li>
        @endrole

        {{-- ================= TEACHER ================= --}}
        @role('user')
        <li class="menu-header small">Ish stoli</li>

        <li class="menu-item {{ $isActive('dashboard') }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bxs-dashboard"></i>
                <div>Boshqaruv paneli</div>
            </a>
        </li>

        {{-- "Mening guruhlarim" olib tashlandi: Davomat, Oylik test va
             Ko'nikmalar bo'limlarining har biri baribir guruh tanlashdan
             boshlanadi, ya'ni u takroriy qadam edi. Marshrutning o'zi
             (teacher.groups) joyida qoldi — unga havolalar bor. --}}

        <li class="menu-item {{ $isActive('attendance', 'attendance.check', 'group.attendance') }}">
            <a href="{{ route('attendance') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                <div>Davomat</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('assessment.teacher.groups', 'assessment.index', 'assessment.show') }}">
            <a href="{{ route('assessment.teacher.groups') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-list-check"></i>
                <div>Oylik test</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('homework.index', 'homework.create', 'homework.edit', 'homework.show', 'homework.grade') }}">
            <a href="{{ route('homework.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-task"></i>
                <div>Uy vazifalari</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('skills.groups', 'skills.grade', 'skills.report') }}">
            <a href="{{ route('skills.groups') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-book-open"></i>
                <div>Ko'nikmalar</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('progress.index', 'progress.group', 'progress.student') }}">
            <a href="{{ route('progress.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-line-chart"></i>
                <div>O'zlashtirish</div>
            </a>
        </li>
        @endrole

        {{-- ================= STUDENT ================= --}}
        @role('student')
        <li class="menu-header small">Mening sahifam</li>

        <li class="menu-item {{ $isActive('dashboard') }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bxs-dashboard"></i>
                <div>Bosh sahifa</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('attendance.index') }}">
            <a href="{{ route('attendance.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                <div>Davomatim</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('assessment.index', 'assessment.show') }}">
            <a href="{{ route('assessment.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-medal"></i>
                <div>Natijalarim</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('student.homework', 'student.homework.show') }}">
            <a href="{{ route('student.homework') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-task"></i>
                <div>Uy vazifalarim</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('student.skills') }}">
            <a href="{{ route('student.skills') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-book-open"></i>
                <div>Ko'nikmalarim</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('student.progress') }}">
            <a href="{{ route('student.progress') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-line-chart"></i>
                <div>O'zlashtirishim</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('student.certificates') }}">
            <a href="{{ route('student.certificates') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-award"></i>
                <div>Sertifikatlarim</div>
            </a>
        </li>
        @endrole

        {{-- ================= PARENT (OTA-ONA) ================= --}}
        @role('parent')
        <li class="menu-header small">Ota-ona kabineti</li>

        <li class="menu-item {{ $isActive('parent.index', 'parent.child') }}">
            <a href="{{ route('parent.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-heart"></i>
                <div>Farzandlarim</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('parent.attendance') }}">
            <a href="{{ route('parent.attendance') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                <div>Davomat</div>
            </a>
        </li>

        <li class="menu-item {{ $isActive('parent.payments') }}">
            <a href="{{ route('parent.payments') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-wallet"></i>
                <div>To'lovlar</div>
            </a>
        </li>
        @endrole

        {{-- ================= SUPER-ADMIN (platforma egasi) ================= --}}
        {{-- Spatie roli emas, `users.is_super_admin` bayrog'i: markazsiz rol
             biriktirish imkonsiz (model_has_roles.centre_id NOT NULL), va
             super-admin aynan markazlardan yuqorida turadi. --}}
        @if(auth()->user()?->is_super_admin)
        <li class="menu-header small">Platforma</li>

        <li class="menu-item {{ $isActive('super.centres.index', 'super.centres.create', 'super.centres.edit') }}">
            <a href="{{ route('super.centres.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-buildings"></i>
                <div>O'quv markazlari</div>
            </a>
        </li>
        @endif

        {{-- Bir nechta markazda ishlaydigan odam uchun almashtirish yo'li. --}}
        @if(auth()->check() && auth()->user()->centres()->count() > 1)
        <li class="menu-item">
            <a href="{{ \App\Models\Centre::current() ? url('/centres') : route('centres.choose') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                <div>Markazni almashtirish</div>
            </a>
        </li>
        @endif

        {{-- ================= EVERYONE ================= --}}
        <li class="menu-header small">Hisob</li>

        <li class="menu-item {{ $isActive('profile.edit') }}">
            <a href="{{ route('profile.edit') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div>Profil</div>
            </a>
        </li>

        <li class="menu-item">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="menu-link w-100 text-start border-0 bg-transparent">
                    <i class="menu-icon tf-icons bx bx-log-out"></i>
                    <div>Chiqish</div>
                </button>
            </form>
        </li>
    </ul>
</aside>
