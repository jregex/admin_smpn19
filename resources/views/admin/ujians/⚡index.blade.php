<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Ujian;
use Illuminate\Support\Facades\Gate;

new #[Layout('layouts::app_admin', ['title' => 'Manajemen Ujian'])] class extends Component {
    public $ujianJson;

    public function mount()
    {
        $this->getFreshData();
    }
    #[On('refreshDatatable')]
    public function refreshTable()
    {
        // Emit data terbaru ke Javascript Tabulator
        $this->dispatch('update-ujian-data', data: $this->getFreshData());
    }
    public function getFreshData()
    {
        // Pastikan User memiliki hak akses (Atur via UjianPolicy / Spatie)
        if (function_exists('Gate::authorize')) {
            Gate::authorize('view-ujian');
        }

        $userLogin = auth()->user();
        $query = Ujian::query()->with(['kelas', 'guru']);

        // Filter data berdasarkan hak akses/role jika bukan superadmin
        if (!$userLogin->hasRole('superadmin')) {
            $query->where('sekolah_id', $userLogin->sekolah_id);
            if ($userLogin->hasRole('guru')) {
                $query->where('guru_id', $userLogin->guru->id)->orWhere('pengawas_id', $userLogin->guru->id);
            }
        }

        $data = $query
            ->latest()
            ->get()
            ->map(function ($ujian) {
                return [
                    'id' => $ujian->id,
                    'nama_ujian' => $ujian->nama_ujian,
                    'kelas' => $ujian->kelas->nama_kelas ?? '-',
                    'nama_guru' => $ujian->guru->nama ?? '-',
                    'waktu_mulai' => $ujian->waktu_mulai ? $ujian->waktu_mulai->format('d/m/Y H:i') : '-',
                    'waktu_selesai' => $ujian->waktu_selesai ? $ujian->waktu_selesai->format('d/m/Y H:i') : '-',
                    'durasi' => $ujian->durasi . ' Menit',
                    'kkpp' => $ujian->kkpp . ' Poin',
                    'total_soal' => $ujian->soals()->count() . ' Butir',
                    'status' => $ujian->status,
                ];
            })
            ->toArray();

        $this->ujianJson = json_encode($data);
        return $data;
    }
    public function render()
    {
        $columns = [['label' => 'Nama Ujian', 'field' => 'nama_ujian'], ['label' => 'Kelas', 'field' => 'kelas'], ['label' => 'Nama Guru', 'field' => 'nama_guru'], ['label' => 'Waktu Mulai', 'field' => 'waktu_mulai', 'sortable' => false], ['label' => 'Waktu selesai', 'field' => 'waktu_selesai', 'sortable' => false], ['label' => 'Durasi', 'field' => 'durasi'], ['label' => 'Total Soal', 'field' => 'total_soal', 'searchable' => false, 'sortable' => false]];
        return $this->view(compact('columns'));
    }
};
?>

<div class="row">
    @if (session()->has('error'))
        <div class="alert alert-error alert-dismissible fade show" role="alert">
            {{ session()->get('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session()->get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(session()->has('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session()->get('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <a href="{{ route('ujian.create') }}" class="btn btn-success fw-semibold">
                    <i class="bi bi-plus-lg"></i> Buat Ujian baru
                </a>
            </div>
            <div class="card-body">
                <x-datatable :jsonData="$ujianJson" :columns="$columns" eventName="refreshDatatable" modalId="modalDetailUjian"
                    updateEvent="update-ujian-data" />
            </div>
        </div>
    </div>
    <livewire:admin.ujians.detail-ujian />
</div>
