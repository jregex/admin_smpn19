<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('layouts::app_admin', ['title' => 'Pembuatan Ujian'])] class extends Component {
    public $currentStep = 'ujian';
    public $ujianId = null;
    public function mount($id = null)
    {
        if ($id) {
            $this->ujianId = $id;
            $this->currentStep = 'ujian';
        }
    }

    #[On('ujianDisimpan')]
    public function lanjutKeSoal($ujianId)
    {
        $this->ujianId = $ujianId;
        $this->currentStep = 'soal';
    }

    #[On('kembaliKeUjian')]
    public function kembali()
    {
        $this->currentStep = 'ujian';
        // $this->ujianId = null;
    }
};
?>

<div>
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="position-relative d-flex justify-content-center align-items-center mx-auto"
                style="max-width: 600px;">

                <div class="position-absolute top-50 end-0 start-0" style="height: 4px; z-index: 1;">
                    <div class="{{ $currentStep == 'ujian' ? 'bg-primary' : 'bg-success' }} h-100"
                        style="width: {{ $currentStep === 'soal' ? '100%' : '50%' }}; transition: width 0.4s ease, background-color 0.4s ease;">
                    </div>
                </div>

                <div class="position-relative flex-grow-1 text-center" style="z-index: 2;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center border-3 {{ $currentStep === 'ujian' ? 'bg-primary border-primary text-white' : 'bg-success border-success text-white' }} mx-auto mb-3 border"
                        style="width: 45px; height: 45px; transition: all 0.3s ease;">
                        @if ($currentStep === 'soal')
                            <i class="bi bi-check-lg fs-5"></i>
                        @else
                            <span class="fw-bold">1</span>
                        @endif
                    </div>
                    <div class="small fw-bold {{ $currentStep === 'ujian' ? 'text-primary' : 'text-success' }} mt-2">
                        Parameter Ujian</div>
                    <div class="text-muted" style="font-size: 11px;">Pengaturan dasar jadwal & KKM</div>
                </div>

                <div class="position-relative flex-grow-1 text-center" style="z-index: 2;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center border-3 {{ $currentStep === 'soal' ? 'bg-primary border-primary text-white' : 'bg-white border-secondary text-muted' }} mx-auto mb-3 border"
                        style="width: 45px; height: 45px; transition: all 0.3s ease;">
                        <span class="fw-bold">2</span>
                    </div>
                    <div class="small fw-bold {{ $currentStep === 'soal' ? 'text-primary' : 'text-muted' }} mt-2">Bank
                        Soal & Kunci</div>
                    <div class="text-muted" style="font-size: 11px;">Input pertanyaan & opsi jawaban</div>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if ($currentStep === 'ujian')
                <livewire:admin.ujians.step-ujian-form :ujianId="$ujianId" />
            @elseif ($currentStep === 'soal')
                <livewire:admin.ujians.step-soal-form :ujianId="$ujianId" />
            @endif
        </div>
    </div>
</div>

@push('css')
    <style>
        .transition-all {
            transition: width 0.4s ease-in-out;
        }
    </style>
@endpush
