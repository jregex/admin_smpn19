<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Format;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\{Info, User, Sekolah};

new class extends Component {
    use WithFileUploads;
    public $info_id;
    public $isEdit = false;
    public $judul = '';
    public $isi = '';
    public $target_audiens = 'semua';
    public $is_pin = false;
    public $gambar;
    public $sekolah_id;
    public $dibuat;
    public $pembuat;
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
    public function store()
    {
        $rules = [
            'judul' => 'required|string|min:5|max:150',
            'sekolah_id' => $this->isSuperAdmin ? 'required|exists:sekolahs,id' : 'nullable',
            'isi' => 'required|string|min:10',
            'target_audiens' => 'required|in:semua,guru,siswa',
            'is_pin' => 'boolean',
            'gambar' => $this->gambar && is_object($this->gambar) ? 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048' : 'nullable',
        ];
        $messages = [
            'judul.required' => 'Judul pengumuman wajib diisi.',
            'judul.min' => 'Judul minimal berisi 5 karakter.',
            'judul.max' => 'Judul maksimal berisi 150 karakter.',
            'isi.required' => 'Isi isi pengumuman tidak boleh kosong.',
            'isi.min' => 'Isi isi minimal berisi 10 karakter.',
            'target_audiens.required' => 'Target audiens harus dipilih.',
            'target_audiens.in' => 'Target audiens tidak valid.',
            'gambar.image' => 'File yang diunggah harus berupa gambar.',
            'gambar.mime' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'gambar.max' => 'Ukuran gambar maksimal adalah 2 MB.',
        ];

        $finalId = $this->finalSekolahId;
        // 3. Jalankan Validasi
        $this->validate($rules, $messages);
        DB::beginTransaction();
        try {
            if ($this->info_id) {
                $info = Info::findOrFail($this->info_id);
                if ($this->gambar && is_object($this->gambar)) {
                    if (!Storage::exists('/images/info')) {
                        Storage::makeDirectory('/images/info', 0o775, true);
                    }
                    $filename = Str::slug($this->judul) . '_' . time() . '.jpg';
                    $img = Image::decode($this->gambar)->scale(300);
                    $img->encodeUsingFormat(Format::JPEG, progressive: true, quality: 90)->save(Storage::path('images/info/' . $filename));
                    if ($info->gambar !== 'placeholder.jpg') {
                        Storage::disk('local')->delete('images/info/' . $info->gambar);
                    }
                } else {
                    $filename = $info->gambar;
                }

                $dataUpdate = [
                    'judul' => $this->judul,
                    'isi' => $this->isi,
                    'sekolah_id' => $finalId,
                    'user_id' => auth()->id(),
                    'target_audiens' => $this->target_audiens,
                    'is_pin' => $this->is_pin,
                    'gambar' => $filename,
                ];
                $info->update($dataUpdate);
            } else {
                if ($this->gambar && is_object($this->gambar)) {
                    if (!Storage::exists('/images/info')) {
                        Storage::makeDirectory('/images/info', 0o775, true);
                    }
                    $filename = Str::slug($this->judul) . '_' . time() . '.jpg';
                    $img = Image::decode($this->gambar)->scale(300);
                    $img->encodeUsingFormat(Format::JPEG, progressive: true, quality: 90)->save(Storage::path('images/info/' . $filename));
                } else {
                    $filename = 'placeholder.jpg';
                }
                $info = Info::create([
                    'judul' => $this->judul,
                    'isi' => $this->isi,
                    'sekolah_id' => $finalId,
                    'user_id' => auth()->id(),
                    'target_audiens' => $this->target_audiens,
                    'is_pin' => $this->is_pin,
                    'gambar' => $filename,
                ]);
            }
            DB::commit();
            if (!$this->info_id && $info) {
                $deviceTokens = \App\Models\User::where('sekolah_id', $finalId)
                    ->when($this->target_audiens == 'siswa', function ($query) {
                        return $query->whereHas('role', function ($q) {
                            $q->where('role', $this->target_audiens);
                        });
                    })
                    ->whereNotNull('fcm_token')
                    ->pluck('fcm_token')
                    ->toArray();

                if (!empty($deviceTokens)) {
                    $firebaseService = app(\App\Services\FirebaseService::class);

                    $payloadData = [
                        'screen' => 'info_detail',
                        'id' => strval($info->id),
                        'judul' => strval($info->judul),
                        'isi' => strval($info->isi),
                        'gambar' => strval($info->gambar),
                        'is_pin' => strval($info->is_pin ? 1 : 0),
                        'created_at' => strval($info->created_at->toIso8601String()),
                        'pembuat' => strval(auth()->user()->name),
                    ];

                    foreach ($deviceTokens as $token) {
                        $firebaseService->sendNotification($token, $this->judul, Str::limit($this->isi, 100), $payloadData);
                    }
                }
            }
            LivewireAlert::title('Berhasil!')
                ->text($this->info_id ? 'Data Pengumuman berhasil diperbarui' : 'Pengumuman berhasil ditambahkan')
                ->success()
                ->asToast()
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->error()->timer(6000)->asToast()->show();
            return;
        }
        $this->dispatch('close-modal-infoModal');
        $this->dispatch('refreshDatatable');
        $this->cancel();
    }
    #[On('editRow')]
    public function edit($id)
    {
        $info = Info::findOrFail($id);
        $this->info_id = $info->id;
        $this->judul = $info->judul;
        $this->isi = $info->isi;
        $this->is_pin = $info->is_pin;
        $this->target_audiens = $info->target_audiens;
        $this->sekolah_id = $info->sekolah_id;
        $this->gambar = $info->gambar;
        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-infoModal');
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
            $info = Info::findOrFail($data['id']);
            $info->delete();
            if ($info->gambar != 'placeholder.jpg') {
                Storage::disk('local')->delete('images/info/' . $info->gambar);
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
    #[On('triggerCancel')]
    public function cancel()
    {
        // if ($this->foto && method_exists($this->foto, 'getFilename')) {

        // }

        $this->reset(['info_id', 'judul', 'isi', 'target_audiens', 'is_pin', 'gambar', 'sekolah_id', 'pembuat', 'dibuat']);
        if (Storage::disk('local')->exists('livewire-tmp')) {
            Storage::disk('local')->deleteDirectory('livewire-tmp');
        }
        $this->isEdit = false;
        $this->clearValidation();
    }
    #[On('triggerTutup')]
    public function tutup()
    {
        $this->reset(['info_id', 'judul', 'isi', 'target_audiens', 'is_pin', 'gambar', 'sekolah_id', 'pembuat', 'dibuat']);
        $this->dispatch('close-modal-detailInfoModal');
    }

    #[On('detailModal')]
    public function detailInfo($id)
    {
        $info = Info::with('pembuat')->findOrFail($id);
        $this->judul = $info->judul;
        $this->isi = $info->isi;
        $this->target_audiens = $info->target_audiens;
        $this->dibuat = $info->created_at->format('d M Y H:i');
        $this->pembuat = $info->pembuat->name;
        $this->gambar = $info->gambar;
        $this->dispatch('open-modal-detailInfoModal');
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
        $finalId = $this->finalSekolahId;
        return [
            'all_sekolah' => $this->isSuperAdmin ? Sekolah::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : null,
        ];
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="infoModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <form wire:submit.prevent="store" class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="infoModalLabel">
                        {{ $isEdit ? 'Edit Data Pengumuman' : 'Tambah Pengumuman Baru' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" aria-label="Close" data-bs-dismiss="modal"
                        wire:click="cancel"></button>
                </div>
                <div class="modal-body">
                    @if (auth()->user()->hasRole('superadmin'))
                        <div class="mb-3">
                            <label class="form-label">Asal Sekolah</label>
                            <select wire:model.live="sekolah_id" class="form-select">
                                <option value="">-- Pilih Sekolah --</option>
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
                        <label class="form-label">Judul Info</label>
                        <input type="text" wire:model="judul" class="form-control" placeholder="Tulis judul...">
                        @error('judul')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Target Audiens</label>
                            <select wire:model="target_audiens" class="form-select">
                                <option value="semua">Semua (Guru & Siswa)</option>
                                <option value="guru">Khusus Guru</option>
                                <option value="siswa">Khusus Siswa</option>
                            </select>
                            @error('target_audiens')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-center mb-3 pt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model="is_pin" id="pinCheck">
                                <label class="form-check-label" for="pinCheck">Sematkan di baris paling atas
                                    (Pin)</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isi Pengumuman</label>
                        <textarea wire:model="isi" rows="5" class="form-control" placeholder="Tulis informasi lengkap disini..."></textarea>
                        @error('isi')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"
                            wire:key="preview-container-{{ is_object($gambar) ? $gambar->temporaryUrl() : $gambar }}">
                            @if ($gambar && method_exists($gambar, 'temporaryUrl'))
                                <img id="previewImg" src="{{ $gambar->temporaryUrl() }}" height="300" width="300"
                                    class="img-thumbnail">
                            @else
                                <img id="previewImg" height="300" width="300" class="img-thumbnail"
                                    src="{{ $isEdit && $gambar ? asset('gambar/images/info/' . $gambar) : asset('gambar/images/info/placeholder.jpg') }}">
                            @endif
                        </div>
                        <div class="col-md-9">
                            <div class="custom-file mb-2">
                                <input type="file" class="form-control form-control-alternative" name="gambar"
                                    wire:model="gambar" id="image" placeholder="Input file">
                            </div>
                            @error('gambar')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="cancel">Batal</button>
                    <button type="submit"
                        class="btn btn-success">{{ $isEdit ? 'Perbarui info' : 'Publish info' }}</button>
                </div>
            </form>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="detailInfoModal" tabindex="-1" data-bs-backdrop="static"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-bottom-0 py-3">
                    <h5 class="modal-title fw-bold text-secondary"><i class="bi bi-info-circle me-2"></i>Pratinjau
                        Pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="tutup"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="d-flex align-items-center small text-muted mb-3 flex-wrap gap-2">
                        <span class="badge bg-primary text-uppercase px-2.5 py-1.5">🎯 Target:
                            {{ $target_audiens }}</span>
                        <span>•</span>
                        <span><i class="bi bi-person me-1"></i> {{ $pembuat }}</span>
                        <span>•</span>
                        <span><i class="bi bi-calendar3 me-1"></i> {{ $dibuat }}</span>
                    </div>

                    <h3 class="fw-bold text-dark border-bottom mb-4 pb-2">{{ $judul }}</h3>

                    @if ($gambar)
                        <div class="bg-light mb-4 rounded border p-2 text-center">
                            <img src="{{ asset('gambar/images/info/' . $gambar) }}"
                                class="img-fluid rounded shadow-sm" style="max-height: 300px; object-fit: contain;"
                                alt="Gambar Pengumuman">
                        </div>
                    @endif

                    <div class="text-secondary lh-base px-1"
                        style="white-space: pre-line; text-align: justify; font-size: 0.95rem;">
                        {{ $isi }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
