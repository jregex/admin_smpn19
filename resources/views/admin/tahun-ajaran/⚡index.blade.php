<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\TahunAjaran;

new #[Layout('layouts::app_admin', ['title' => 'Tahun ajaran'])] class extends Component {
    public $tahunJson;
    public function mount()
    {
        $this->getFreshData();
    }
    public function getFreshData()
    {
        $query = TahunAjaran::with('sekolah');
        $data = $query
            ->latest()
            ->get()
            ->map(function ($tahun) {
                return [
                    'id' => $tahun->id,
                    'tahun' => $tahun->tahun,
                    'tanggal_mulai' => date('d F Y', strtotime($tahun->tanggal_mulai)),
                    'tanggal_selesai' => date('d F Y', strtotime($tahun->tanggal_selesai)),
                    'nama_sekolah' => $tahun->sekolah->nama_sekolah ?? 'Belum Ditentukan',
                    'is_aktif' => $tahun->is_aktif,
                ];
            })
            ->toArray();

        $this->tahunJson = json_encode($data);
        return $data;
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-tahun-data', data: $this->getFreshData());
    }
    public function render()
    {
        // Definisikan kolom apa saja yang ingin ditampilkan ke user

        $columns = [['label' => 'Tahun', 'field' => 'tahun'], ['label' => 'Tanggal Mulai', 'field' => 'tanggal_mulai'], ['label' => 'Tanggal Selesai', 'field' => 'tanggal_selesai'], ['label' => 'Status', 'field' => 'is_aktif', 'sortable' => false, 'searchable' => false]];
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
                @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah']))
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tahunModal"><i
                            class="bi bi-plus-lg"></i> Tambah
                        data</button>
                @endif
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$tahunJson" :columns="$columns" eventName="refreshDatatable" modalId="tahunModal"
                    :hasAction="auth()
                        ->user()
                        ->hasAnyRole(['superadmin', 'admin_sekolah'])
                        ? true
                        : false" updateEvent="update-tahun-data" />
            </div>
            @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah']))
                <livewire:admin.tahun-ajaran.tahun-ajaran-form />
            @endif
        </div>
    </div>
</div>
