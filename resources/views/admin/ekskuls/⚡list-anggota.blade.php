<?php

use Livewire\Component;
use App\Models\Ekskul;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $list_anggota = [];
    public $ekskul_id;
    public $nama_ekskul_terpilih; // Ditrigger dari aksi baris datatable
    #[On('lihatAnggotaRow')]
    public function loadAnggotaEkskul($ekskulId)
    {
        $this->ekskul_id = $ekskulId;
        $tahunAjaranId = session()->get('tahun_ajaran_id');

        $ekskul = Ekskul::with([
            'siswas' => function ($q) use ($tahunAjaranId) {
                $q->where('ekskul_siswa.tahun_ajaran_id', $tahunAjaranId);
                // Jika menggunakan sistem verifikasi sebelumnya, Anda bisa memfilter:
                // ->where('ekskul_siswa.status', 'approved');
            },
        ])->findOrFail($ekskulId);

        $this->nama_ekskul_terpilih = $ekskul->nama_ekskul;

        // Konversi ke array agar bisa dirender di Blade
        $this->list_anggota = $ekskul->siswas->toArray();

        $this->dispatch('open-modal-detailAnggotaModal');
    }
    public function keluarkanSiswa($siswaId)
    {
        $ekskul = Ekskul::findOrFail($this->ekskul_id);
        $ekskul->siswas()->detach($siswaId);

        LivewireAlert::title('Berhasil!')->text('Siswa telah dikeluarkan dari ekstrakurikuler.')->success()->asToast()->show();

        // Refresh list anggota di modal & datatable utama
        $this->loadAnggotaEkskul($this->ekskul_id);
        $this->dispatch('refreshDatatable');
    }
};
?>

<div wire:ignore.self class="modal fade" id="detailAnggotaModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Daftar Anggota Ekskul: <span
                        class="fw-bold text-warning">{{ $nama_ekskul_terpilih }}</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    wire:click="cancel"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table-hover table-striped table border align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th style="width: 15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($list_anggota as $index => $siswa)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $siswa['nisn'] }}</td>
                                    <td>{{ $siswa['nama_siswa'] }}</td>

                                    <td class="text-center">
                                        @if (!auth()->user()->hasRole(['siswa']))
                                            <button wire:click="keluarkanSiswa({{ $siswa['id'] }})"
                                                wire:confirm="Apakah Anda yakin ingin mengeluarkan siswa ini dari anggota ekskul?"
                                                class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash3"></i> Keluarkan
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted py-4 text-center">
                                        <i class="bi bi-people d-block fs-2 mb-2"></i>
                                        Belum ada siswa yang terdaftar di tahun ajaran ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    wire:click="cancel">Tutup</button>
            </div>
        </div>
    </div>
</div>
