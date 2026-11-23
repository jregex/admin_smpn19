<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Sekolah;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Format;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
    public $sekolah_id;
    public $isEdit = false;
    public $npsn;
    public $nama_sekolah;
    public $jenjang;
    public $alamat;
    public $email;
    public $logo;
    public $visi;

    public function rules()
    {
        return [
            'npsn' => $this->isEdit ? 'required' : 'required|unique:sekolahs,npsn',
            'nama_sekolah' => 'required',
            'jenjang' => 'required',
            'alamat' => 'nullable',
            'email' => $this->isEdit ? 'nullable|email|max:200' : 'nullable|email|max:200|unique:sekolahs,email',
            'logo' => 'nullable|image|max:2048',
            'visi' => 'nullable|string',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Id Sekolah',
        'nama_sekolah' => 'Nama Sekolah',
        'email' => 'Email Sekolah',
        'alamat' => 'Alamat Sekolah',
        'npsn' => 'NPSN',
        'logo' => 'Logo Sekolah',
        'jenjang' => 'Jenjang',
        'visi' => 'Visi dan Misi Sekolah',
    ];

    public function store()
    {
        $this->validate();
        DB::beginTransaction();
        try {
            if ($this->sekolah_id) {
                $sekolah = Sekolah::findOrFail($this->sekolah_id);
                if ($this->logo && is_object($this->logo)) {
                    if (!Storage::exists('/images/logo')) {
                        Storage::makeDirectory('/images/logo', 0o775, true);
                    }
                    $filename = $this->npsn . '_' . time() . '.png';
                    $img = Image::decode($this->logo)->scale(300);
                    $img->encodeUsingFormat(Format::PNG, progressive: true, quality: 90)->save(Storage::path('images/logo/' . $filename));
                    if ($sekolah->logo !== 'default.png') {
                        Storage::disk('local')->delete('images/logo/' . $sekolah->logo);
                    }
                } else {
                    $filename = $sekolah->logo;
                }
                $sekolah->update([
                    'nama_sekolah' => $this->nama_sekolah,
                    'email' => $this->email,
                    'alamat' => $this->alamat,
                    'npsn' => $this->npsn,
                    'jenjang' => $this->jenjang,
                    'logo' => $filename,
                    'visi' => $this->visi,
                ]);
            } else {
                if ($this->logo && is_object($this->logo)) {
                    if (!Storage::exists('/images/logo')) {
                        Storage::makeDirectory('/images/logo', 0o775, true);
                    }
                    $filename = $this->npsn . '_' . time() . '.png';
                    $img = Image::decode($this->logo)->scale(300);
                    $img->encodeUsingFormat(Format::PNG, progressive: true, quality: 90)->save(Storage::path('images/logo/' . $filename));
                } else {
                    $filename = 'default.png';
                }
                Sekolah::create([
                    'nama_sekolah' => $this->nama_sekolah,
                    'email' => $this->email,
                    'alamat' => $this->alamat,
                    'npsn' => $this->npsn,
                    'jenjang' => $this->jenjang,
                    'logo' => $filename,
                    'visi' => $this->visi,
                ]);
            }
            DB::commit();
            LivewireAlert::title('Berhasil!')
                ->text($this->sekolah_id ? 'Data Sekolah diperbarui' : 'Sekolah berhasil ditambahkan')
                ->success()
                ->asToast()
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->timer(8000)->error()->asToast()->show();
            return;
        }
        $this->dispatch('close-modal-sekolahModal');
        $this->dispatch('refreshDatatable');
        $this->cancel();
    }
    #[On('editRow')]
    public function edit($id)
    {
        $sekolah = Sekolah::findOrFail($id);
        $this->sekolah_id = $sekolah->id;
        $this->npsn = $sekolah->npsn;
        $this->nama_sekolah = $sekolah->nama_sekolah;
        $this->jenjang = $sekolah->jenjang;
        $this->alamat = $sekolah->alamat;
        $this->email = $sekolah->email;
        $this->logo = $sekolah->logo;
        $this->visi = $sekolah->visi;
        $this->isEdit = true;
        $this->dispatch('open-modal-sekolahModal');
    }
    #[On('triggerCancel')]
    public function cancel()
    {
        $this->reset(['sekolah_id', 'npsn', 'email', 'nama_sekolah', 'jenjang', 'alamat', 'logo', 'visi']);
        if (Storage::disk('local')->exists('livewire-tmp')) {
            Storage::disk('local')->deleteDirectory('livewire-tmp');
        }
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
    public function delete(array $data)
    {
        DB::beginTransaction();
        try {
            $sekolah = Sekolah::findOrFail($data['id']);
            $sekolah->delete();
            if ($sekolah->logo && $sekolah->logo !== 'default.png') {
                Storage::disk('local')->delete('images/logo/' . $sekolah->logo);
            }
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
    <div wire:ignore.self class="modal fade" id="sekolahModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" aria-labelledby="sekolahModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="sekolahModalLabel">
                        {{ $isEdit ? 'Edit Data Sekolah' : 'Tambah Sekolah Baru' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" wire:click="cancel"
                        aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NPSN (10 Digit)</label>
                                <input type="text" wire:model="npsn" name="npsn" class="form-control"
                                    placeholder="Contoh: 0043211234">
                                @error('npsn')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Sekolah</label>
                                <input type="text" wire:model="nama_sekolah" name="nama_sekolah" class="form-control"
                                    placeholder="Masukkan nama sekolah">
                                @error('nama_sekolah')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Jenjang</label>
                                <select wire:model="jenjang" name="jenjang" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                </select>
                                @error('jenjang')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Email (Opsional)</label>
                                <input type="email" wire:model="email" name="email" class="form-control"
                                    name="email" autocomplete="on" placeholder="namasekolah@email.sch.id">
                                @error('email')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Visi dan Misi</label>
                            <textarea type="text" wire:model="visi" name="visi" class="form-control" placeholder="Visi dan Misi sekolah"
                                rows="5"></textarea>
                            @error('visi')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea type="text" wire:model="alamat" name="alamat" class="form-control" placeholder="Alamat sekolah"
                                rows="5"></textarea>
                            @error('alamat')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3"
                                wire:key="preview-container-{{ is_object($logo) ? $logo->temporaryUrl() : $logo }}">
                                @if ($logo && method_exists($logo, 'temporaryUrl'))
                                    <img id="previewImg" src="{{ $logo->temporaryUrl() }}" height="300" width="300"
                                        class="img-thumbnail">
                                @else
                                    <img id="previewImg" height="300" width="300" class="img-thumbnail"
                                        src="{{ $isEdit && $logo ? asset('gambar/images/logo/' . $logo) : asset('gambar/images/logo/default.png') }}">
                                @endif
                            </div>
                            <div class="col-md-9">
                                <div class="custom-file mb-2">
                                    <input type="file" class="form-control form-control-alternative" name="logo"
                                        wire:model="logo" id="image" placeholder="Input file">
                                </div>
                                @error('logo')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
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
