<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Jurusan;

new #[Layout('layouts::app_admin', ['title' => 'Jurusan'])] class extends Component {
    public $jurJson;
    public function mount()
    {
        $this->getFreshData();
    }
    public function getFreshData()
    {
        $userLogin = auth()->user();
        $query = Jurusan::with(['sekolah']);

        if (!$userLogin->hasRole('superadmin')) {
            $query->where('sekolah_id', $userLogin->sekolah_id);
        }
        $data = $query
            ->latest()
            ->get()
            ->map(function ($jurusan) {
                return [
                    'id' => $jurusan->id,
                    'kode_jurusan' => $jurusan->kode_jurusan,
                    'nama_jurusan' => $jurusan->nama_jurusan,
                    'nama_sekolah' => $jurusan->sekolah->nama_sekolah ?? 'Belum Ditentukan',
                ];
            })
            ->toArray();

        $this->jurJson = json_encode($data);
        return $data;
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-jurusan-data', data: $this->getFreshData());
    }
    public function render()
    {
        // Definisikan kolom apa saja yang ingin ditampilkan ke user
        $columns = [['label' => 'Kode Jurusan', 'field' => 'kode_jurusan'], ['label' => 'Nama Jurusan', 'field' => 'nama_jurusan'], ['label' => 'Sekolah', 'field' => 'nama_sekolah']];

        return $this->view(compact('columns'));
    }
};
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-none">
            <div class="card-header">
                @if (!auth()->user()->hasAnyRole(['guru', 'siswa']))
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#jurusanModal"><i
                            class="bi bi-plus-lg"></i> Tambah
                        data</button>
                @endif
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$jurJson" :columns="$columns" eventName="refreshDatatable" :hasAction="auth()
                    ->user()
                    ->hasAnyRole(['superadmin', 'admin_sekolah'])
                    ? true
                    : false"
                    modalId="jurusanModal" updateEvent="update-jurusan-data" />
            </div>
            @if (!auth()->user()->hasAnyRole(['guru', 'siswa']))
                <livewire:admin.jurusan.jurusan-form />
            @endif
        </div>
    </div>
</div>
