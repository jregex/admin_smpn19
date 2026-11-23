<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Illuminate\Support\Facades\Hash;
use App\Models\{User, Sekolah};
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Format;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

new #[Layout('layouts::app_admin', ['title' => 'Pengaturan'])] class extends Component {
    use WithFileUploads;

    #[Url(keep: true)]
    public $tab = 'profile';
    public $npsn, $nama_sekolah, $visi, $logo, $alamat;
    public $current_password;
    public $password;
    public $password_confirmation;
    protected $allowedTabs = ['profile', 'security'];
    public function mount()
    {
        if (!in_array($this->tab, $this->allowedTabs)) {
            $this->tab = 'profile';
        }
        $this->getProfile();
    }
    private function getProfile()
    {
        if (auth()->user()->hasRole('admin_sekolah')) {
            $sekolah = auth()->user()->sekolah;
            $this->npsn = $sekolah->npsn;
            $this->nama_sekolah = $sekolah->nama_sekolah;
            $this->visi = $sekolah->visi;
            $this->logo = $sekolah->logo;
            $this->alamat = $sekolah->alamat;
        }
    }
    public function changeTab($targetTab)
    {
        if (in_array($targetTab, $this->allowedTabs)) {
            $this->tab = $targetTab;
        }
    }
    public function changeProfile()
    {
        $rules = [
            'npsn' => 'nullable|numeric',
            'nama_sekolah' => 'required|string',
            'visi' => 'nullable|string',
            'logo' => $this->logo && is_object($this->logo) ? 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048' : 'nullable',
            'alamat' => 'nullable|string',
        ];
        $messages = [
            'npsn.numeric' => 'NPSN berupa angka',
            'nama_sekolah.required' => 'nama sekolah wajib diisi.',
            'logo.image' => 'File yang diunggah harus berupa gambar.',
            'logo.mime' => 'Format gambar harus jpeg, png, jpg, atau webp.',
            'logo.max' => 'Ukuran gambar maksimal adalah 2 MB.',
        ];
        $this->validate($rules, $messages);
        DB::beginTransaction();
        try {
            $sekolah = auth()->user()->sekolah;
            if ($this->logo && is_object($this->logo)) {
                if (!Storage::exists('/images/logo')) {
                    Storage::makeDirectory('/images/logo', 0o775, true);
                }
                $filename = Str::slug(strtolower($this->nama_sekolah)) . '_' . time() . '.webp';
                $img = Image::decode($this->logo)->scale(300);
                $img->encodeUsingFormat(Format::WEBP, progressive: true, quality: 90)->save(Storage::path('images/logo/' . $filename));
                if ($sekolah->logo !== 'default.webp') {
                    Storage::disk('local')->delete('images/logo/' . $sekolah->logo);
                }
            } else {
                $filename = $sekolah->logo;
            }
            $dataUpdate = [
                'npsn' => $this->npsn,
                'nama_sekolah' => $this->nama_sekolah,
                'visi' => $this->visi,
                'alamat' => $this->alamat,
                'logo' => $filename,
            ];
            Sekolah::where('id', auth()->user()->sekolah_id)->update($dataUpdate);
            $sekolah->update($dataUpdate);
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Profil sekolah berhasil diperbarui')->success()->asToast()->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            // LivewireAlert::title('Gagal!')->text('Data gagal diperbarui')->error()->asToast()->show();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->timer(8000)->error()->asToast()->show();
            $this->getProfile();
            return;
        }
        $this->tab = 'profile';
    }
    public function updatePassword()
    {
        // 1. Validasi Input Kriteria Keamanan Password
        $this->validate(
            [
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed|different:current_password',
            ],
            [
                'current_password.required' => 'Password saat ini tidak boleh sekarang',
                'password.required' => 'Password baru tidak boleh kosong',
                'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
                'password.different' => 'Password baru harus berbeda dengan password saat ini.',
                'password.min' => 'Password baru minimal harus 8 karakter.',
            ],
        );

        $user = auth()->user();

        // 2. Validasi Apakah Password Saat Ini Benar
        if (!Hash::check($this->current_password, $user->password)) {
            LivewireAlert::title('Gagal!')->text('Password saat ini yang Anda masukkan salah.')->error()->asToast()->show();
            return;
        }

        // 3. Eksekusi Perubahan ke Database
        $user->update([
            'password' => bcrypt($this->password),
        ]);

        $this->resetPasswordForm();
        $this->tab = 'security';
        LivewireAlert::title('Berhasil!')->text('password berhasil diperbarui')->success()->asToast()->show();
    }
    public function resetPasswordForm()
    {
        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
    }
};
?>
@push('css')
    <style>
        .tab-smooth-enter {
            transition: opacity 300ms ease-out, transform 300ms ease-out;
        }

        .tab-smooth-enter-start {
            opacity: 0;
            transform: translateY(1rem);
        }

        .tab-smooth-enter-end {
            opacity: 1;
            transform: translateY(0);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

<div>
    <div class="row g-3" x-data="{ currentTab: @entangle('tab') }" wire:key="settings-wrapper">
        <!-- Left rail -->
        <div class="col-md-3">
            <div class="list-group list-group-flush nav nav-pills flex-column" id="settings-nav" role="tablist">
                <button x-on:click="currentTab = 'profile'" :class="{ 'active': currentTab === 'profile' }"
                    class="list-group-item list-group-item-action" type="button" role="tab">
                    <i class="bi bi-person me-2" aria-hidden="true"></i>Profil Sekolah
                </button>

                <button x-on:click="currentTab = 'security'" :class="{ 'active': currentTab === 'security' }"
                    class="list-group-item list-group-item-action" type="button" role="tab">
                    <i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Keamanan
                </button>
            </div>
        </div>
        <div class="col-md-9">
            <div class="tab-content position-relative">
                <!-- Profile -->
                <div wire:show="currentTab === 'profile'" x-transition:enter="tab-smooth-enter"
                    x-transition:enter-start="tab-smooth-enter-start" x-transition:enter-end="tab-smooth-enter-end"
                    wire:key="pane-profile" id="profile" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h3 class="card-title">Profil Sekolah</h3>
                        </div>
                        <div class="card-body">
                            <form class="row g-3" wire:submit.prevent="changeProfile">
                                <div class="col-md-6 position-relative">
                                    <label class="form-label" for="settings-name">NPSN </label>
                                    <div class="input-group has-validation">
                                        <input type="text" class="form-control @error('npsn') is-invalid @enderror"
                                            id="settings-name" wire:model="npsn" />
                                        @error('npsn')
                                            <div class="invalid-tooltip small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label" for="settings-name">Nama sekolah </label>
                                    <div class="input-group has-validation">
                                        <input type="text"
                                            class="form-control @error('nama_sekolah') is-invalid @enderror"
                                            id="settings-name" wire:model="nama_sekolah" />
                                        @error('nama_sekolah')
                                            <div class="invalid-tooltip small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label class="form-label" for="visi"> Visi misi </label>
                                    <div class="input-group has-validation">
                                        <textarea name="visi" wire:model="visi" id="visi" rows="5"
                                            class="form-control @error('visi') is-invalid @enderror"></textarea>
                                        @error('visi')
                                            <div class="invalid-tooltip small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="alamat"> Alamat </label>
                                    <textarea name="alamat" wire:model="alamat" id="alamat" rows="5" class="form-control"></textarea>
                                </div>
                                <div class="col-md-3"
                                    wire:key="preview-container-{{ is_object($logo) ? $logo->temporaryUrl() : $logo }}">
                                    @if ($logo && method_exists($logo, 'temporaryUrl'))
                                        <img id="previewImg" src="{{ $logo->temporaryUrl() }}" height="300"
                                            width="300" class="img-thumbnail">
                                    @else
                                        <img id="previewImg" height="300" width="300" class="img-thumbnail"
                                            src="{{ $logo ? asset('gambar/images/logo/' . $logo) : asset('gambar/images/logo/default.webp') }}">
                                    @endif
                                </div>
                                <div class="col-md-9">
                                    <div class="custom-file mb-2">
                                        <input type="file" class="form-control form-control-alternative"
                                            name="logo" wire:model="logo" id="image" placeholder="Input file">
                                    </div>
                                    @error('logo')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-12 border-top d-flex justify-content-end mt-4 pt-2">
                                    <button type="submit" class="btn btn-success px-4">
                                        <span wire:loading wire:target="changeProfile"
                                            class="spinner-border spinner-border-sm me-1" role="status"
                                            aria-hidden="true"></span>
                                        Perbarui profil
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {{-- end profile --}}
            <!-- Security -->
            <div wire:show="currentTab === 'security'" x-transition:enter="tab-smooth-enter"
                x-transition:enter-start="tab-smooth-enter-start" x-transition:enter-end="tab-smooth-enter-end"
                wire:key="pane-security" id="security" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h3 class="card-title">Keamanan dan password</h3>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" wire:submit.prevent="updatePassword">
                            <div class="col-md-12 position-relative">
                                <label class="form-label" for="pwd-current"> Password saat ini </label>
                                <div class="input-group has-validation">
                                    <input type="password"
                                        class="form-control @error('current_password') is-invalid @enderror"
                                        wire:model="current_password" id="pwd-current" />
                                    @error('current_password')
                                        <div class="invalid-tooltip small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label class="form-label" for="pwd-new"> Password baru </label>
                                <div class="input-group has-validation">
                                    <input type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        wire:model="password" id="pwd-new" />
                                    @error('password')
                                        <div class="invalid-tooltip small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pwd-confirm">
                                    Konfirmasi password
                                </label>
                                <div class="input-group has-validation">
                                    <input type="password" class="form-control" wire:model="password_confirmation"
                                        id="pwd-confirm" />
                                </div>
                            </div>
                            <div class="col-12 border-top d-flex justify-content-end mt-4 pt-2">
                                <button type="submit" class="btn btn-success px-4">
                                    <span wire:loading wire:target="updatePassword"
                                        class="spinner-border spinner-border-sm me-1" role="status"
                                        aria-hidden="true"></span>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {{-- end security --}}
        </div>
    </div>
</div>
