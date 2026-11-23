<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Mapel;

new #[Layout('layouts::app_admin', ['title' => 'Mata Pelajaran'])] class extends Component {
    public $mapelJson;
    public function mount()
    {
        $this->getFreshData();
    }
    public function getFreshData()
    {
        $userLogin = auth()->user();
        $query = Mapel::with(['sekolah']);

        if (!$userLogin->hasRole('superadmin')) {
            $query->where('sekolah_id', $userLogin->sekolah_id);
        }

        $data = $query
            ->latest()
            ->get()
            ->map(function ($mapel) {
                return [
                    'id' => $mapel->id,
                    'kode_mapel' => $mapel->kode_mapel,
                    'nama_mapel' => $mapel->nama_mapel,
                    'nama_sekolah' => $mapel->sekolah->nama_sekolah ?? '-',
                ];
            })
            ->toArray();

        $this->mapelJson = json_encode($data);
        return $data;
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-mapel-data', data: $this->getFreshData());
    }
    public function render()
    {
        // Definisikan kolom apa saja yang ingin ditampilkan ke user
        $columns = [['label' => 'Kode', 'field' => 'kode_mapel'], ['label' => 'Mata pelajaran', 'field' => 'nama_mapel'], ['label' => 'Sekolah', 'field' => 'nama_sekolah']];

        return $this->view(compact('columns'));
    }
};
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-none">
            <div class="card-header">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#mapelModal"><i
                        class="bi bi-plus-lg"></i> Tambah
                    data</button>
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$mapelJson" :columns="$columns" eventName="refreshDatatable" modalId="mapelModal"
                    updateEvent="update-mapel-data" />
            </div>
            <livewire:admin.mapel.mapel-form />
        </div>
    </div>
</div>
