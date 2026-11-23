<?php

use Livewire\Component;
use App\Models\{Ujian, Soal};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\WithFileUploads;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Format;
use Illuminate\Support\Facades\{Storage, DB, File};
use Illuminate\Support\Str;

new class extends Component {
    use WithFileUploads;
    public $ujianId;
    public $soalId = null;
    public $isEdit = false;
    public $teks_soal = '';
    public $kunci_jawaban = '';
    public $pilihan = ['A' => '', 'B' => '', 'C' => '', 'D' => ''];
    public $gambar;
    public function mount($ujianId)
    {
        $this->ujianId = $ujianId;
    }
    public function rules()
    {
        return [
            'teks_soal' => 'required|string',
            'kunci_jawaban' => 'required|in:A,B,C,D,E',
            'pilihan.A' => 'required|string',
            'pilihan.B' => 'required|string',
            'pilihan.C' => 'required|string',
            'pilihan.D' => 'required|string',
            'gambar' => $this->gambar && is_object($this->gambar) ? 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048' : 'nullable',
        ];
    }
    public function pilihEditSoal($idSoal)
    {
        $this->soalId = $idSoal;
        $this->isEdit = true;

        $soal = Soal::with('pilihanJawabans')->findOrFail($idSoal);
        $this->teks_soal = $soal->teks_soal;
        $this->kunci_jawaban = $soal->kunci_jawaban;
        $this->gambar = $soal->gambar;

        foreach ($soal->pilihanJawabans as $opsi) {
            if (in_array($opsi->opsi, ['A', 'B', 'C', 'D'])) {
                $this->pilihan[$opsi->opsi] = $opsi->teks_pilihan;
            }
        }
    }
    public function batalkanEditSoal()
    {
        $this->reset(['teks_soal', 'gambar', 'kunci_jawaban', 'pilihan', 'soalId', 'isEdit']);
        $this->clearValidation();
        if (Storage::disk('local')->exists('livewire-tmp')) {
            Storage::disk('local')->deleteDirectory('livewire-tmp');
        }
    }
    public function tambahSoal()
    {
        $this->validate();
        DB::beginTransaction();
        try {
            if ($this->soalId) {
                $soal = Soal::findOrFail($this->soalId);
                if ($this->gambar && is_object($this->gambar)) {
                    if (!Storage::exists('/images/soal')) {
                        Storage::makeDirectory('/images/soal', 0o775, true);
                    }
                    $filename = Str::slug($this->kunci_jawaban) . '_' . time() . '.jpg';
                    $img = Image::decode($this->gambar)->scale(300);
                    $img->encodeUsingFormat(Format::JPEG, progressive: true, quality: 95)->save(Storage::path('images/soal/' . $filename));
                    if (Storage::disk('local')->exists('images/soal/' . $soal->gambar)) {
                        Storage::disk('local')->delete('images/soal/' . $soal->gambar);
                    }
                } else {
                    $filename = $soal->gambar;
                }
                $soal->update([
                    'ujian_id' => $this->ujianId,
                    'teks_soal' => $this->teks_soal,
                    'kunci_jawaban' => $this->kunci_jawaban,
                    'gambar' => $filename,
                ]);
            } else {
                if ($this->gambar && is_object($this->gambar)) {
                    if (!Storage::exists('/images/soal')) {
                        Storage::makeDirectory('/images/soal', 0o775, true);
                    }
                    $filename = Str::limit($this->kunci_jawaban, 50) . '_' . time() . '.jpg';
                    $img = Image::decode($this->gambar)->scale(300);
                    $img->encodeUsingFormat(Format::JPEG, progressive: true, quality: 95)->save(Storage::path('images/soal/' . $filename));
                } else {
                    $filename = '';
                }
                $soal = Soal::create([
                    'ujian_id' => $this->ujianId,
                    'teks_soal' => $this->teks_soal,
                    'kunci_jawaban' => $this->kunci_jawaban,
                    'gambar' => $filename,
                ]);
            }
            if ($this->isEdit) {
                $soal->pilihanJawabans()->delete();
            }

            // Tulis ulang/simpan pilihan jawaban baru
            //         foreach ($this->pilihan as $opsi => $teks) {
            //             if (!empty($teks)) {
            //                 \App\Models\PilihanJawaban::create([
            //                     'soal_id' => $soal->id,
            //                     'opsi' => strtoupper($opsi),
            //                     'teks_pilihan' => $teks,
            //                      'is_kunci' => strtoupper($this->kunci_jawaban) === strtoupper($opsi) ? 1 : 0,
            //                  ]);
            //               }
            //            }
            $dataPilihan = collect($this->pilihan)
                ->filter(fn($teks) => !empty($teks)) // Buang opsi yang teksnya kosong
                ->map(
                    fn($teks, $opsi) => [
                        'opsi' => strtoupper($opsi),
                        'teks_pilihan' => $teks,
                        'is_kunci' => strtoupper($this->kunci_jawaban) === strtoupper($opsi) ? 1 : 0,
                    ],
                )
                ->toArray();
            $soal->pilihanJawabans()->createMany($dataPilihan);
            DB::commit();
            $pesan = $this->isEdit ? 'Butir soal berhasil diperbarui' : 'Soal berhasil disimpan';
            LivewireAlert::title($pesan)->success()->asToast()->show();
            $this->batalkanEditSoal();
        } catch (\Throwable $th) {
            DB::rollBack();
            LivewireAlert::title('Gagal!')->text($th->getMessage())->error()->timer(6000)->asToast()->show();
            return;
        }
    }
    public function hapusSoal($idSoal)
    {
        Soal::findOrFail($idSoal)->delete();
        if (Storage::disk('local')->exists('livewire-tmp')) {
            Storage::disk('local')->deleteDirectory('livewire-tmp');
        }
        LivewireAlert::title('Soal Dihapus')->success()->asToast()->show();
    }
    public function selesaiWizard()
    {
        LivewireAlert::title('Berhasil')->text('Paket soal ujian dipublikasikan.')->success()->show();
        return redirect()->route('ujian.index');
    }

    public function with()
    {
        return [
            'ujian' => Ujian::with('soals')->findOrFail($this->ujianId),
        ];
    }
};
?>

