<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Role;
use App\Models\User;
use App\Models\Sekolah;
use App\Models\Jurusan;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $kelas_id;
    public $isEdit = false;
    public $sekolah_id = '';
    public $nama_kelas = '';
    public $jurusan_id = '';
    public function rules()
    {
        return [
            'sekolah_id' => auth()->user()->hasRole('superadmin') ? 'required|exists:sekolahs,id' : 'nullable',
            'nama_kelas' => 'required|string',
            'jurusan_id' => 'nullable|exists:jurusans,id',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Instansi Sekolah',
        'nama_kelas' => 'Nama Kelas',
        'jurusan_id' => 'Jurusan',
    ];
    public function rendering()
    {
        if (!$this->isEdit) {
            $this->clearValidation();
        }
    }
    #[On('editRow')]
    public function edit($id)
    {
        $kelas = Kelas::findOrFail($id);
        $this->kelas_id = $kelas->id;
        $this->sekolah_id = $kelas->sekolah_id;
        $this->nama_kelas = strtoupper($kelas->nama_kelas);

        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-kelasModal');
    }
    public function store()
    {
        $this->validate();

        $userLogin = auth()->user();
        $finalSekolahId = $userLogin->hasRole('superadmin') ? $this->sekolah_id : $userLogin->sekolah_id;

        DB::beginTransaction();
        try {
            Kelas::updateOrCreate(
                ['id' => $this->kelas_id],
                [
                    'sekolah_id' => $finalSekolahId,
                    'nama_kelas' => strtoupper($this->nama_kelas),
                    'jurusan_id' => $this->jurusan_id ?: null,
                ],
            );
            DB::commit();

            LivewireAlert::title('Berhasil!')
                ->text($this->kelas_id ? 'Data Kelas diperbarui' : 'Kelas berhasil ditambahkan')
                ->success()
                ->asToast()
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->error()->timer(6000)->asToast()->show();
            return;
        }

        $this->dispatch('close-modal-kelasModal');
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
            $kelas = Kelas::findOrFail($data['id']);
            $kelas->delete();
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Data berhasil dihapus')->success()->asToast()->show();
            $this->dispatch('refreshDatatable');
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text('Data gagal dihapus')->error()->asToast()->show();
            return;
        }
    }
    #[On('triggerCancel')]
    public function cancel()
    {
        $this->reset(['kelas_id', 'sekolah_id', 'nama_kelas']);
        $this->isEdit = false;
        $this->clearValidation();
    }
    public function with()
    {
        $user = auth()->user();
        $finalSekolahId = $user->hasRole('superadmin') ? $this->sekolah_id : $user->sekolah_id;
        $sekolahQuery = Sekolah::query();
        if ($user->hasRole('superadmin')) {
            // Superadmin bisa melihat semua sekolah, diurutkan berdasarkan nama
            $sekolahQuery->orderBy('nama_sekolah', 'asc');
        } else {
            // Admin biasa hanya bisa melihat sekolahnya sendiri
            $sekolahQuery->where('id', $finalSekolahId);
        }
        $sekolahTerpilih = $finalSekolahId ? Sekolah::find($finalSekolahId) : null;

        return [
            'all_sekolah' => $sekolahQuery->get(['id', 'nama_sekolah', 'jenjang']),
            'sekolahTerpilih' => $sekolahTerpilih,
        ];
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="kelasModal" tabindex="-1" aria-labelledby="kelasModalLabel"
        data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="kelasModalLabel">{{ $isEdit ? 'Edit Data Kelas' : 'Tambah Kelas Baru' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
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

                        <div class="mb-3">
                            <label class="form-label">Nama Kelas</label>
                            <input type="text" wire:model="nama_kelas" class="form-control text-uppercase"
                                placeholder="contoh: IX atau VII" autocomplete="on">
                            @error('nama_kelas')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit"
                            class="btn btn-success">{{ $isEdit ? 'Perbarui Data' : 'Simpan Data' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
