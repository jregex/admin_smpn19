<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Kelas;

new #[Layout('layouts::app_admin', ['title' => 'Kelas'])] class extends Component {
    public $kelasJson;
    public function mount()
    {
        $this->getFreshData();
    }
    public function getFreshData()
    {
        $query = Kelas::with(['sekolah', 'jurusan']);

        if (!auth()->user()->hasRole('superadmin')) {
            $query->where('sekolah_id', auth()->user()->sekolah_id);
        }
        $data = $query
            ->latest()
            ->get()
            ->map(function ($kelas) {
                return [
                    'id' => $kelas->id,
                    'nama_kelas' => $kelas->nama_kelas,
                    'nama_jurusan' => $kelas->jurusan->nama_jurusan,
                    'nama_sekolah' => $kelas->sekolah->nama_sekolah ?? 'Belum Ditentukan',
                ];
            })
            ->toArray();

        $this->kelasJson = json_encode($data);
        return $data;
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-kelas-data', data: $this->getFreshData());
    }
    public function render()
    {
        $columns = [['label' => 'Nama Kelas', 'field' => 'nama_kelas']];
        if (auth()->user()->hasRole('superadmin')) {
            $columns[] = ['label' => 'Sekolah', 'field' => 'nama_sekolah'];
        }

        return $this->view(compact('columns'));
    }
};
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-none">
            <div class="card-header">
                @if (!auth()->user()->hasAnyRole(['guru', 'siswa']))
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#kelasModal"><i
                            class="bi bi-plus-lg"></i> Tambah
                        data</button>
                @endif
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$kelasJson" :columns="$columns" eventName="refreshDatatable" modalId="kelasModal"
                    :hasAction="auth()
                        ->user()
                        ->hasAnyRole(['superadmin', 'admin_sekolah'])
                        ? true
                        : false" updateEvent="update-kelas-data" />
            </div>
            @if (!auth()->user()->hasAnyRole(['guru', 'siswa']))
                <livewire:admin.kelas.kelas-form />
            @endif
        </div>
    </div>
</div>
