<?php

use Livewire\Component;
use App\Enum\Hari;
use App\Models\Jadwal;
use App\Models\User;
use App\Models\Mapel;
use App\Models\Sekolah;
use App\Models\Rombel;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\On;

new class extends Component {
    public $jadwalId;
    public $user_id,
        $mapel_id,
        $hari,
        $jam_mulai,
        $jam_selesai,
        $isEdit = false,
        $rombel_id,
        $sekolah_id = '',
        $tahun_ajaran_id = '';

    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'mapel_id' => 'required|exists:mapels,id',
            'rombel_id' => 'required|exists:rombels,id',
            'hari' => ['required', new Enum(Hari::class)],
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
        ];
    }

    protected $validationAttributes = [
        'user_id' => 'Guru Pengampu',
        'mapel_id' => 'Mata Pelajaran',
        'rombel_id' => 'Rombel',
        'hari' => 'Hari',
        'jam_mulai' => 'Jam Mulai',
        'tahun_ajaran_id' => 'Tahun Ajaran',
        'jam_selesai' => 'Jam Selesai',
    ];
    #[On('editRow')]
    public function edit($id)
    {
        $jadwal = Jadwal::findOrFail($id);
        $this->jadwalId = $jadwal->id;
        $this->user_id = $jadwal->user_id;
        $this->mapel_id = $jadwal->mapel_id;
        $this->rombel_id = $jadwal->rombel_id;
        $this->hari = $jadwal->hari;
        $this->jam_mulai = date('H:i', strtotime($jadwal->jam_mulai));
        $this->jam_selesai = date('H:i', strtotime($jadwal->jam_selesai));
        $this->tahun_ajaran_id = $jadwal->tahun_ajaran_id;
        $this->clearValidation();

        $this->isEdit = true;
        $this->clearValidation();
        $this->dispatch('open-modal-jadwalModal');
    }
    public function rendering()
    {
        // Jika sedang TIDAK dalam mode edit (alias sedang tambah data baru),
        // pastikan error validasi lama dibersihkan secara otomatis.
        if (!$this->isEdit) {
            $this->clearValidation();
        }
    }
    public function store()
    {
        try {
            // Proses Cek Hak Akses
            if ($this->jadwalId) {
                $this->authorize('update', Jadwal::find($this->jadwalId));
            } else {
                $this->authorize('create', Jadwal::class);
            }
            $validData = $this->validate();
            DB::beginTransaction();
            $validData['sekolah_id'] = auth()->user()->sekolah_id;
            $validData['tahun_ajaran_id'] = session()->get('tahun_ajaran_id');
            Jadwal::updateOrCreate(['id' => $this->jadwalId], $validData);
            DB::commit();
            LivewireAlert::title('Berhasil!')
                ->text($this->mapel_id ? 'Jadwal diperbarui' : 'Jadwal berhasil ditambahkan')
                ->success()
                ->asToast()
                ->show();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal Menyimpan!')
                ->text('Detail Error: ' . $th->getMessage())
                ->error()
                ->show();
            return;
        } catch (AuthorizationException $e) {
            LivewireAlert::title('Akses Ditolak!')->text('Anda tidak memiliki izin untuk memanipulasi data jadwal.')->error()->show();
            return;
        }

        $this->dispatch('close-modal-jadwalModal');
        $this->dispatch('refreshDatatable');
        $this->cancel();
    }
    #[On('triggerDeleteConfirm')]
    public function triggerDeleteConfirm($id)
    {
        LivewireAlert::title('Hapus Data')
            ->text('Apakah Anda yakin ingin menghapus data ini?')
            ->asConfirm()
            ->onConfirm('delete', ['id' => $id])
            ->show();
    }

    public function delete(array $data)
    {
        DB::beginTransaction();
        try {
            $this->authorize('update', Jadwal::find($this->jadwalId));
            Jadwal::findOrFail($data['id'])->delete();
            DB::commit();
            LivewireAlert::title('Berhasil!')->text('Data berhasil dihapus')->success()->asToast()->show();
            $this->refreshTable();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text('Data gagal dihapus')->error()->asToast()->show();
        } catch (AuthorizationException $e) {
            DB::rollBack();
            LivewireAlert::title('Akses Ditolak!')->text('Anda tidak memiliki izin untuk memanipulasi data jadwal.')->error()->show();
            return;
        }
    }
    #[On('triggerCancel')]
    public function cancel()
    {
        $this->reset(['jadwalId', 'user_id', 'rombel_id', 'mapel_id', 'hari', 'jam_mulai', 'jam_selesai', 'tahun_ajaran_id']);
        $this->isEdit = false;
        $this->clearValidation();
    }
    public function with()
    {
        $user = auth()->user();
        $this->authorize('viewAny', Jadwal::class);

        return [
            'list_guru' => User::whereHas('role', function ($query) {
                $query->where('role', 'guru');
            })
                ->where('sekolah_id', $user->sekolah_id)
                ->get(),
            'list_rombel' => Rombel::where('sekolah_id', $user->sekolah_id)->get(),
            'list_mapel' => Mapel::where('sekolah_id', $user->sekolah_id)->get(),
        ];
    }
};
?>

