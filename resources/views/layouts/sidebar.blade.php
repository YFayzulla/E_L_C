<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('logos/main-white.svg') }}" width="110" height="32" alt="SpeakUp" class="navbar-brand-image">
            </a>
        </h1>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @role('admin')
                {{-- Dashboard --}}
                <li class="nav-item @if(request()->routeIs('dashboard')) active @endif">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-tachometer-alt"></i>
                        </span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>
                {{-- 1) Teachers --}}
                <li class="nav-item @if(request()->routeIs('teacher.index', 'teacher.create', 'teacher.edit', 'teacher.show')) active @endif">
                    <a class="nav-link" href="{{ route('teacher.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </span>
                        <span class="nav-link-title">Teachers</span>
                    </a>
                </li>
                {{-- 2) Groups --}}
                <li class="nav-item @if(request()->routeIs('group.index', 'group.create', 'group.edit', 'group.show', 'group.attendance', 'group.create.room')) active @endif">
                    <a class="nav-link" href="{{ route('group.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-door-open"></i>
                        </span>
                        <span class="nav-link-title">Groups</span>
                    </a>
                </li>
                {{-- 3) Students --}}
                <li class="nav-item @if(request()->routeIs('student.index', 'student.create', 'student.edit', 'student.show')) active @endif">
                    <a class="nav-link" href="{{ route('student.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-user-graduate"></i>
                        </span>
                        <span class="nav-link-title">Students</span>
                    </a>
                </li>
                {{-- 4) Department/Payments --}}
                <li class="nav-item @if(request()->routeIs('dept.index')) active @endif">
                    <a class="nav-link" href="{{ route('dept.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </span>
                        <span class="nav-link-title">Payments</span>
                    </a>
                </li>
                <li class="nav-item @if(request()->routeIs('test') || request()->routeIs('test.show')) active @endif">
                    <a class="nav-link" href="{{ route('test') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-clipboard-check"></i>
                        </span>
                        <span class="nav-link-title">Assessment</span>
                    </a>
                </li>
                {{-- 5) Waiting Room --}}
                <li class="nav-item @if(request()->routeIs('waiters.index')) active @endif">
                    <a class="nav-link" href="{{ route('waiters.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-hourglass-half"></i>
                        </span>
                        <span class="nav-link-title">Waiting Room</span>
                    </a>
                </li>
                <li class="nav-item @if(request()->routeIs('finance.other')) active @endif">
                    <a class="nav-link" href="{{ route('finance.other') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-coins"></i>
                        </span>
                        <span class="nav-link-title">Finance</span>
                    </a>
                </li>
                @endrole

                @hasanyrole('user')
                <li class="nav-item @if(request()->routeIs('assessment.index', 'assessment.show')) active @endif">
                    <a class="nav-link" href="{{ route('assessment.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-tasks"></i>
                        </span>
                        <span class="nav-link-title">Tests</span>
                    </a>
                </li>
                <li class="nav-item @if(request()->routeIs('attendance', 'attendance.check')) active @endif">
                    <a class="nav-link" href="{{ route('attendance') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="fas fa-user-check"></i>
                        </span>
                        <span class="nav-link-title">Attendance</span>
                    </a>
                </li>
                @endhasanyrole
            </ul>
        </div>
    </div>
</aside>
