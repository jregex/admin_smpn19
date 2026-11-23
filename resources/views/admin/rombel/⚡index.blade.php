<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Rombel;

new #[Layout('layouts::app_admin', ['title' => 'Rombel'])] class extends Component {
    public $rombelJson;
    public function mount()
    {
        $this->getFreshData();
    }
    public function getFreshData()
    {
        $query = Rombel::with(['kelas', 'sekolah']);
        $data = $query
            ->latest()
            ->get()
            ->map(function ($rombel) {
                return [
                    'id' => $rombel->id,
                    'nama_rombel' => $rombel->nama_rombel,
                    'nama_kelas' => $rombel->kelas->nama_kelas ?? 'Belum Ditentukan',
                    'walikelas' => $rombel->guru->nama ?? '-',
                    'nama_sekolah' => $rombel->sekolah->nama_sekolah ?? 'Belum Ditentukan',
                ];
            })
            ->toArray();

        $this->rombelJson = json_encode($data);
        return $data;
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-rombel-data', data: $this->getFreshData());
    }
    public function render()
    {
        // Definisikan kolom apa saja yang ingin ditampilkan ke user

        $columns = [['label' => 'Rombel', 'field' => 'nama_rombel'], ['label' => 'Kelas', 'field' => 'nama_kelas'], ['label' => 'Wali Kelas', 'field' => 'walikelas']];
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
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#rombelModal"><i
                            class="bi bi-plus-lg"></i> Tambah
                        data</button>
                @endif
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$rombelJson" :columns="$columns" eventName="refreshDatatable" modalId="rombelModal"
                    :hasAction="auth()
                        ->user()
                        ->hasAnyRole(['superadmin', 'admin_sekolah'])
                        ? true
                        : false" updateEvent="update-rombel-data" />
            </div>
            @if (auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah']))
                <livewire:admin.rombel.rombel-form />
            @endif
        </div>
    </div>
</div>
