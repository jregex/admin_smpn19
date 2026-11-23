<?php

namespace App\Exports;

use App\Models\Ujian;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiSiswaExport implements FromCollection, WithColumnWidths, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    /**
     * @param  array<int, array{nisn: string, nama: string, nilai: int|null, benar: int|null, salah: int|null, status: string}>  $daftarNilai
     */
    public function __construct(
        public Ujian $ujian,
        public array $daftarNilai,
    ) {}

    public function collection(): Collection
    {
        $rows = [
            ['DAFTAR NILAI SISWA'],
            ['Ujian: '.$this->ujian->nama_ujian],
            ['Kelas: '.($this->ujian->kelas->nama_kelas ?? '-').' | Mata Pelajaran: '.($this->ujian->mapel->nama_mapel ?? '-')],
            ['Tanggal Export: '.now()->format('d/m/Y H:i')],
            ['No', 'NISN', 'Nama Siswa', 'Nilai', 'Benar', 'Salah', 'Status'],
        ];

        foreach ($this->daftarNilai as $index => $nilai) {
            $status = match ($nilai['status']) {
                'selesai' => 'Selesai',
                'mengerjakan' => 'Mengerjakan',
                default => 'Tidak mengikuti',
            };

            $rows[] = [
                $index + 1,
                $nilai['nisn'],
                $nilai['nama'],
                $nilai['nilai'] ?? '-',
                $nilai['benar'] ?? '-',
                $nilai['salah'] ?? '-',
                $status,
            ];
        }

        return collect($rows);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 18,
            'C' => 32,
            'D' => 10,
            'E' => 8,
            'F' => 8,
            'G' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            5 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (['A1:G1', 'A2:G2', 'A3:G3', 'A4:G4'] as $range) {
                    $sheet->mergeCells($range);
                }

                $sheet->getStyle('A5:G5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($sheet->getHighestRow() >= 6) {
                    $sheet->getStyle('D6:G'.$sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->freezePane('A6');
            },
        ];
    }

    public function title(): string
    {
        return 'Nilai Siswa';
    }
}
