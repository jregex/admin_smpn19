<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Mapel;
use App\Models\Sekolah;
use App\Models\Jurusan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $mapel_id;
    public $isEdit = false;
    public $sekolah_id = '';
    public $jurusan_id = '';
    public $kode_mapel = '';
    public $nama_mapel = '';
    public $kelompok = 'Umum';

    public function rules()
    {
        return [
            'sekolah_id' => auth()->user()->hasRole('superadmin') ? 'required|exists:sekolahs,id' : 'nullable',
            'nama_mapel' => 'required|string|max:100',
            'kode_mapel' => $this->mapel_id ? 'required|string|max:20' : 'required|string|max:20|unique:mapels,kode_mapel',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Instansi Sekolah',
        'kode_mapel' => 'Kode Mata Pelajaran',
        'nama_mapel' => 'Nama Mata Pelajaran',
        'kelompok' => 'Kelompok Mapel',
    ];
    public function rendering()
    {
        if (!$this->isEdit) {
            $this->clearValidation();
        }
    }
    public function updatedSekolahId()
    {
        $this->reset('jurusan_id');
    }
    #[On('editRow')]
    public function edit($id)
    {
        $mapel = Mapel::findOrFail($id);
        $this->mapel_id = $mapel->id;
        $this->sekolah_id = $mapel->sekolah_id;
        $this->kode_mapel = strtoupper($mapel->kode_mapel);
        $this->nama_mapel = $mapel->nama_mapel;
        $this->jurusan_id = $mapel->jurusan_id ?? '';

        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-mapelModal');
    }
    public function store()
    {
        $userLogin = auth()->user();
        $finalSekolahId = $userLogin->hasRole('superadmin') ? $this->sekolah_id : $userLogin->sekolah_id;

        // Reset jurusan jika ternyata sekolahnya SD/SMP
        $sekolah = Sekolah::find($finalSekolahId);
        if ($sekolah && !$userLogin->sekolah->jenjang == 'SMK') {
            $this->jurusan_id = null;
        }

        $this->validate();

        DB::beginTransaction();
        try {
            Mapel::updateOrCreate(
                ['id' => $this->mapel_id],
                [
                    'sekolah_id' => $finalSekolahId,
                    'jurusan_id' => $this->jurusan_id ?: null,
                    'kode_mapel' => strtoupper($this->kode_mapel),
                    'nama_mapel' => $this->nama_mapel,
                ],
            );
            DB::commit();

            LivewireAlert::title('Berhasil!')
                ->text($this->mapel_id ? 'Mata pelajaran diperbarui' : 'Mata pelajaran berhasil ditambahkan')
                ->success()
                ->asToast()
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal Menyimpan!')
                ->text('Detail Error: ' . $th->getMessage())
                ->error()
                ->show();
            return;
        }

        $this->dispatch('close-modal-mapelModal');
        $this->dispatch('refreshDatatable');
        $this->cancel();
    }
    #[On('triggerDeleteConfirm')]
    public function triggerDeleteConfirm($id)
    {
        LivewireAlert::title('Hapus Data')
            ->text('Apakah Anda yakin ingin menghapus data ini?')
            ->asConfirm()
            ->onConfirm('delete', ['id' => $id])
            ->show();
    }

    public function delete(array $data)
    {
        DB::beginTransaction();
        try {
            Mapel::findOrFail($data['id'])->delete();
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Data berhasil dihapus')->success()->asToast()->show();
            $this->refreshTable();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text('Data gagal dihapus')->error()->asToast()->show();
        }
    }
    #[On('triggerCancel')]
    public function cancel()
    {
        $this->reset(['mapel_id', 'sekolah_id', 'kode_mapel', 'nama_mapel']);
        $this->isEdit = false;
        $this->clearValidation();
    }
    public function with()
    {
        $user = auth()->user();
        $finalSekolahId = $user->hasRole('superadmin') ? $this->sekolah_id : $user->sekolah_id;
        $sekolahTerpilih = $finalSekolahId ? Sekolah::find($finalSekolahId) : null;

        $jurusanQuery = Jurusan::query();
        if ($sekolahTerpilih && in_array($sekolahTerpilih->jenjang, ['SMK'])) {
            $jurusanQuery->where('sekolah_id', $sekolahTerpilih->id)->orderBy('nama_jurusan', 'asc');
        } else {
            $jurusanQuery->whereRaw('1 = 0');
        }

        return [
            'all_sekolah' => $user->hasRole('superadmin') ? Sekolah::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah', 'jenjang']) : null,
            'all_jurusan' => $jurusanQuery->get(['id', 'nama_jurusan', 'kode_jurusan']),
            'sekolahTerpilih' => $sekolahTerpilih,
        ];
    }
};
?>

<div wire:ignore.self class="modal fade" id="mapelModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">{{ $isEdit ? 'Edit Mata Pelajaran' : 'Tambah Mapel Baru' }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    wire:click="cancel"></button>
            </div>
            <form wire:submit.prevent="store">
                <div class="modal-body">
                    @if (auth()->user()->hasRole('superadmin'))
                        <div class="mb-3">
                            <label class="form-label">Sekolah</label>
                            <select wire:model.live="sekolah_id" class="form-select">
                                <option value="">-- Pilih Sekolah --</option>
                                @foreach ($all_sekolah as $item)
                                    <option value="{{ $item->id }}">{{ $item->nama_sekolah }} [{{ $item->jenjang }}]
                                    </option>
                                @endforeach
                            </select>
                            @error('sekolah_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kode Mapel</label>
                            <input type="text" wire:model="kode_mapel" class="form-control text-uppercase"
                                placeholder="Ex: MTK">
                            @error('kode_mapel')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nama Mata Pelajaran</label>
                            <input type="text" wire:model="nama_mapel" class="form-control"
                                placeholder="Ex: Matematika Wajib">
                            @error('nama_mapel')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kelompok Mapel</label>
                        <select wire:model="kelompok" class="form-select">
                            <option value="Umum">Umum</option>
                            <option value="Nasional">Kelompok A (Muatan Nasional)</option>
                            <option value="Kewilayahan">Kelompok B (Muatan Kewilayahan)</option>
                        </select>
                        @error('kelompok')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($sekolahTerpilih && $sekolahTerpilih->jenjang == 'SMK')
                        <div class="mb-3">
                            <label class="form-label">Khusus Jurusan <span class="text-muted small">(Kosongkan jika
                                    mapel umum)</span></label>
                            <select wire:model="jurusan_id" class="form-select">
                                <option value="">-- Semua Jurusan (Mapel Umum) --</option>
                                @foreach ($all_jurusan as $jur)
                                    <option value="{{ $jur->id }}">{{ $jur->nama_jurusan }}
                                        [{{ $jur->kode_jurusan }}]</option>
                                @endforeach
                            </select>
                            @error('jurusan_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="cancel">Batal</button>
                    <button type="submit"
                        class="btn btn-success">{{ $isEdit ? 'Perbarui Data' : 'Simpan Data' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
