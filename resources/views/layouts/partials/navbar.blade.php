<ul class="navbar-nav ms-auto">
    <!--begin::User Menu Dropdown-->
    <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
            <img src="{{ asset('images/user.svg') }}" class="user-image rounded-circle" alt="User Image" />
            <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
            <!--begin::User Image-->
            <li class="user-header text-bg-success">
                <img src="{{ asset('images/user.svg') }}" class="rounded-circle shadow-sm" alt="User Image" />
                <p>
                    {{ auth()->user()->email }} - {{ Str::upper(auth()->user()->role->role) }}
                    <small>Member since {{ date('F Y') }}</small>
                </p>
            </li>
            <!--end::User Image-->
            <!--begin::Menu Footer-->
            <li class="user-footer">
                @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah']))
                    <a href="{{ route('admin.profile') }}" class="btn btn-outline-secondary">Profile</a>
                @endif
                <form action="{{ route('logout') }}" method="post" class="d-inline">
			@csrf
                    <button type="submit" class="btn btn-outline-danger float-end">Sign out</button>
                </form>
            </li>
            <!--end::Menu Footer-->
        </ul>
    </li>
    <!--end::User Menu Dropdown-->
</ul>
