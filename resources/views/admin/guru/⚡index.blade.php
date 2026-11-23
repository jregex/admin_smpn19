<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Guru;

new #[Layout('layouts::app_admin', ['title' => 'Guru'])] class extends Component {
    public $guruJson;

    public function mount()
    {
        $this->getFreshData();
    }

    public function getFreshData()
    {
        $data = Guru::with('sekolah')
            ->where('sekolah_id', auth()->user()->sekolah_id)
            ->get()
            ->map(function ($guru) {
                return [
                    'id' => $guru->id,
                    'nip' => $guru->nip,
                    'nama' => $guru->nama,
                    'jenis_kelamin' => $guru->jenis_kelamin == 'L' ? 'Laki - laki' : 'Perempuan',
                    'no_telp' => $guru->no_telp,
                    'nama_sekolah' => $guru->sekolah->nama_sekolah ?? '-',
                ];
            })
            ->toArray();

        $this->guruJson = json_encode($data);
        return $data;
    }

    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-guru-data', data: $this->getFreshData());
    }

    public function render()
    {
        // Definisikan kolom apa saja yang ingin ditampilkan ke user
        $columns = [['label' => 'NIP', 'field' => 'nip'], ['label' => 'Nama Guru', 'field' => 'nama'], ['label' => 'Jenis Kelamin', 'field' => 'jenis_kelamin'], ['label' => 'No Hp', 'field' => 'no_telp']];
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
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#guruModal"><i
                        class="bi bi-plus-lg"></i> Tambah
                    data</button>
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$guruJson" :columns="$columns" eventName="refreshDatatable" modalId="guruModal"
                    :hasAction="auth()
                        ->user()
                        ->hasAnyRole(['superadmin', 'admin_sekolah'])
                        ? true
                        : false" updateEvent="update-guru-data" />
            </div>
            <livewire:admin.guru.guru-form />
        </div>
    </div>
</div>
