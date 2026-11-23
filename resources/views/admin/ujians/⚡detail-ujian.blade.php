<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Ujian;
use App\Services\NilaiUjianService;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $ujian = null;
    public $ujianNilai = null;
    public $daftarNilai = [];
    #[On('detailModal')]
    public function tampilkanModalDetail($id)
    {
        // Eager load bertingkat (with) memastikan query ke PostgreSQL sangat ringan (bebas N+1)
        $this->ujian = Ujian::with(['soals.pilihanJawabans', 'kelas', 'guru'])->findOrFail($id);
        $this->dispatch('open-modal-modalDetailUjian');
    }
    #[On('lihatNilaiUjian')]
    public function tampilkanNilaiUjian($id)
    {
        $ujian = Ujian::with(['kelas', 'mapel'])->findOrFail($id);
        Gate::authorize('view', $ujian);

        if ($ujian->status !== 'SELESAI') {
            LivewireAlert::title('Belum bisa dibuka')
                ->text('Nilai siswa hanya dapat dilihat setelah ujian selesai.')
                ->warning()
                ->asToast()
                ->show();
            return;
        }

        $this->ujianNilai = $ujian;
        $this->daftarNilai = app(NilaiUjianService::class)->daftarNilai($ujian);

        $this->dispatch('open-modal-modalNilaiUjian');
    }
    #[On('triggerDeleteConfirm')]
    public function triggerDeleteConfirm($id)
    {
        LivewireAlert::title('Hapus data')
            ->text('Apakah kamu yakin ingin menghapus?')
            ->asConfirm()
            ->onConfirm('delete', ['id' => $id])
            ->show();
    }
    public function delete(array $data)
    {
        DB::beginTransaction();
        try {
            $ujian = Ujian::findOrFail($data['id']);
            $ujian->delete();
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Data berhasil dihapus')->success()->asToast()->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->timer(8000)->error()->asToast()->show();
            return;
        }
        $this->dispatch('refreshDatatable');
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="modalDetailUjian" tabindex="-1" aria-labelledby="modalDetailUjianLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0">

                @if ($ujian)
                    <div class="modal-header bg-dark py-3 text-white">
                        <div>
                            <h5 class="modal-title fw-bold" id="modalDetailUjianLabel">
                                <i class="bi bi-info-circle text-info me-2"></i>Struktur Paket Ujian
                            </h5>
                            <h6 class="text-white-50">
                                {{ $ujian->nama_ujian }} | Kelas: {{ $ujian->kelas->nama_kelas ?? '-' }} | Guru:
                                {{ $ujian->guru->nama ?? '-' }} | Pengawas ujian : {{ $ujian->pengawas->nama ?? '-' }} |
                                Tahun Ajaran: {{ session()->get('tahun_ajaran_nama') }}
                            </h6>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body bg-light p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-secondary mb-0">
                                <i class="bi bi-list-ol text-primary me-1"></i> Daftar Soal Aktif
                                ({{ $ujian->soals->count() }} Butir)
                            </h6>
                            <div>
                                <span class="badge bg-secondary">Durasi: {{ $ujian->durasi }} Menit</span> | <span
                                    class="badge bg-secondary">KKPP: {{ $ujian->kkpp }} Poin</span>
                            </div>
                        </div>

                        @forelse($ujian->soals as $index => $soal)
                            <div class="card rounded-3 mb-3 overflow-hidden border-0 shadow-sm">
                                <div class="card-body p-3">

                                    <div
                                        class="d-flex justify-content-between align-items-center border-bottom mb-3 pb-2">
                                        <span class="badge bg-primary rounded-pill fw-semibold px-3 py-2">Soal
                                            {{ $index + 1 }}</span>
                                        <span class="badge bg-success-subtle text-success border px-3 py-2">
                                            Kunci Utama: <strong
                                                class="ms-1">{{ strtoupper($soal->kunci_jawaban) }}</strong>
                                        </span>
                                    </div>

                                    <p class="mb-3" style="white-space: pre-line;">
                                        {{ $soal->teks_soal }}</p>

                                    @if ($soal->gambar)
                                        <div class="d-inline-block mb-3 rounded border bg-white p-2 text-start">
                                            <small class="text-muted d-block mb-1"><i class="bi bi-paperclip"></i>
                                                Lampiran Gambar:</small>
                                            <img src="{{ asset('gambar/images/soal/' . $soal->gambar) }}"
                                                class="img-fluid rounded-2"
                                                style="max-height: 220px; object-fit: contain;">
                                        </div>
                                    @endif

                                    <div class="row g-2">
                                        @foreach ($soal->pilihanJawabans as $opsi)
                                            <div class="col-12">
                                                <div class="{{ $opsi->is_kunci ? 'bg-success-subtle border-success text-success fw-bold shadow-sm' : 'bg-white border-light text-secondary' }} rounded border p-2"
                                                    style="transition: all 0.3s ease;">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span
                                                                class="fw-black text-dark me-2">[{{ $opsi->opsi }}]</span>
                                                            {{ $opsi->teks_pilihan }}
                                                        </div>
                                                        @if ($opsi->is_kunci)
                                                            <span
                                                                class="badge bg-success small rounded-pill px-2 py-1 text-white">
                                                                <i class="bi bi-check-lg"></i> Kunci
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                </div>
                            </div>
                        @empty
                            <div class="text-muted rounded border border-dashed bg-white py-5 text-center">
                                <i class="bi bi-folder-x fs-1 d-block text-secondary mb-2"></i>
                                <p class="fw-semibold mb-0">Belum ada butir pertanyaan yang diinput untuk paket ujian
                                    ini.</p>
                                <small>Silakan tambahkan soal melalui modul Wizard Input Timeline.</small>
                            </div>
                        @endforelse
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="modalNilaiUjian" tabindex="-1"
        aria-labelledby="modalNilaiUjianLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0">
                @if ($ujianNilai)
                    <div class="modal-header bg-primary py-3 text-white">
                        <div>
                            <h5 class="modal-title fw-bold" id="modalNilaiUjianLabel">
                                <i class="bi bi-bar-chart-fill me-2"></i>Nilai Siswa
                            </h5>
                            <h6 class="text-white-50 mb-0">
                                {{ $ujianNilai->nama_ujian }} | Kelas: {{ $ujianNilai->kelas->nama_kelas ?? '-' }} |
                                Mapel: {{ $ujianNilai->mapel->nama_mapel ?? '-' }}
                            </h6>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body bg-light p-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle bg-white mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center" style="width: 60px;">No</th>
                                        <th>NISN</th>
                                        <th>Nama Siswa</th>
                                        <th class="text-center">Nilai</th>
                                        <th class="text-center">Benar</th>
                                        <th class="text-center">Salah</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($daftarNilai as $index => $nilai)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $nilai['nisn'] }}</td>
                                            <td>{{ $nilai['nama'] }}</td>
                                            <td class="text-center fw-bold">{{ $nilai['nilai'] ?? '-' }}</td>
                                            <td class="text-center">{{ $nilai['benar'] ?? '-' }}</td>
                                            <td class="text-center">{{ $nilai['salah'] ?? '-' }}</td>
                                            <td class="text-center">
                                                @if ($nilai['status'] === 'selesai')
                                                    <span class="badge bg-success">Selesai</span>
                                                @elseif ($nilai['status'] === 'mengerjakan')
                                                    <span class="badge bg-warning text-dark">Mengerjakan</span>
                                                @else
                                                    <span class="badge bg-secondary">Tidak mengikuti</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">Belum ada data siswa
                                                untuk kelas ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="{{ route('ujian.export-nilai', $ujianNilai->id) }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('livewire:init', () => {
            const modalNilaiElement = document.getElementById('modalNilaiUjian');
            if (modalNilaiElement) {
                const modalNilaiInstance = bootstrap.Modal.getOrCreateInstance(modalNilaiElement, {
                    focus: false
                });

                window.addEventListener('open-modal-modalNilaiUjian', () => modalNilaiInstance.show());
                modalNilaiElement.addEventListener('hidden.bs.modal', () => {
                    if (document.activeElement && document.activeElement !== document.body) {
                        document.activeElement.blur();
                    }
                });
            }
        });
    </script>
@endpush