<div>
    <div wire:ignore.self class="modal fade" id="jadwalModal" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false" aria-labelledby="jadwalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="jadwalModalLabel">
                        {{ $isEdit ? 'Edit Data Jadwal' : 'Tambah Jadwal Baru' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" wire:click="cancel"
                        aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Hari</label>
                            <select wire:model="hari" class="form-select">
                                <option value="">-- Pilih Hari --</option>
                                @foreach (\App\Enum\Hari::values() as $h)
                                    <option value="{{ $h }}">{{ $h }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rombel</label>
                            <select wire:model="rombel_id" class="form-select">
                                <option value="">-- Pilih Rombel --</option>
                                @foreach ($list_rombel as $kls)
                                    <option value="{{ $kls->id }}">{{ $kls->nama_rombel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mata Pelajaran</label>
                            <select wire:model="mapel_id" class="form-select">
                                <option value="">-- Pilih Mapel --</option>
                                @foreach ($list_mapel as $mpl)
                                    <option value="{{ $mpl->id }}">{{ $mpl->nama_mapel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guru</label>
                            <select wire:model="user_id" class="form-select">
                                <option value="">-- Pilih Guru --</option>
                                @foreach ($list_guru as $gr)
                                    <option value="{{ $gr->id }}">{{ $gr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3" wire:ignore x-data x-init="flatpickr($refs.jamMulai, {
                                enableTime: true,
                                noCalendar: true,
                                dateFormat: 'H:i',
                                time_24hr: true,
                                // Mengirim nilai unmasked/bersih langsung saat waktu dipilih
                                onChange: function(selectedDates, dateStr) {
                                    $wire.jam_mulai = dateStr;
                                }
                            });">
                                <label class="form-label">Jam Mulai</label>
                                <input type="text" wire:model="jam_mulai" x-ref="jamMulai" class="form-control"
                                    id="jam_mulai">
                            </div>
                            <div class="col-6 mb-3" wire:ignore x-data x-init="flatpickr($refs.jamSelesai, {
                                enableTime: true,
                                noCalendar: true,
                                dateFormat: 'H:i',
                                time_24hr: true,
                                // Mengirim nilai unmasked/bersih langsung saat waktu dipilih
                                onChange: function(selectedDates, dateStr) {
                                    $wire.jam_selesai = dateStr;
                                }
                            });">
                                <label class="form-label">Jam Selesai</label>
                                <input type="text" wire:model="jam_selesai" class="form-control" x-ref="jamSelesai"
                                    id="jam_selesai">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cancel" class="btn btn-secondary"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="coba" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
