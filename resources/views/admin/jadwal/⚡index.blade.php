<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\{Jadwal, Rombel};
use App\Enum\Hari;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new #[Layout('layouts::app_admin', ['title' => 'Jadwal'])] class extends Component {
    public $jadwalJson;
    public $filter_rombel = '';
    public function mount()
    {
        if (!session()->has('tahun_ajaran_id')) {
            LivewireAlert::title('Gagal!')->text('Tahun ajaran aktif belum terdeteksi di session.')->error()->show();
        }
    }
    public function getFreshData()
    {
        $this->authorize('viewAny', \App\Models\Jadwal::class);
        $userLogin = auth()->user();

        // Mengunci kueri jadwal berdasarkan Tahun Ajaran dan Semester berjalan (Data Isolation)
        $query = Jadwal::where('tahun_ajaran_id', session()->get('tahun_ajaran_id'))->with(['guru', 'rombel.kelas', 'mapel', 'sekolah']);

        // FILTER AKSES TENANT & ROLE
        if ($userLogin->hasRole('guru')) {
            $query->where('user_id', $userLogin->id);
        } elseif (!$userLogin->hasRole('superadmin')) {
            $query->where('sekolah_id', $userLogin->sekolah_id);
        }

        // Filter interaktif berdasarkan pilihan dropdown Rombel (Khusus Admin/Guru)
        if ($this->filter_rombel) {
            $query->where('rombel_id', $this->filter_rombel);
        }

        return $query->orderBy('jam_mulai', 'asc')->get();
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-jadwal-data', data: $this->getFreshData());
    }
    public function render()
    {
        $userLogin = auth()->user();
        $jadwals = $this->getFreshData();

        return $this->view([
            'jadwals' => $jadwals,
            'daftar_hari' => Hari::cases(),
            'all_rombel' => !$userLogin->hasRole('siswa') ? Rombel::where('sekolah_id', $userLogin->sekolah_id)->get() : collect(),
        ]);

        // return $this->view(compact('columns'));
    }
};
?>
@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" />
@endpush
@push('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
    <script>
        const modalElement = document.getElementById('jadwalModal');
        if (modalElement) {
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement, {
                focus: false
            });

            // Menangani event buka dan tutup modal secara dinamis
            window.addEventListener('open-modal-jadwalModal', () => modalInstance.show());
            window.addEventListener('close-modal-jadwalModal', () => {
                modalInstance.hide()
            });
            modalElement.addEventListener('hide.bs.modal', function() {
                document.body.focus();

                // 2. Sebagai cadangan, hilangkan status active element jika ada descendant lain
                if (document.activeElement && document.activeElement !== document.body) {
                    document.activeElement.blur();
                }
            });
            modalElement.addEventListener('hidden.bs.modal', () => {
                Livewire.dispatch('triggerCancel');
            });
        }
    </script>
@endpush

<div>
    <div class="d-flex justify-content-between align-items-center border-bottom mb-4 pb-3">
        <div>
            <h4 class="font-weight-bolder text-dark mb-0">Jadwal Pelajaran</h4>
            <p class="text-muted mb-0 text-sm">
                Periode Aktif: <span
                    class="badge bg-success shadow-sm">{{ session()->get('tahun_ajaran_nama', 'Belum Diatur') }}</span>
            </p>
        </div>
        @if (!auth()->user()->hasAnyRole(['guru', 'siswa']))
            <button class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#jadwalModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
            </button>
        @endif
    </div>

    @if (!auth()->user()->hasRole('siswa'))
        <div class="card bg-light mb-4 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label font-weight-bold text-uppercase text-secondary mb-1 text-xs">Filter
                            Berdasarkan Rombel</label>
                        <select wire:model.live="filter_rombel" class="form-select form-select-sm border-0 shadow-sm">
                            <option value="">-- Tampilkan Semua Rombel --</option>
                            @foreach ($all_rombel as $rombel)
                                <option value="{{ $rombel->id }}">{{ $rombel->nama_rombel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        @foreach ($daftar_hari as $hari)
            @php
                $jadwalHariIni = $jadwals->filter(function ($j) use ($hari) {
                    // Memastikan properti hari pada model adalah instance Enum yang benar
                    $modelEnum = $j->hari instanceof \App\Enum\Hari ? $j->hari : \App\Enum\Hari::tryFrom($j->hari);

                    return $modelEnum === $hari;
                });
            @endphp

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div
                        class="card-header border-bottom d-flex justify-content-between align-items-center bg-white py-3">
                        <span class="font-weight-bold text-dark text-uppercase fs-6 tracking-wider">
                            <i class="bi bi-calendar-check text-success me-2"></i>{{ $hari->value }}
                        </span>
                        <span
                            class="badge bg-light text-secondary border px-2 py-1 text-xs">{{ $jadwalHariIni->count() }}
                            Sesi</span>
                    </div>

                    <div class="card-body position-relative overflow-auto pt-3" style="max-height: 550px;">
                        @if ($jadwalHariIni->isEmpty())
                            <div class="text-muted my-3 py-5 text-center">
                                <i class="bi bi-calendar-x fs-2 d-block text-black-50 mb-2"></i>
                                <span class="text-sm">Tidak ada jadwal pelajaran</span>
                            </div>
                        @else
                            <div class="position-relative ms-2 ps-3" style="border-left: 2px solid #e9ecef;">

                                @foreach ($jadwalHariIni as $item)
                                    <div class="position-relative mb-4">
                                        <span class="position-absolute bg-primary rounded-circle shadow"
                                            style="width: 12px; height: 12px; left: -22px; top: 5px; border: 2px solid #fff; z-index: 3;"></span>

                                        <div
                                            class="rounded-3 bg-light shadow-xs hover-shadow border-0 p-3 transition-all">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="font-weight-bold text-primary text-xs">
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ Carbon\Carbon::parse($item->jam_mulai)->format('H:i') }} -
                                                    {{ Carbon\Carbon::parse($item->jam_selesai)->format('H:i') }}
                                                </span>
                                            </div>

                                            <h6 class="font-weight-bold text-dark mb-1 text-sm">
                                                {{ $item->mapel?->nama_mapel ?? '-' }}</h6>

                                            <div
                                                class="d-flex justify-content-between align-items-center border-top border-200 mt-2 flex-wrap pt-2">
                                                <small class="text-muted text-xxs"><i
                                                        class="bi bi-person-badge me-1"></i>{{ $item->guru?->name ?? '-' }}</small>
                                                <span class="fw-bold text-secondary text-xxs"><i
                                                        class="bi bi-door-closed me-1"></i>{{ $item->rombel?->nama_rombel ?? '-' }}</span>
                                            </div>

                                            @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah']))
                                                <div class="border-top mt-2 pt-2 text-end">
                                                    <button
                                                        wire:click="$dispatch('editRow', { id: {{ $item->id }} })"
                                                        class="btn btn-link text-warning text-xxs text-decoration-none me-2 p-0">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <button
                                                        wire:click="$dispatch('triggerDeleteConfirm', { id: {{ $item->id }} })"
                                                        class="btn btn-link text-danger text-xxs text-decoration-none p-0">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @if (!auth()->user()->hasAnyRole(['guru', 'siswa']))
        <livewire:admin.jadwal.jadwal-form />
    @endif
</div>
