<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Sekolah;

new #[Layout('layouts::app_admin', ['title' => 'Sekolah'])] class extends Component {
    public $sekolahJson;
    public function mount()
    {
        $this->getFreshData();
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-sekolah-data', data: $this->getFreshData());
    }
    public function getFreshData()
    {
        $data = Sekolah::latest()
            ->get()
            ->map(function ($sekolah) {
                return [
                    'id' => $sekolah->id,
                    'npsn' => $sekolah->npsn,
                    'nama_sekolah' => $sekolah->nama_sekolah,
                    'jenjang' => $sekolah->jenjang,
                    'alamat' => $sekolah->alamat,
                    'email' => $sekolah->email,
                    'logo' => $sekolah->logo,
                ];
            })
            ->toArray();

        $this->sekolahJson = json_encode($data);
        return $data;
    }
    public function render()
    {
        $columns = [['label' => 'NPSN', 'field' => 'npsn'], ['label' => 'Nama Sekolah', 'field' => 'nama_sekolah'], ['label' => 'Jenjang', 'field' => 'jenjang'], ['label' => 'Email', 'field' => 'email'], ['label' => 'Alamat', 'field' => 'alamat'], ['label' => 'Logo', 'field' => 'logo']];
        return $this->view(compact('columns'));
    }
};
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-none">
            <div class="card-header">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sekolahModal"><i
                        class="bi bi-plus-lg"></i> Tambah
                    data</button>
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$sekolahJson" :columns="$columns" eventName="refreshDatatable" modalId="sekolahModal"
                    updateEvent="update-sekolah-data" />
            </div>
            <livewire:admin.sekolah.sekolah-form />
        </div>
    </div>
</div>
