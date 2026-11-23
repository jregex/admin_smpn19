<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::app_admin', ['title' => 'Profil akun'])] class extends Component {
    public $name,
        $email,
        $thisEdit = false,
        $sekolah_id;
    public function mount()
    {
        $this->getProfile();
    }
    #[On('edit-profile')]
    private function getProfile()
    {
        $user = auth()->user();
        $this->npsn = $user->npsn;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->sekolah_id = $user->sekolah_id;
    }
    #[On('update-profile')]
    public function store()
    {
        $user = auth()->user();
        DB::beginTransaction();
        try {
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Data berhasil diperbarui')->success()->asToast()->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text('Data gagal diperbarui')->error()->asToast()->show();
            return;
        }
    }
};
?>

<div class="row g-3">
    <!-- Profile sidebar -->
    <div class="col-md-3">
        <!-- About card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-3"
                    style="width: 96px; height: 96px; font-size: 2rem" aria-hidden="true">
                    {{ auth()->user()->initials() }}
                </div>
                <h3 class="h5 mb-0">{{ auth()->user()->name }}</h3>
                <p class="text-secondary mb-3">{{ auth()->user()->role->role }}</p>
                <ul class="list-group list-group-flush small text-start">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-secondary">Sekolah</span>
                        <span
                            class="fw-semibold">{{ !auth()->user()->hasRole('superadmin') ? auth()->user()->sekolah->nama_sekolah : 'None' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-secondary">Dibuat pada</span>
                        <span class="fw-semibold">{{ date('d F Y', strtotime(auth()->user()->created_at)) }}</span>
                    </li>

                </ul>
                <button class="btn btn-success w-100 mt-3" id="btn-edit">
                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                    Edit
                </button>
            </div>
        </div>
    </div>

    <!-- Tabbed content -->
    <div class="col-md-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="card-title">Profil akun</h5>
            </div>
            <div class="card-body">
                <form class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nama"> Nama </label>
                        <input type="text" class="form-control" id="nama" wire:model="name" disabled />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email"> Email </label>
                        <input type="email" class="form-control" id="email" wire:model="email" disabled />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="profile-role"> Role </label>
                        <input type="text" class="form-control" id="profile-role"
                            value="{{ auth()->user()->role->role }}" disabled />
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="profile-bio">Bio</label>
                        <textarea class="form-control" id="profile-bio" rows="4"></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-success" id="btnPerbarui" disabled>Perbarui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('livewire:init', () => {
            const edit = document.querySelector('#btn-edit');
            const update = document.querySelector('#btnPerbarui');
            edit.addEventListener('click', function() {
                document.getElementById('nama').disabled = false;
                document.getElementById('email').disabled = false;
                document.getElementById('btnPerbarui').removeAttribute('disabled');
                this.disabled = true;
            });
            update.addEventListener('click', function() {
                Livewire.dispatch('update-profile');
                document.getElementById('nama').disabled = true;
                document.getElementById('email').disabled = true;
                this.disabled = true;
            });
            // window.addEventListener('updated', function() {
            //     edit.disabled = false;
            //     document.getElementById('nama').disabled = true;
            //     document.getElementById('email').disabled = true;
            //     update.disabled = true;
            // });
        });
    </script>
@endpush
