<?php

use Livewire\Component;
use App\Models\TahunAjaran;
use App\Models\Sekolah;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Carbon\Carbon;

new class extends Component {
    public $ta_id;
    public $tahun;
    public $tanggal_mulai;
    public $tanggal_selesai;
    public $isEdit = false;
    public $sekolah_id;
    public function rules()
    {
        return [
            'tahun' => 'required|string|size:9|regex:/^\d{4}\/\d{4}$/',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'sekolah_id' => auth()->user()->hasRole('superadmin') ? 'required|exists:sekolahs,id' : 'nullable',
        ];
    }
    protected $validationAttributes = [
        'sekolah_id' => 'Instansi Sekolah',
        'tanggal_mulai' => 'Tanggal Mulai',
        'tanggal_selesai' => 'Tanggal Selesai',
        'tahun' => 'Tahun ajaran',
    ];
    public function rendering()
    {
        if (!$this->isEdit) {
            $this->clearValidation();
        }
    }
    #[On('editRow')]
    public function edit($id)
    {
        $tahun = TahunAjaran::findOrFail($id);
        $this->tanggal_mulai = $tahun->tanggal_mulai;
        $this->tanggal_selesai = $tahun->tanggal_selesai;
        $this->ta_id = $tahun->id;
        $this->tahun = $tahun->tahun;
        $this->sekolah_id = $tahun->sekolah_id;
        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-tahunModal');
    }

    public function store()
    {
        $this->validate();
        $userLogin = auth()->user();
        $finalSekolahId = $userLogin->hasRole('superadmin') ? $this->sekolah_id : $userLogin->sekolah_id;

        // 1. Deteksi Otomatis Apakah Tahun yang Diinput Merupakan Tahun Aktif Sekarang
        $sekarang = Carbon::now();
        $stringTAAktifSeharusnya = $sekarang->month >= 7 ? $sekarang->year . '/' . ($sekarang->year + 1) : $sekarang->year - 1 . '/' . $sekarang->year;
        $isAktifOtomatis = $this->tahun === $stringTAAktifSeharusnya;
        DB::beginTransaction();
        try {
            // 2. Jika tahun baru ini aktif, nonaktifkan tahun ajaran lainnya terlebih dahulu
            if ($isAktifOtomatis) {
                TahunAjaran::query()->update(['is_aktif' => false]);
            }

            TahunAjaran::updateOrCreate(
                ['id' => $this->ta_id],
                [
                    'tahun' => $this->tahun,
                    'sekolah_id' => $finalSekolahId,
                    'tanggal_mulai' => $this->tanggal_mulai,
                    'tanggal_selesai' => $this->tanggal_selesai,
                    'is_aktif' => $isAktifOtomatis,
                ],
            );

            DB::commit();

            // 3. HAPUS CACHE REDIS GLOBAL agar middleware mendeteksi perubahan instan
            Cache::forget('global_ta_aktif');

            LivewireAlert::title('Berhasil!')->text('Data berhasil disimpan')->success()->asToast()->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text('Data gagal disipmpan')->error()->asToast()->show();
            return;
        }
        $this->dispatch('close-modal-tahunModal');
        $this->dispatch('refreshDatatable');
        $this->cancel();
    }
    #[On('triggerCancel')]
    public function cancel()
    {
        $this->reset(['ta_id', 'sekolah_id', 'tahun', 'tanggal_selesai', 'tanggal_mulai']);
        $this->isEdit = false;
        $this->clearValidation();
    }
    #[On('triggerDeleteConfirm')]
    public function triggerDeleteConfirm($id)
    {
        LivewireAlert::title('Hapus data')
            ->text('Apakah kamu yakin ingin menghapus?')
            ->asConfirm()
            ->onConfirm('delete', ['id' => $id])
            ->show();
    }
    public function delete(array $data)
    {
        DB::beginTransaction();
        try {
            $tahun = TahunAjaran::findOrFail($data['id']);
            $tahun->delete();
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Data berhasil dihapus')->success()->asToast()->show();
            $this->dispatch('refreshDatatable');
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text('Data gagal dihapus')->error()->asToast()->show();
            return;
        }
    }
    public function with()
    {
        $user = auth()->user();
        $finalSekolahId = $user->hasRole('superadmin') ? $this->sekolah_id : $user->sekolah_id;
        $sekolahQuery = Sekolah::query();
        if ($user->hasRole('superadmin')) {
            // Superadmin bisa melihat semua sekolah, diurutkan berdasarkan nama
            $sekolahQuery->orderBy('nama_sekolah', 'asc');
        } else {
            // Admin biasa hanya bisa melihat sekolahnya sendiri
            $sekolahQuery->where('id', $finalSekolahId);
        }
        return [
            'all_sekolah' => $sekolahQuery->get(),
        ];
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="tahunModal" tabindex="-1" aria-labelledby="tahunModalLabel"
        data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="tahunModalLabel">
                        {{ $isEdit ? 'Edit Data Tahun ajaran' : 'Tambah Tahun ajaran Baru' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        @if (auth()->user()->hasRole('superadmin'))
                            <div class="mb-3">
                                <label class="form-label">Sekolah</label>
                                <select wire:model="sekolah_id" class="form-control">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($all_sekolah as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_sekolah }}</option>
                                    @endforeach
                                </select>
                                @error('sekolah_id')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Tahun Ajaran</label>
                            <input type="text" wire:model="tahun" class="form-control text-uppercase"
                                autocomplete="on">
                            @error('tahun')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3" wire:ignore x-data="{
                                picker: null
                            }" x-init="picker = flatpickr($refs.tanggalMulai, {
                                enableTime: false,
                                dateFormat: 'Y-m-d',
                                onChange: function(selectedDates, dateStr) {
                                    $wire.tanggal_mulai = dateStr;
                                }
                            });
                            $watch('$wire.tanggal_mulai', value => picker.setDate(value, false));">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="text" wire:model="tanggal_mulai" x-ref="tanggalMulai"
                                    class="form-control" id="tanggal_mulai">
                            </div>
                            <div class="col-6 mb-3" wire:ignore x-data="{
                                picker: null
                            }" x-init="picker = flatpickr($refs.tanggalSelesai, {
                                enableTime: false,
                                dateFormat: 'Y-m-d',
                                onChange: function(selectedDates, dateStr) {
                                    $wire.tanggal_selesai = dateStr;
                                }
                            });
                            // Jalankan ini setiap kali variabel tanggal_selesai di Livewire berubah (saat edit)
                            $watch('$wire.tanggal_selesai', value => picker.setDate(value, false));">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="text" wire:model="tanggal_selesai" x-ref="tanggalSelesai"
                                    class="form-control" id="tanggal_selesai">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit"
                            class="btn btn-success">{{ $isEdit ? 'Perbarui Data' : 'Simpan Data' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
