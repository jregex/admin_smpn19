<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\{Siswa, Kelas, Role, User, Sekolah, Rombel};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Format;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;
    public $siswa_id;
    public $isEdit = false;
    public $nisn = '';
    public $nama = '';
    public $email = '';
    public $no_telp = '';
    public $jenis_kelamin = '';
    public $agama = '';
    public $kelas_id = '';
    public $rombel_id = '';
    public $sekolah_id = '';
    public $tempat_lahir = '';
    public $password = '';
    public $foto;
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
    public function getSekolahTerpilihProperty()
    {
        $id = $this->finalSekolahId;
        return $id ? Sekolah::find($id) : null;
    }

    public function rules()
    {
        return [
            'sekolah_id' => $this->isSuperAdmin ? 'required|exists:sekolahs,id' : 'nullable',
            'rombel_id' => 'required|exists:rombels,id',
            'nama' => 'required|string',
            'nisn' => $this->siswa_id ? 'required|max:10|unique:siswas,nisn,' . $this->siswa_id : 'required|max:18|unique:siswas,nisn',
            'password' => $this->siswa_id ? 'string|min:8' : 'nullable',
            'email' => $this->siswa_id ? 'required|email|unique:users,email,' . Siswa::find($this->siswa_id)->user_id : 'required|email|unique:users,email',
            'jenis_kelamin' => 'required|in:L,P',
            'no_telp' => 'nullable|string|max:15',
            'agama' => 'required|string|in:Islam,Kristen,Budha,Hindu,Kong Hu Cu',
            'foto' => $this->foto && is_object($this->foto) ? 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048' : 'nullable',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Instansi Sekolah',
        'rombel_id' => 'Rombel',
        'email' => 'Email Login',
        'password' => 'Password',
        'nama' => 'Nama Lengkap Guru',
        'jenis_kelamin' => 'Jenis Kelamin',
        'agama' => 'Agama',
        'no_telp' => 'Nomor HP',
        'nisn' => 'NISN',
        'foto' => 'Foto siswa',
    ];
    public function store()
    {
        $this->validate();
        $finalId = $this->finalSekolahId;
        $sekolah = $this->sekolahTerpilih;

        $roleSiswa = Role::where('role', 'siswa')->first();
        DB::beginTransaction();
        try {
            if ($this->siswa_id) {
                $siswa = Siswa::findOrFail($this->siswa_id);
                $user = User::findOrFail($siswa->user_id);
                if ($this->foto && is_object($this->foto)) {
                    if (!Storage::exists('/images/siswa')) {
                        Storage::makeDirectory('/images/siswa', 0o775, true);
                    }
                    $filename = $this->nisn . '_' . time() . '.jpg';
                    $img = Image::decode($this->foto)->scale(300);
                    $img->encodeUsingFormat(Format::JPEG, progressive: true, quality: 90)->save(Storage::path('images/siswa/' . $filename));
                    if ($siswa->foto !== 'default.png') {
                        Storage::disk('local')->delete('images/siswa/' . $siswa->foto);
                    }
                } else {
                    $filename = $siswa->foto;
                }
                $user->update([
                    'name' => $this->nama,
                    'email' => $this->email,
                    'password' => $this->password ? bcrypt($this->password) : $user->password,
                    'role_id' => $roleSiswa->id,
                ]);

                // Susun data pembaruan siswa
                $dataUpdate = [
                    'nama' => $this->nama,
                    'jenis_kelamin' => $this->jenis_kelamin,
                    'rombel_id' => $this->rombel_id,
                    'no_telp' => $this->no_telp,
                    'agama' => $this->agama,
                    'tempat_lahir' => $this->tempat_lahir,
                    'foto' => $filename,
                ];

                $siswa->update($dataUpdate);
            } else {
                if ($this->foto && is_object($this->foto)) {
                    if (!Storage::exists('/images/siswa')) {
                        Storage::makeDirectory('/images/siswa', 0o775, true);
                    }
                    $filename = $this->nisn . '_' . time() . '.jpg';
                    $img = Image::decode($this->foto)->scale(300);
                    $img->encodeUsingFormat(Format::JPEG, progressive: true, quality: 90)->save(Storage::path('images/siswa/' . $filename));
                } else {
                    $filename = 'default.png';
                }
                $namaDepan = Str::before($this->nama, ' ');
                $userBaru = User::create([
                    'sekolah_id' => $finalId,
                    'name' => $this->nama,
                    'email' => $this->email,
                    'password' => bcrypt($namaDepan . '@12345'),
                    'role_id' => $roleSiswa->id,
                    'is_active' => 1,
                ]);
                Siswa::create([
                    'nisn' => $this->nisn,
                    'nama' => $this->nama,
                    'sekolah_id' => $finalId,
                    'no_telp' => $this->no_telp,
                    'agama' => $this->agama,
                    'jenis_kelamin' => $this->jenis_kelamin,
                    'rombel_id' => $this->rombel_id,
                    'user_id' => $userBaru->id,
                    'foto' => $filename,
                    'tempat_lahir' => $this->tempat_lahir ?? '-',
                ]);
            }
            DB::commit();
            LivewireAlert::title('Berhasil!')
                ->text($this->siswa_id ? 'Data Siswa diperbarui' : 'Siswa berhasil ditambahkan')
                ->success()
                ->asToast()
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->timer(8000)->error()->asToast()->show();
        }
        $this->dispatch('close-modal-siswaModal');
        $this->dispatch('refreshDatatable');
        $this->cancel();
    }
    #[On('editRow')]
    public function edit($id)
    {
        $siswa = Siswa::findOrFail($id);
        $this->siswa_id = $siswa->id;
        $this->nisn = $siswa->nisn;
        $this->nama = $siswa->nama;
        $this->email = $siswa->user->email;
        $this->password = '';
        $this->agama = $siswa->agama;
        $this->no_telp = $siswa->no_telp;
        $this->jenis_kelamin = $siswa->jenis_kelamin;
        $this->rombel_id = $siswa->rombel_id;
        $this->tempat_lahir = $siswa->tempat_lahir;
        $this->foto = $siswa->foto;
        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-siswaModal');
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
            $siswa = Siswa::findOrFail($data['id']);
            $user = User::find($siswa->user_id);
            $siswa->delete();
            if ($user) {
                $user->delete();
            }
            if ($siswa->foto != 'default.png') {
                Storage::disk('local')->delete('images/siswa/' . $siswa->foto);
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

        $this->reset(['siswa_id', 'nisn', 'email', 'nama', 'jenis_kelamin', 'agama', 'rombel_id', 'foto', 'tempat_lahir']);
        if (Storage::disk('local')->exists('livewire-tmp')) {
            Storage::disk('local')->deleteDirectory('livewire-tmp');
        }
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
        $finalId = $this->finalSekolahId; // Panggil computed property
        $sekolahTerpilih = $this->sekolahTerpilih; // Panggil computed property

        $rombelQuery = Rombel::query();
        if ($finalId) {
            $rombelQuery->where('sekolah_id', $finalId);
            $rombelQuery->with('kelas');

            if ($sekolahTerpilih && $this->rombel_id) {
                $rombelQuery->where('id', $this->rombel_id);
            }
            $rombelQuery->orderBy('nama_rombel', 'asc');
        } else {
            $rombelQuery->whereRaw('1 = 0');
        }

        return [
            'all_sekolah' => $this->isSuperAdmin ? Sekolah::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']) : null,
            'all_rombel' => $rombelQuery->orderBy('nama_rombel')->get(['nama_rombel', 'id']),
            'sekolahTerpilih' => $sekolahTerpilih,
        ];
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="siswaModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" aria-labelledby="siswaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="siswaModalLabel">{{ $isEdit ? 'Edit Data Siswa' : 'Tambah Siswa Baru' }}
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
                                <label class="form-label">NISN (10 Digit)</label>
                                <input type="text" wire:model="nisn" nama="nisn" class="form-control"
                                    placeholder="Contoh: 0043211234">
                                @error('nisn')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap Siswa</label>
                                <input type="text" wire:model="nama" name="nama" class="form-control"
                                    placeholder="Masukkan nama sesuai ijazah">
                                @error('nama')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <select wire:model="jenis_kelamin" name="jenis_kelamin" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                @error('jenis_kelamin')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Agama</label>
                                <select wire:model="agama" name="agama" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    <option value="Islam">Islam</option>
                                    <option value="Kristen">Kristen</option>
                                    <option value="Katolik">Katolik</option>
                                    <option value="Budha">Budha</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Kong Hu Cu">Kong Hu Cu</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                                @error('agama')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="mb-3">
                                <label class="form-label">Penempatan Kelas/Rombel</label>
                                <select wire:model="rombel_id" name="rombel_id" class="form-select">
                                    <option value="">-- Pilih Kelas/Rombel --</option>
                                    @foreach ($all_rombel as $rombel)
                                        <option value="{{ $rombel->id }}">
                                            {{ $rombel->nama_rombel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('rombel_id')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Aktif</label>
                                <input type="email" wire:model="email" class="form-control" name="email"
                                    autocomplete="on" placeholder="contoh@namasekolah.sch.id">
                                @error('email')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No Telp / WhatsApp</label>
                                <input type="text" wire:model="no_telp" name="no_telp" class="form-control"
                                    placeholder="08xxxxxxxxxx">
                                @error('no_telp')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        @if ($isEdit)
                            <div class="bg-light mb-3 rounded border p-2">
                                <label class="form-label fw-bold text-secondary">Ganti Password Akun (Opsional)</label>
                                <input type="password" wire:model="password" name="password" class="form-control"
                                    placeholder="Isi hanya jika ingin merubah password login siswa">
                                @error('password')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Tempat lahir</label>
                            <textarea type="text" wire:model="tempat_lahir" name="tempat_lahir" class="form-control"
                                placeholder="Tempat lahir" rows="5"></textarea>
                            @error('tempat_lahir')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3"
                                wire:key="preview-container-{{ is_object($foto) ? $foto->temporaryUrl() : $foto }}">
                                @if ($foto && method_exists($foto, 'temporaryUrl'))
                                    <img id="previewImg" src="{{ $foto->temporaryUrl() }}" height="300"
                                        width="300" class="img-thumbnail">
                                @else
                                    <img id="previewImg" height="300" width="300" class="img-thumbnail"
                                        src="{{ $isEdit && $foto ? asset('gambar/images/siswa/' . $foto) : asset('gambar/images/siswa/default.png') }}">
                                @endif
                            </div>
                            <div class="col-md-9">
                                <div class="custom-file mb-2">
                                    <input type="file" class="form-control form-control-alternative"
                                        name="foto" wire:model="foto" id="image" placeholder="Input file">
                                </div>
                                @error('foto')
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
