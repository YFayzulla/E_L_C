<nav
    class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar"
>
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        @role('admin')
        <div class="navbar-nav align-items-center">
            <div class="nav-item d-flex align-items-center">
                <i class="bx bx-search fs-4 lh-0"></i>
                <input
                    id="myInput"
                    type="text"
                    class="form-control border-0 shadow-none"
                    placeholder="Search..."
                    aria-label="Search..."
                />
            </div>
        </div>
        <!-- /Search -->
        <!-- Search by date -->
        <form action="{{route('student.search')}}" method="post">
            @csrf
            <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                    <input
                        type="date"
                        class="form-control border-0 shadow-none"
                        placeholder="Start Date"
                        name="start_date"
                        aria-label="Start Date"
                    />
                </div>

                <div class="nav-item d-flex align-items-center">
                    <input
                        type="date"
                        class="form-control border-0 shadow-none"
                        placeholder="End Date"
                        name="end_date"
                        aria-label="End Date"
                    />
                </div>

                <button type="submit" class="btn btn-outline-dark">Search</button>
            </div>
        </form>

        <!-- /Search -->
        @endrole

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ asset('icon/avatar.png') }}" alt class="w-px-40 h-auto rounded-circle"/>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('icon/avatar.png') }}" alt
                                             class="w-px-40 h-auto rounded-circle"/>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span
                                        class="fw-semibold d-block">{{ \Illuminate\Support\Facades\Auth::user()->name }}</span>
                                    @foreach(\Illuminate\Support\Facades\Auth::user()->getRoleNames() as $item)
                                        <small class="text-muted">{{ $item }}</small>
                                    @endforeach
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route("profile.edit") }}">
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route("profile.edit") }}">
                            <i class="bx bx-cog me-2"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                    </li>
                    <!-- Authentication -->

                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <input type="hidden" name="logout" value="1">
                            <button type="submit" class="dropdown-item">
                                <i class="bx bx-power-off me-2"></i>
                                <span class="align-middle">Log out</span>
                            </button>
                        </form>
                    </li>

                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
