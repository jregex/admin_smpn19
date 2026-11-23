<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Siswa;

new #[Layout('layouts::app_admin', ['title' => 'Siswa'])] class extends Component {
    public $siswaJson;
    public function mount()
    {
        $this->getFreshData();
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-siswa-data', data: $this->getFreshData());
    }
    public function getFreshData()
    {
        $userLogin = auth()->user();
        $query = Siswa::query();

        if (!$userLogin->hasRole('superadmin')) {
            $query->where('sekolah_id', $userLogin->sekolah_id);
        }
        $data = $query
            ->latest()
            ->get()
            ->map(function ($siswa) {
                return [
                    'id' => $siswa->id,
                    'nisn' => $siswa->nisn,
                    'nama' => $siswa->nama,
                    'jenis_kelamin' => $siswa->jenis_kelamin == 'L' ? 'Laki - laki' : 'Perempuan',
                    'agama' => $siswa->agama,
                    'rombel' => $siswa->rombel->nama_rombel,
                    'nama_sekolah' => $siswa->sekolah->nama_sekolah ?? '-',
                    'foto' => $siswa->foto ?? 'default.png',
                ];
            })
            ->toArray();

        $this->siswaJson = json_encode($data);
        return $data;
    }
    public function render()
    {
        $columns = [['label' => 'NISN', 'field' => 'nisn'], ['label' => 'Nama Siswa', 'field' => 'nama'], ['label' => 'Jenis Kelamin', 'field' => 'jenis_kelamin'], ['label' => 'Agama', 'field' => 'agama'], ['label' => 'Rombel', 'field' => 'rombel'], ['label' => 'Foto', 'field' => 'foto', 'searchable' => false, 'sortable' => false]];
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
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#siswaModal"><i
                        class="bi bi-plus-lg"></i> Tambah
                    data</button>
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$siswaJson" :columns="$columns" eventName="refreshDatatable" modalId="siswaModal"
                    updateEvent="update-siswa-data" />
            </div>
            <livewire:admin.siswa.siswa-form />
        </div>
    </div>
</div>
