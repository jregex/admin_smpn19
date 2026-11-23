<?php

use Livewire\Component;
use App\Models\{Ujian, Kelas, Guru, Mapel};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $ujianId = null;
    public $nama_ujian,
        $kelas_id,
        $guru_id,
        $waktu_mulai,
        $durasi,
        $kkpp,
        $deskripsi,
        $pengawas_id,
        $mapel_id,
        $isEdit = false;

    public function mount($ujianId = null)
    {
        if ($ujianId) {
            $this->ujianId = $ujianId;
            $ujian = Ujian::findOrFail($ujianId);

            $this->nama_ujian = $ujian->nama_ujian;
            $this->kelas_id = $ujian->kelas_id;
            $this->guru_id = $ujian->guru_id;
            $this->waktu_mulai = date('Y-m-d H:i', strtotime($ujian->waktu_mulai));
            $this->durasi = $ujian->durasi;
            $this->kkpp = $ujian->kkpp;
            $this->deskripsi = $ujian->deskripsi;
            $this->pengawas_id = $ujian->pengawas_id;
            $this->mapel_id = $ujian->mapel_id;
            $this->isEdit = true;
        }
    }
    public function rules()
    {
        return [
            'kelas_id' => 'required|exists:kelas,id',
            'nama_ujian' => 'required|string|max:150',
            'waktu_mulai' => 'required|date',
            'durasi' => 'required|integer|min:1',
            'kkpp' => 'required|integer|between:1,100',
            'pengawas_id' => 'required|exists:gurus,id',
            'guru_id' => 'nullable|exists:gurus,id',
            'mapel_id' => 'nullable|exists:mapels,id',
        ];
    }
    public function simpanUjian()
    {
        $this->validate();
        $mulai = \Carbon\Carbon::parse($this->waktu_mulai);
        $selesai = $mulai->addMinutes((int) $this->durasi);
        $ujian = Ujian::updateOrCreate(
            ['id' => $this->ujianId], // Kunci pencarian: Jika ID ada maka EDIT, jika null maka TAMBAH
            [
                'sekolah_id' => auth()->user()->sekolah_id,
                'kelas_id' => $this->kelas_id,
                'guru_id' => auth()->user()->isGuru() ? auth()->user()->guru->id : $this->guru_id,
                'nama_ujian' => $this->nama_ujian,
                'deskripsi' => $this->deskripsi,
                'waktu_mulai' => $this->waktu_mulai,
                'waktu_selesai' => $selesai,
                'mapel_id' => $this->mapel_id,
                'durasi' => $this->durasi,
                'kkpp' => $this->kkpp,
                'pengawas_id' => $this->pengawas_id,
                'status' => 'BELUM MULAI',
            ],
        );
        $pesan = $this->ujianId ? 'Konfigurasi ujian berhasil diperbarui.' : 'Konfigurasi berhasil disimpan.';
        LivewireAlert::title('Langkah 1 Selesai')->success()->text($pesan)->asToast()->show();
        $this->cancel();

        $this->dispatch('ujianDisimpan', ujianId: $ujian->id);
    }
    public function cancel()
    {
        $this->reset(['ujianId', 'isEdit', 'kelas_id']);
    }

    public function with()
    {
        return [
            'all_kelas' => Kelas::with('jurusan')
                ->where('sekolah_id', auth()->user()->sekolah_id)
                ->get(),
            'listGuru' => Guru::where('sekolah_id', auth()->user()->sekolah_id)
                ->orderBy('nama', 'asc')
                ->get(),
            'listMapel' => Mapel::with('jurusan')
                ->where('sekolah_id', auth()->user()->sekolah_id)
                ->orderBy('nama_mapel', 'asc')
                ->get(),
        ];
    }
};
?>

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" />
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
@endpush

<div class="card animate-fade-in border-0 shadow-sm">
    <div class="card-body p-3">
        <h5 class="fw-bold"><i class="bi bi-gear-fill text-primary me-2"></i>Langkah 1: Atur Parameter
            Ujian</h5>

        <form wire:submit.prevent="simpanUjian">
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Nama Ujian</label>
                    <input type="text" wire:model="nama_ujian" class="form-control card-input"
                        placeholder="Contoh: Penilaian Harian Bahasa Indonesia">
                    @error('nama_ujian')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Kelas Sasaran</label>
                    <select wire:model="kelas_id" class="form-select card-input">
                        <option value="">-- Pilih Target Kelas --</option>
                        @foreach ($all_kelas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }} [{{ $k->jurusan->kode_jurusan }}]
                            </option>
                        @endforeach
                    </select>
                    @error('kelas_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Mata Pelajaran</label>
                <select wire:model="mapel_id" class="form-select">
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    @foreach ($listMapel as $mapel)
                        <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}
                            [{{ $mapel->kode_mapel ?? '-' }}]-{{ $mapel->jurusan->kode_jurusan ?? 'Umum' }}
                        </option>
                    @endforeach
                </select>
                @error('mapel_id')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah']))
                <div class="mb-3">
                    <label class="form-label fw-bold">Guru Pembuat Ujian</label>
                    <select wire:model="guru_id" class="form-select">
                        <option value="">-- Pilih Guru Pembuat ujian --</option>
                        @foreach ($listGuru as $guru)
                            <option value="{{ $guru->id }}">{{ $guru->nama }}</option>
                        @endforeach
                    </select>
                    @error('guru_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            @endif
            <div class="mb-3">
                <label class="form-label fw-bold">Guru Pengawas Ujian</label>
                <select wire:model="pengawas_id" class="form-select">
                    <option value="">-- Pilih Guru Pengawas Ujian --</option>
                    @foreach ($listGuru as $guru)
                        @if ($guru->id !== auth()->user()->guru?->id)
                            <option value="{{ $guru->id }}">{{ $guru->nama }}</option>
                        @endif
                    @endforeach
                </select>
                @error('pengawas_id')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>

            <div class="row mb-3">
                <div class="col-md-6" wire:ignore x-data x-init="flatpickr($refs.waktuMulai, {
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    'locale': 'id',
                    time_24hr: true,
                    defaultDate: $wire.get('waktu_mulai'),
                    // Mengirim nilai unmasked/bersih langsung saat waktu dipilih
                    onChange: function(selectedDates, dateStr) {
                        $wire.waktu_mulai = dateStr;
                    }
                });">
                    <label class="form-label fw-bold">Waktu Mulai</label>
                    <input type="text" wire:model="waktu_mulai" x-ref="waktuMulai" class="form-control card-input">
                    @error('waktu_mulai')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Durasi (Menit)</label>
                    <input type="number" wire:model="durasi" class="form-control card-input" placeholder="Misal: 90">
                    @error('durasi')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Nilai KKPP Kelulusan</label>
                <input type="number" wire:model="kkpp" class="form-control card-input" placeholder="Misal: 75">
                @error('kkpp')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Tata Tertib / Deskripsi Tambahan</label>
                <textarea wire:model="deskripsi" class="form-control card-input" rows="3" placeholder="Opsional..."></textarea>
            </div>

            <div class="d-flex float-end gap-2 pt-2">
                <a href="{{ route('ujian.index') }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-success px-4">
                    Simpan & Lanjut Pembuatan Soal <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>