<div class="row g-4 animate-fade-in">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-pencil-fill text-primary me-2"></i>Langkah 2: Tulis
                    Pertanyaan Baru</h5>

                <form wire:submit.prevent="tambahSoal">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pertanyaan</label>
                        <textarea wire:model="teks_soal" class="form-control card-input" rows="10" placeholder="Ketik kalimat soal..."></textarea>
                        @error('teks_soal')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Pilih Opsi Kunci Jawaban</label>
                        <select wire:model="kunci_jawaban" class="form-select card-input w-50">
                            <option value="">-- Kunci Jawaban Benar --</option>
                            @foreach (['A', 'B', 'C', 'D'] as $char)
                                <option value="{{ $char }}">Pilihan {{ $char }}</option>
                            @endforeach
                        </select>
                        @error('kunci_jawaban')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <h6 class="fw-bold text-secondary border-bottom mb-3 pb-2">Deskripsi Pilihan Jawaban (A-D)</h6>
                    @foreach (['A', 'B', 'C', 'D'] as $char)
                        <div class="input-group shadow-xs mb-2">
                            <span class="input-group-text bg-light fw-bold text-secondary"
                                style="width: 45px;">{{ $char }}</span>
                            <input type="text" wire:model="pilihan.{{ $char }}" class="form-control"
                                placeholder="Teks pilihan {{ $char }}">
                        </div>
                        @error('pilihan.' . $char)
                            <span class="text-danger d-block small mb-2">{{ $message }}</span>
                        @enderror
                    @endforeach
                    <div class="row my-3">
                        <label class="form-label fw-bold">Upload Gambar Soal (Opsional)</label>
                        <div class="col-md-3"
                            wire:key="preview-container-{{ is_object($gambar) ? $gambar->temporaryUrl() : $gambar }}">
                            @if ($gambar && method_exists($gambar, 'temporaryUrl'))
                                <img id="previewImg" src="{{ $gambar->temporaryUrl() }}" height="300" width="300"
                                    class="img-thumbnail">
                            @else
                                <img id="previewImg" height="300" width="300" class="img-thumbnail img-fluid"
                                    src="{{ $isEdit && $gambar ? asset('gambar/images/soal/' . $gambar) : asset('gambar/images/info/placeholder.jpg') }}">
                            @endif
                        </div>
                        <div class="col-md-9">
                            <div class="custom-file mb-2">
                                <input type="file" class="form-control form-control-alternative" name="gambar"
                                    wire:model="gambar" id="image" placeholder="Input file">
                                <span class="small text-secondary fst-italic">Note : Upload gambar jika ingin
                                    memasukkan deskripsi soal dengan gambar</span>
                                <div wire:loading wire:target="gambar" class="text-muted small mt-1">
                                    Mengunggah gambar...
                                </div>
                            </div>
                            @error('gambar')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-2 pt-2">
                        <button type="button" class="btn btn-outline-secondary"
                            wire:click="$dispatch('kembaliKeUjian')">
                            <i class="bi bi-arrow-left"></i> Kembali ke Atur Parameter
                        </button>
                        <div>
                            @if ($isEdit)
                                <button type="button" class="btn btn-light me-1" wire:click="batalkanEditSoal">Batal
                                    Edit</button>
                            @endif
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i>
                                {{ $isEdit ? 'Perbarui & Simpan Soal' : 'Simpan & Lanjut Soal Berikutnya' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card position-sticky border-0 shadow-sm" style="top: 20px;">
            <div class="card-header bg-dark py-3 text-white">
                <h6 class="fw-bold mb-0"><i class="bi bi-bookmark-star me-2"></i>Daftar Soal Terinput</h6>
            </div>
            <div class="card-body p-3">
                <div class="bg-light mb-3 rounded p-3 text-center">
                    <small class="text-muted d-block text-xs uppercase tracking-wider">Total Soal</small>
                    <h3 class="fw-bold text-primary mb-0">{{ $ujian->soals->count() }} Butir</h3>
                </div>

                <div style="max-height: 250px; overflow-y: auto;">
                    <ul class="list-group list-group-flush small">
                        @forelse($ujian->soals as $index => $soal)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-1">
                                <span class="text-truncate me-2" style="max-width: 140px;">
                                    {{ $index + 1 }}. {{ $soal->teks_soal }}
                                </span>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-success-subtle text-success rounded-pill px-2">
                                        Kunci: {{ $soal->kunci_jawaban }}
                                    </span>
                                    <button type="button" class="btn btn-xs btn-outline-primary p-1 py-0"
                                        wire:click="pilihEditSoal({{ $soal->id }})">
                                        <i class="bi bi-pencil-fill" style="font-size: 11px;"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger p-1 py-0"
                                        wire:click="hapusSoal({{ $soal->id }})"
                                        onclick="confirm('Apakah Anda yakin ingin menghapus soal ini?') || event.stopImmediatePropagation()">
                                        <i class="bi bi-trash-fill" style="font-size: 11px;"></i>
                                    </button>
                                </div>
                            </li>
                        @empty
                            <li class="text-muted py-3 text-center">Belum ada butir soal.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="card-footer border-top bg-white p-3">
                <button type="button" class="btn btn-success w-100 fw-bold py-2" wire:click="selesaiWizard"
                    {{ $ujian->soals->count() == 0 ? 'disabled' : '' }}>
                    <i class="bi bi-check-all fs-5 me-1"></i> Selesai & Terbitkan Ujian
                </button>
            </div>
        </div>
    </div>
</div>
