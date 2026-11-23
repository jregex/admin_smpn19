<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Sekolah;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Rombel;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $rombel_id;
    public $isEdit = false;
    public $sekolah_id = '';
    public $nama_rombel = '';
    public $kelas_id = '';
    public $guru_id = '';
    public $tahun_ajaran_id;
    public $isSuperAdmin = false;
    public $authSekolahId = null;
    public function mount()
    {
        $user = auth()->user();
        $this->isSuperAdmin = $user->hasRole('superadmin');
        $this->authSekolahId = $user->sekolah_id;
    }
    public function getFinalSekolahIdProperty()
    {
        return $this->isSuperAdmin ? $this->sekolah_id : $this->authSekolahId;
    }
    public function rules()
    {
        return [
            'sekolah_id' => $this->isSuperAdmin ? 'required|exists:sekolahs,id' : 'nullable',
            'kelas_id' => 'required|exists:kelas,id',
            'nama_rombel' => 'required|string',
            'guru_id' => $this->guru_id ? 'nullable|unique:gurus,id,' . Guru::find($this->guru_id)->id : 'nullable|unique:gurus,id',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Instansi Sekolah',
        'kelas_id' => 'Kelas',
        'nama_rombel' => 'Nama Rombel',
        'guru_id' => 'Walikelas',
    ];
    #[On('editRow')]
    public function edit($id)
    {
        $rombel = Rombel::findOrFail($id);
        $this->rombel_id = $rombel->id;
        $nama_rombel = 'KELAS ' . strtoupper($rombel->nama_rombel);
        $this->nama_rombel = $nama_rombel;
        $this->guru_id = $rombel->guru_id;
        $this->kelas_id = $rombel->kelas_id;
        $this->tahun_ajaran_id = $rombel->tahun_ajaran_id;
        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-rombelModal');
    }

    public function store()
    {
        $this->validate();

        $finalId = $this->finalSekolahId;
        $ta_id = session()->has('tahun_ajaran_id') ? session()->get('tahun_ajaran_id') : '';
        $nama_rombel = 'KELAS ' . strtoupper($this->nama_rombel);

        DB::beginTransaction();
        try {
            Rombel::updateOrCreate(
                ['id' => $this->rombel_id],
                [
                    'sekolah_id' => $finalId,
                    'nama_rombel' => $nama_rombel,
                    'guru_id' => $this->guru_id ?: null,
                    'tahun_ajaran_id' => $ta_id,
                    'kelas_id' => $this->kelas_id,
                ],
            );
            DB::commit();

            LivewireAlert::title('Berhasil!')
                ->text($this->rombel_id ? 'Data Rombel diperbarui' : 'Rombel berhasil ditambahkan')
                ->success()
                ->asToast()
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->error()->timer(6000)->asToast()->show();
            return;
        }

        $this->dispatch('close-modal-rombelModal');
        $this->dispatch('refreshDatatable');
        $this->cancel();
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
            $rombel = Rombel::findOrFail($data['id']);
            $rombel->delete();
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Data berhasil dihapus')->success()->asToast()->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->timer(8000)->error()->asToast()->show();
            return;
        }
        $this->dispatch('refreshDatatable');
    }
    #[On('triggerCancel')]
    public function cancel()
    {
        $this->reset(['rombel_id', 'kelas_id', 'nama_rombel', 'guru_id', 'tahun_ajaran_id', 'sekolah_id']);
        $this->isEdit = false;
        $this->clearValidation();
    }
    public function rendering()
    {
        // Jika sedang TIDAK dalam mode edit (alias sedang tambah data baru),
        // pastikan error validasi lama dibersihkan secara otomatis.
        if (!$this->isEdit) {
            $this->clearValidation();
        }
    }

    public function with()
    {
        $user = auth()->user();
        $finalId = $this->finalSekolahId;
        $guruQuery = Guru::query();
        if ($finalId) {
            $guruQuery->where('sekolah_id', $finalId);
            $guruQuery->orderBy('nama', 'asc');
        } else {
            $guruQuery->whereRaw('1 = 0');
        }
        return [
            'all_guru' => $guruQuery->get(['nama', 'id']),
            'all_kelas' => Kelas::where('sekolah_id', $user->sekolah_id)->get(),
        ];
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="rombelModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" aria-labelledby="rombelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="rombelModalLabel">
                        {{ $isEdit ? 'Edit Data Rombel' : 'Tambah Rombel Baru' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" wire:click="cancel"
                        aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        @if (auth()->user()->hasRole('superadmin'))
                            <div class="mb-3">
                                <label class="form-label">Asal Sekolah</label>
                                <select wire:model.live="sekolah_id" class="form-select">
                                    <option value="">-- Pilih Sekolah --</option>
                                    @foreach ($all_sekolah as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_sekolah }}
                                            [{{ $item->jenjang }}]</option>
                                    @endforeach
                                </select>
                                @error('sekolah_id')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kelas</label>
                                <select wire:model="kelas_id" name="kelas_id" class="form-select">
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach ($all_kelas as $kelas)
                                        <option value="{{ $kelas->id }}">
                                            {{ $kelas->nama_kelas }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kelas_id')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Rombel</label>
                                <input type="text" wire:model="nama_rombel" name="nama_rombel"
                                    class="form-control text-uppercase" placeholder="contoh: IX A atau VII B">
                                @error('nama_rombel')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WaliKelas</label>
                            <select wire:model="guru_id" name="guru_id" class="form-select">
                                <option value="">-- Pilih WaliKelas --</option>
                                @foreach ($all_guru as $guru)
                                    <option value="{{ $guru->id }}">
                                        {{ $guru->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('guru_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
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
</div>
