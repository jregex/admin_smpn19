<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use App\Models\Guru;
use App\Models\Role;
use App\Models\User;
use App\Models\Sekolah;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

new class extends Component {
    public $guru_id;
    public $isEdit = false;
    public $sekolah_id = '';
    public $nip = '';
    public $email = '';
    public $password = '';
    public $nama = '';
    public $jenis_kelamin = '';
    public $no_telp = '';

    public function rules()
    {
        return [
            'sekolah_id' => auth()->user()->hasRole('superadmin') ? 'required|exists:sekolahs,id' : 'nullable',
            'nama' => 'required|string',
            'nip' => $this->guru_id ? 'required|max:20|unique:gurus,nip,' . $this->guru_id : 'required|max:20|unique:gurus,nip',
            'email' => $this->guru_id ? 'required|email|unique:users,email,' . Guru::find($this->guru_id)->user_id : 'required|email',
            'password' => $this->isEdit ? 'nullable|string|min:8' : 'required|string|min:8',
            'jenis_kelamin' => 'required|in:L,P',
            'no_telp' => 'nullable|string|max:15',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Instansi Sekolah',
        'email' => 'Email Login',
        'password' => 'Password',
        'nama' => 'Nama Lengkap Guru',
        'jenis_kelamin' => 'Jenis Kelamin',
        'no_telp' => 'Nomor HP',
        'nip' => 'NIP',
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
        $guru = Guru::findOrFail($id);
        $this->guru_id = $guru->id;
        $this->nip = $guru->nip;
        $this->nama = $guru->nama;
        $this->email = $guru->user->email;
        $this->password = '';
        $this->no_telp = $guru->no_telp;
        $this->jenis_kelamin = $guru->jenis_kelamin;
        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-guruModal');
    }
    public function store()
    {
        $this->validate();
        $userLogin = auth()->user();
        $finalSekolahId = $userLogin->hasRole('superadmin') ? $this->sekolah_id : $userLogin->sekolah_id;

        $roleGuru = Role::where('role', 'guru')->first();
        if ($this->guru_id) {
            $guru = Guru::findOrFail($this->guru_id);
            $user = User::findOrFail($guru->user_id);
            $user->update([
                'name' => $this->nama,
                'email' => $this->email,
                'password' => $this->password ? bcrypt($this->password) : $user->password,
            ]);

            // Update Profil Guru
            $guru->update([
                'nama' => $this->nama,
                'jenis_kelamin' => $this->jenis_kelamin,
                'no_telp' => $this->no_telp,
            ]);
        } else {
            $userExisting = User::where('email', $this->email)->where('sekolah_id', $finalSekolahId)->first();
            if ($userExisting) {
                // Jika akun user sudah dibuat duluan, ambil ID-nya dan perbarui namanya jika berbeda
                $userId = $userExisting->id;
                $userExisting->update([
                    'name' => $this->nama,
                    // Opsional: Perbarui password juga jika diinput saat nge-link profil
                    'password' => $this->password ? bcrypt($this->password) : $userExisting->password,
                ]);
            } else {
                // Jika benar-benar akun baru, buat entri User baru
                $userBaru = User::create([
                    'sekolah_id' => $finalSekolahId,
                    'role_id' => $roleGuru->id,
                    'name' => $this->nama,
                    'email' => $this->email,
                    'password' => bcrypt($this->password),
                    'is_active' => 1,
                ]);
                $userId = $userBaru->id;
            }
            Guru::create([
                'sekolah_id' => $finalSekolahId,
                'user_id' => $userId,
                'nip' => $this->nip,
                'nama' => $this->nama,
                'jenis_kelamin' => $this->jenis_kelamin,
                'no_telp' => $this->no_telp,
            ]);
        }
        LivewireAlert::title('Berhasil!')
            ->text($this->guru_id ? 'Data Guru diperbarui' : 'Guru berhasil ditambahkan')
            ->success()
            ->asToast()
            ->show();
        $this->dispatch('close-modal-guruModal');
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
            $guru = Guru::findOrFail($data['id']);
            $user = User::find($guru->user_id);
            $guru->delete();
            if ($user) {
                $user->delete();
            }
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
        $this->reset(['guru_id', 'sekolah_id', 'email', 'password', 'nip', 'nama', 'jenis_kelamin', 'no_telp']);
        $this->isEdit = false;
        $this->clearValidation();
    }
    public function with()
    {
        return [
            'all_sekolah' => auth()->user()->hasRole('superadmin') ? Sekolah::orderBy('nama_sekolah', 'asc')->get(['nama_sekolah', 'id']) : null,
        ];
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="guruModal" tabindex="-1" aria-labelledby="guruModalLabel"
        data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="guruModalLabel">{{ $isEdit ? 'Edit Data Guru' : 'Tambah Guru Baru' }}
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
                            <label class="form-label">NIP</label>
                            <input type="text" wire:model="nip" class="form-control" autocomplete="on"
                                {{ $isEdit ? 'disabled' : '' }}>
                            @error('nip')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Guru</label>
                            <input type="text" wire:model="nama" class="form-control" autocomplete="on">
                            @error('nama')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select wire:model="jenis_kelamin" class="form-control">
                                <option value="">-- Pilih --</option>
                                <option value="L" {{ $jenis_kelamin == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ $jenis_kelamin == 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('jenis_kelamin')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3" wire:ignore x-data x-init="const mask = IMask($refs.phoneInput, {
                            mask: '+{62}(000) 0000-0000'
                        });
                        
                        mask.on('accept', () => {
                            $wire.no_telp = mask.unmaskedValue;
                        });
                        $wire.$watch('no_telp', (value) => {
                            if (value !== mask.unmaskedValue) {
                                mask.unmaskedValue = value || '';
                            }
                        });">
                            <label class="form-label">No Telp</label>
                            <input type="text" wire:model="no_telp" x-ref="phoneInput" id="no_telp"
                                class="form-control" placeholder="62 (822) 7144-5622">
                            @error('no_telp')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Login Guru *</label>
                                <input type="email" wire:model="email" class="form-control"
                                    placeholder="contoh: guru@sekolah.sch.id" autocomplete="on"
                                    {{ $isEdit ? 'disabled' : '' }}>
                                @error('email')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password Login
                                    {{ $isEdit ? '(Isi jika ingin diganti)' : '*' }}</label>
                                <input type="password" wire:model="password" class="form-control"
                                    placeholder="Minimal 8 karakter">
                                @error('password')
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
