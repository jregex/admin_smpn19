<?php

use Livewire\Component;
use App\Models\{Ekskul, Sekolah, Guru, Rombel};
use App\Enum\Hari;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app_admin', ['title' => 'Ekstrakulikuler'])] class extends Component {
    public $list_anggota = [];
    public $nama_ekskul_terpilih;
    public $ekskul_id, $nama_ekskul, $guru_id, $hari, $jam_mulai, $jam_selesai, $sekolah_id;
    public $pembina_id;
    public $rombel_id;
    public $isEdit = false;
    public $ekskulJson;
    public function mount()
    {
        $this->getFreshData();
    }
    public function rules()
    {
        return [
            'sekolah_id' => auth()->user()->hasRole('superadmin') ? 'required|exists:sekolahs,id' : 'nullable',
            'nama_ekskul' => 'required|string|max:100',
            'guru_id' => 'nullable|exists:gurus,id',
            'hari' => ['required', new Enum(Hari::class)],
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Instansi Sekolah',
        'nama_ekskul' => 'Nama Ekstrakulikuler',
        'guru_id' => 'Pembina',
        'hari' => 'Hari',
        'jam_mulai' => 'Jam Mulai',
        'jam_selesai' => 'Jam Selesai',
    ];

    public function rendering()
    {
        if (!$this->isEdit) {
            $this->clearValidation();
        }
    }
    public function getFreshData()
    {
        $user = auth()->user();
        $query = Ekskul::with(['sekolah', 'pembina']);
        if (!$user->hasRole('superadmin')) {
            $query->where('sekolah_id', $user->sekolah_id);
        }
        if ($user->hasRole('guru')) {
            // Memastikan user guru terhubung dengan id di tabel gurus
            $query->where('guru_id', $user->guru?->id);
        }
        $tahunAjaranId = session()->get('tahun_ajaran_id');

        $data = $query
            ->withCount([
                'rombels' => function ($q) use ($tahunAjaranId) {
                    $q->where('ekskul_rombel.tahun_ajaran_id', $tahunAjaranId);
                },
            ])
            ->latest()
            ->get()
            ->map(function ($ekskul) {
                return [
                    'id' => $ekskul->id,
                    'nama_ekskul' => $ekskul->nama_ekskul,
                    'pembina' => $ekskul->pembina->nama ?? 'Belum ditentukan',
                    'hari' => $ekskul->hari->value,
                    'waktu' => date('H:i', strtotime($ekskul->jam_mulai)) . ' - ' . date('H:i', strtotime($ekskul->jam_selesai)),
                    'total_rombel' => $ekskul->rombels->count() . ' Rombel',
                    'nama_sekolah' => $ekskul->sekolah->nama_sekolah ?? 'Belum diitentukan',
                ];
            })
            ->toArray();

        $this->ekskulJson = json_encode($data);
        return $data;
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-ekskul-data', data: $this->getFreshData());
    }
    #[On('editRow')]
    public function edit($id)
    {
        $ekskul = Ekskul::findOrFail($id);
        $this->ekskul_id = $ekskul->id;
        $this->nama_ekskul = $ekskul->nama_ekskul;
        $this->sekolah_id = $ekskul->sekolah_id;
        $this->guru_id = $ekskul->guru_id;
        $this->hari = $ekskul->hari;
        $this->jam_mulai = date('H:i', strtotime($ekskul->jam_mulai));
        $this->jam_selesai = date('H:i', strtotime($ekskul->jam_selesai));
        $this->clearValidation();

        $this->isEdit = true;
        $this->dispatch('open-modal-ekskulModal');
    }
    public function store()
    {
        $this->validate();

        $userLogin = auth()->user();
        $finalSekolahId = $userLogin->hasRole('superadmin') ? $this->sekolah_id : $userLogin->sekolah_id;
        DB::beginTransaction();
        try {
            Ekskul::updateOrCreate(
                ['id' => $this->ekskul_id],
                [
                    'nama_ekskul' => $this->nama_ekskul,
                    'guru_id' => $this->guru_id,
                    'hari' => $this->hari,
                    'jam_mulai' => $this->jam_mulai,
                    'jam_selesai' => $this->jam_selesai,
                    'sekolah_id' => $finalSekolahId,
                ],
            );
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->error()->timer(6000)->asToast()->show();
            return;
        }
        $this->dispatch('close-modal-ekskulModal');
        $this->dispatch('refreshDatatable');
        LivewireAlert::title('Berhasil!')
            ->text($this->ekskul_id ? 'Data Ekstrakulikuler diperbarui' : 'Ekstrakulikuler berhasil ditambahkan')
            ->success()
            ->asToast()
            ->show();
        $this->cancel();
    }
    #[On('triggerCancel')]
    public function cancel()
    {
        $this->reset(['ekskul_id', 'sekolah_id', 'nama_ekskul', 'hari', 'guru_id', 'jam_mulai', 'jam_selesai', 'rombel_id']);
        $this->isEdit = false;
        $this->clearValidation();
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
    #[On('addRombelRow')]
    public function openAddRombelModal($id)
    {
        $this->ekskul_id = $id;
        $tahunAjaranId = session()->get('tahun_ajaran_id');

        $ekskul = Ekskul::with([
            'rombels' => function ($q) use ($tahunAjaranId) {
                $q->select('nama_rombel', 'nilai', 'rombels.id')->where('ekskul_rombel.tahun_ajaran_id', $tahunAjaranId);
                // Jika menggunakan sistem verifikasi sebelumnya, Anda bisa memfilter:
                // ->where('rombel.status', 'approved');
            },
            'pembina',
        ])->findOrFail($id);
        $this->nama_ekskul_terpilih = $ekskul->nama_ekskul;
        $this->list_anggota = $ekskul->rombels->toArray();
        if (auth()->user()->isGuru()) {
            $this->guru_id = $ekskul->guru_id;
        }
        $this->reset(['rombel_id']);
        $this->clearValidation();
        $this->dispatch('open-modal-tambahRombelModal');
    }
    public function keluarkanRombel($rombelId)
    {
        $ekskul = Ekskul::findOrFail($this->ekskul_id);
        $ekskul->rombels()->detach($rombelId);

        LivewireAlert::title('Berhasil!')->text('Rombel telah dikeluarkan dari ekstrakurikuler.')->success()->asToast()->show();

        // Refresh list anggota di modal & datatable utama
        $this->openAddRombelModal($this->ekskul_id);
        $this->dispatch('refreshDatatable');
    }
    public function storeAnggota()
    {
        $this->validate([
            'rombel_id' => 'required|exists:rombels,id',
        ]);
        $tahunAjaranId = session()->get('tahun_ajaran_id');
        DB::beginTransaction();
        try {
            $ekskul = Ekskul::findOrFail($this->ekskul_id);

            // Menyisipkan data hubungan ke tabel pivot
            $ekskul->rombels()->syncWithoutDetaching([
                $this->rombel_id => [
                    'tahun_ajaran_id' => $tahunAjaranId,
                ],
            ]);

            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Rombel berhasil didaftarkan ke ekskul ini.')->success()->asToast()->show();

            $this->dispatch('close-modal-tambahRombelModal');
            $this->dispatch('refreshDatatable');
            $this->cancel();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text('Rombel gagal di daftarkan')->error()->asToast()->show();
        }
    }
    public function delete(array $data)
    {
        DB::beginTransaction();
        try {
            $ekskul = Ekskul::findOrFail($data['id']);
            $ekskul->delete();
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Data berhasil dihapus')->success()->asToast()->show();
            $this->dispatch('refreshDatatable');
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text('Data gagal dihapus')->error()->asToast()->show();
            return;
        }
    }

    public function render()
    {
        $userLogin = auth()->user();
        $finalId = $userLogin->hasRole('superadmin') ? $this->sekolah_id : $userLogin->sekolah_id;
        $guruQuery = Guru::query();
        $rombelQuery = Rombel::query();
        if ($finalId) {
            $guruQuery->where('sekolah_id', $finalId);
            $rombelQuery->where('sekolah_id', $finalId);
            $guruQuery->orderBy('nama', 'asc');
            $rombelQuery->orderBy('nama_rombel', 'asc');
            if ($this->ekskul_id) {
                $tahunAjaranId = session()->get('tahun_ajaran_id');

                $rombelQuery->whereDoesntHave('ekskuls', function ($query) use ($tahunAjaranId) {
                    $query->where('ekskul_rombel.ekskul_id', $this->ekskul_id)->where('ekskul_rombel.tahun_ajaran_id', $tahunAjaranId);

                    // Jika menggunakan sistem verifikasi pendaftaran mandiri (opsional):
                    // Anda bisa mengizinkan rombel yang statusnya 'rejected' (ditolak) untuk daftar lagi dengan menambah:
                    // ->whereIn('ekskul_rombel.status', ['approved', 'pending']);
                });
            }
        } else {
            $guruQuery->whereRaw('1 = 0');
            $rombelQuery->whereRaw('1 = 0');
        }
        $columns = [['label' => 'Nama Ekskul', 'field' => 'nama_ekskul'], ['label' => 'Pembina', 'field' => 'pembina'], ['label' => 'Hari', 'field' => 'hari'], ['label' => 'Waktu', 'field' => 'waktu'], ['label' => 'Jumlah Anggota', 'field' => 'total_rombel', 'searchable' => false]];
        if (auth()->user()->hasRole('superadmin')) {
            $columns[] = ['label' => 'Sekolah', 'field' => 'nama_sekolah'];
        }
        return $this->view(['columns' => $columns, 'list_hari' => Hari::values(), 'list_guru' => $guruQuery->get(['id', 'nama']), 'list_rombel' => $rombelQuery->with('kelas')->get(['id', 'nama_rombel']), 'all_sekolah' => auth()->user()->hasRole('superadmin') ? Sekolah::orderBy('nama_sekolah', 'asc')->get(['nama_sekolah', 'id']) : null]);
    }
};
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-none">
            <div class="card-header">
                @if (!auth()->user()->hasAnyRole(['guru', 'siswa']))
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ekskulModal">
                        <i class="bi bi-plus-lg"></i> Tambah data
                    </button>
                @endif
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$ekskulJson" :columns="$columns" eventName="refreshDatatable" modalId="ekskulModal"
                    :hasAction="auth()
                        ->user()
                        ->hasAnyRole(['superadmin', 'admin_sekolah'])" updateEvent="update-ekskul-data" />
            </div>
        </div>
    </div>
    @if (!auth()->user()->hasAnyRole(['guru', 'siswa']))
        <div wire:ignore.self class="modal fade" id="ekskulModal" tabindex="-1" data-bs-backdrop="static"
            data-bs-keyboard="false" aria-labelledby="ekskulModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="ekskulModalLabel">
                            {{ $isEdit ? 'Edit Data Ekskul' : 'Tambah Ekskul Baru' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            wire:click="cancel" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="store">
                        <div class="modal-body">
                            @if (auth()->user()->hasRole('superadmin'))
                                <div class="mb-3">
                                    <label class="form-label">Sekolah</label>
                                    <select wire:model="sekolah_id" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($all_sekolah as $item)
                                            <option value="{{ $item->id }}">{{ $item->nama_sekolah }}</option>
                                        @endforeach
                                    </select>
                                    @error('sekolah_id')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Eksktrkulikuler</label>
                                    <input type="text" wire:model="nama_ekskul" class="form-control"
                                        placeholder="contoh: Pramuka" autocomplete="on">
                                    @error('nama_ekskul')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Pembina</label>
                                    <select wire:model="guru_id" class="form-select">
                                        <option value="">-- Pilih Pembina --</option>
                                        @foreach ($list_guru as $gr)
                                            <option value="{{ $gr->id }}">{{ $gr->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hari</label>
                                <select wire:model="hari" class="form-select">
                                    <option value="">-- Pilih Hari --</option>
                                    @foreach ($list_hari as $h)
                                        <option value="{{ $h }}">{{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3" wire:ignore x-data x-init="flatpickr($refs.jamMulai, {
                                    enableTime: true,
                                    noCalendar: true,
                                    dateFormat: 'H:i',
                                    time_24hr: true,
                                    // Mengirim nilai unmasked/bersih langsung saat waktu dipilih
                                    onChange: function(selectedDates, dateStr) {
                                        $wire.jam_mulai = dateStr;
                                    }
                                });">
                                    <label class="form-label">Jam Mulai</label>
                                    <input type="text" wire:model="jam_mulai" x-ref="jamMulai" class="form-control"
                                        id="jam_mulai">
                                </div>
                                <div class="col-6 mb-3" wire:ignore x-data x-init="flatpickr($refs.jamSelesai, {
                                    enableTime: true,
                                    noCalendar: true,
                                    dateFormat: 'H:i',
                                    time_24hr: true,
                                    // Mengirim nilai unmasked/bersih langsung saat waktu dipilih
                                    onChange: function(selectedDates, dateStr) {
                                        $wire.jam_selesai = dateStr;
                                    }
                                });">
                                    <label class="form-label">Jam Selesai</label>
                                    <input type="text" wire:model="jam_selesai" class="form-control"
                                        x-ref="jamSelesai" id="jam_selesai">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" wire:click="cancel" class="btn btn-secondary"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" id="coba" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @if (!auth()->user()->hasRole('siswa'))
        <div wire:ignore.self class="modal fade" id="tambahRombelModal" tabindex="-1" aria-hidden="true"
            data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Masukkan Anggota Rombel</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            wire:click="cancel"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="storeAnggota">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <select wire:model="rombel_id" class="form-control">
                                        <option value="">-- Cari Nama Rombel --</option>
                                        @forelse ($list_rombel as $rombel)
                                            <option value="{{ $rombel->id }}">{{ $rombel->nama_rombel }}</option>
                                        @empty
                                            <option value="" disabled>Tidak ada rombel tersedia</option>
                                        @endforelse
                                    </select>
                                    @error('rombel_id')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success">Daftarkan Anggota</button>
                                </div>
                            </div>

                        </form>
                        <div class="table-responsive">
                            <table class="table-hover table-striped table border align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">No</th>
                                        <th>Nama Rombel</th>
                                        <th>Nilai</th>
                                        <th style="width: 15%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($list_anggota as $index => $rombel)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $rombel['nama_rombel'] }}</td>
                                            <td>{{ $rombel['nilai'] ?? '-' }}</td>

                                            <td class="text-center">
                                                @if (auth()->user()->isGuru() && auth()->user()->guru->id == $guru_id)
                                                    <button wire:click="keluarkanRombel{{ $rombel['id'] }})"
                                                        wire:confirm="Apakah Anda yakin ingin mengeluarkan rombel ini dari anggota ekskul?"
                                                        class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-box-arrow-right"></i>
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
                                                Belum ada rombel yang terdaftar di tahun ajaran ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
    @endif
</div>
