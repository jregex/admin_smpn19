<?php

use Livewire\Component;
use App\Models\Info;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('layouts::app_admin', ['title' => 'Info & pengumuman'])] class extends Component {
    public $infoJson;

    public function mount()
    {
        $this->getFreshData();
    }
    public function getFreshData()
    {
        $user = auth()->user();
        $query = Info::with('pembuat');

        // Multi-tenant filter
        if (!$user->hasRole('superadmin')) {
            $query->where('sekolah_id', $user->sekolah_id);
        }

        $data = $query
            ->latest()
            ->orderBy('is_pin', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'judul' => $p->judul,
                    'target' => strtoupper($p->target_audiens),
                    'pinned' => $p->is_pin ? '📌 Ya' : 'Tidak',
                    'dibuat' => $p->created_at->format('d M Y H:i'),
                    'pembuat' => $p->pembuat->name,
                ];
            })
            ->toArray();

        $this->infoJson = json_encode($data);
        return $data;
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        $this->dispatch('update-info-data', data: $this->getFreshData());
    }

    public function render()
    {
        $columns = [['label' => 'Judul', 'field' => 'judul'], ['label' => 'Target', 'field' => 'target'], ['label' => 'Tanggal', 'field' => 'dibuat'], ['label' => 'Oleh', 'field' => 'pembuat']];
        return $this->view(compact('columns'));
    }
};
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-none">
            <div class="card-header">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#infoModal"><i
                        class="bi bi-plus-lg"></i> Tambah
                    data</button>
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$infoJson" :columns="$columns" eventName="refreshDatatable" modalId="infoModal"
                    :hasAction="auth()
                        ->user()
                        ->hasAnyRole(['superadmin', 'admin_sekolah'])
                        ? true
                        : false" updateEvent="update-info-data" />
            </div>
            <livewire:admin.info.info-form />
        </div>
    </div>
</div>
