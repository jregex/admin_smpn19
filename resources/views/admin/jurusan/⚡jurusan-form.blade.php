<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use App\Models\Sekolah;
use App\Models\Jurusan;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $jurusan_id;
    public $isEdit = false;
    public $sekolah_id = '';
    public $nama_jurusan = '';
    public $kode_jurusan = '';
    public function rules()
    {
        return [
            'sekolah_id' => auth()->user()->hasRole('superadmin') ? 'required|exists:sekolahs,id' : 'nullable',
            'nama_jurusan' => 'required|string',
            'kode_jurusan' => $this->jurusan_id ? 'required|string' : 'required|string|unique:jurusans,kode_jurusan',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Instansi Sekolah',
        'nama_jurusan' => 'Nama Jurusan',
        'kode_jurusan' => 'Kode Jurusan',
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
        $jurusan = Jurusan::findOrFail($id);
        $this->jurusan_id = $jurusan->id;
        $this->sekolah_id = $jurusan->sekolah_id;
        $this->nama_jurusan = $jurusan->nama_jurusan;
        $this->kode_jurusan = strtoupper($jurusan->kode_jurusan);

        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-jurusanModal');
    }
    public function store()
    {
        $this->validate();

        $userLogin = auth()->user();
        $finalSekolahId = $userLogin->hasRole('superadmin') ? $this->sekolah_id : $userLogin->sekolah_id;

        DB::beginTransaction();
        try {
            Jurusan::updateOrCreate(
                ['id' => $this->jurusan_id],
                [
                    'sekolah_id' => $finalSekolahId,
                    'nama_jurusan' => $this->nama_jurusan,
                    'kode_jurusan' => strtoupper($this->kode_jurusan),
                ],
            );
            DB::commit();

            LivewireAlert::title('Berhasil!')
                ->text($this->jurusan_id ? 'Data Jurusan diperbarui' : 'Jurusan berhasil ditambahkan')
                ->success()
                ->asToast()
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->error()->asToast()->show();
            return;
        }

        $this->dispatch('close-modal-jurusanModal');
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
            $jurusan = Jurusan::findOrFail($data['id']);
            $jurusan->delete();
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
        $this->reset(['jurusan_id', 'sekolah_id', 'nama_jurusan', 'kode_jurusan']);
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

        return [
            'all_sekolah' => $sekolahQuery->get(['id', 'nama_sekolah', 'jenjang']),
        ];
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="jurusanModal" tabindex="-1" aria-labelledby="jurusanModalLabel"
        data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="jurusanModalLabel">
                        {{ $isEdit ? 'Edit Data Jurusan' : 'Tambah Jurusan Baru' }}
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
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kode Jurusan</label>
                                <input type="text" wire:model="kode_jurusan" class="form-control text-uppercase"
                                    placeholder="Ex: TKJ">
                                @error('kode_jurusan')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Nama Jurusan</label>
                                <input type="text" wire:model="nama_jurusan" class="form-control" autocomplete="on">
                                @error('nama_jurusan')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
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
