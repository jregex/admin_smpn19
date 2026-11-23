<!-- Sidebar -->
<aside class="app-sidebar bg-success" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="brand-link d-flex flex-column gap-y-2 overflow-hidden">
            <img src="{{ !auth()->user()->hasRole('superadmin') ? asset('gambar/images/logo/' . auth()->user()->sekolah->logo) : asset('images/logo_sekolah.png') }}"
                alt="Web sekolah logo" class="brand-image" />
            <span
                class="brand-text fw-bold fs-6">{{ auth()->user()->hasRole('superadmin') ? 'Web Sekolah' : auth()->user()->sekolah->nama_sekolah }}</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}"
                        class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah', 'guru']))
                    <li class="nav-header">AKADEMIK</li>
                    <li class="nav-item">
                        <a href="{{ route('jadwal') }}"
                            class="nav-link {{ request()->routeIs('jadwal') ? 'active' : '' }}">
                            <i
                                class="nav-icon bi {{ request()->routeIs('jadwal') ? 'bi-calendar-week-fill' : 'bi-calendar-week' }}"></i>
                            <p>Jadwal Mengajar</p>
                        </a>
                    </li>
                    @can('viewAny', App\Models\Ujian::class)
                        <li class="nav-item">
                            <a href="{{ route('ujian.index') }}"
                                class="nav-link {{ request()->routeIs('ujian.*') ? 'active' : '' }}">
                                <i
                                    class="nav-icon bi {{ request()->routeIs('ujian.*') ? 'bi-journal-check' : 'bi-journal' }}"></i>
                                <p>Kelola Ujian</p>
                            </a>
                        </li>
                    @endcan
                    <li class="nav-item">
                        <a href="{{ route('admin.ekskul') }}"
                            class="nav-link {{ request()->routeIs('admin.ekskul') ? 'active' : '' }}">
                            <i
                                class="nav-icon bi {{ request()->routeIs('admin.ekskul') ? 'bi-star-fill' : 'bi-star' }}"></i>
                            <p>Ekstrakulikuler</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.info') }}"
                            class="nav-link {{ request()->routeIs('admin.info') ? 'active' : '' }}">
                            <i
                                class="nav-icon bi {{ request()->routeIs('admin.info') ? 'bi-megaphone-fill' : 'bi-megaphone' }}"></i>
                            <p>Info & Pengumuman</p>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah']))
                    <li class="nav-header">DATA</li>
                    <li class="nav-item {{ request()->routeIs('master.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="nav-icon bi bi-file-medical-fill"></i>
                            <p>
                                Master
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah']))
                                <li class="nav-item">
                                    <a href="{{ route('master.tahun') }}"
                                        class="nav-link {{ request()->routeIs('master.tahun') ? 'active' : '' }}">
                                        <i
                                            class="nav-icon bi {{ request()->routeIs('master.tahun') ? 'bi-square-fill' : 'bi-square' }}"></i>
                                        <p>Tahun Ajaran</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('master.guru') }}"
                                        class="nav-link {{ request()->routeIs('master.guru') ? 'active' : '' }}">
                                        <i
                                            class="nav-icon bi {{ request()->routeIs('master.guru') ? 'bi-square-fill' : 'bi-square' }}"></i>
                                        <p>Guru</p>
                                    </a>
                                </li>
                            @endif
                            {{-- @if (auth()->user()->sekolah->jenjang == 'SMK')
                                <li class="nav-item">
                                    <a href="{{ route('master.jurusan') }}"
                                        class="nav-link {{ request()->routeIs('master.jurusan') ? 'active' : '' }}">
                                        <i
                                            class="nav-icon bi {{ request()->routeIs('master.jurusan') ? 'bi-square-fill' : 'bi-square' }}"></i>
                                        <p>Jurusan</p>
                                    </a>
                                </li>
                            @endif --}}
                            <li class="nav-item">
                                <a href="{{ route('master.kelas') }}"
                                    class="nav-link {{ request()->routeIs('master.kelas') ? 'active' : '' }}">
                                    <i
                                        class="nav-icon bi {{ request()->routeIs('master.kelas') ? 'bi-square-fill' : 'bi-square' }}"></i>
                                    <p>Kelas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('master.rombel') }}"
                                    class="nav-link {{ request()->routeIs('master.rombel') ? 'active' : '' }}">
                                    <i
                                        class="nav-icon bi {{ request()->routeIs('master.rombel') ? 'bi-square-fill' : 'bi-square' }}"></i>
                                    <p>Rombel</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('master.mapel') }}"
                                    class="nav-link {{ request()->routeIs('master.mapel') ? 'active' : '' }}">
                                    <i
                                        class="nav-icon bi {{ request()->routeIs('master.mapel') ? 'bi-square-fill' : 'bi-square' }}"></i>
                                    <p>Mata Pelajaran</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('master.siswa') }}"
                                    class="nav-link {{ request()->routeIs('master.siswa') ? 'active' : '' }}">
                                    <i
                                        class="nav-icon bi {{ request()->routeIs('master.siswa') ? 'bi-square-fill' : 'bi-square' }}"></i>
                                    <p>Siswa</p>
                                </a>
                            </li>

                        </ul>
                    </li>
                @endif
                {{-- @if (auth()->user()->hasAnyRole(['siswa']))
                    <li class="nav-header">Laporan</li>
                    <li class="nav-item">
                        <a href="{{ route('report.siswa') }}"
                            class="nav-link {{ request()->routeIs('report.siswa') ? 'active' : '' }}">
                            <i
                                class="nav-icon bi {{ request()->routeIs('report.siswa') ? 'bi-bar-chart-line-fill' : 'bi-bar-chart-line' }}"></i>
                            <p>Lapor</p>
                        </a>
                    </li>
                @endif --}}
                @if (auth()->user()->hasRole('admin_sekolah'))
                    <li class="nav-header">Admin</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.settings') }}"
                            class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                            <i
                                class="nav-icon bi {{ request()->routeIs('admin.settings') ? 'bi-gear-fill' : 'bi-gear' }}"></i>
                            <p>Pengaturan</p>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasRole('superadmin'))
                    <li class="nav-header">SUPER ADMIN</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon bi bi-people-fill"></i>
                            <p>Users</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('data.sekolah') }}" class="nav-link">
                            <i class="nav-icon bi bi-buildings-fill"></i>
                            <p>Sekolah</p>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
