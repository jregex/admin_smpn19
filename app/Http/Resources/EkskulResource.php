<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EkskulResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_ekskul' => $this->nama_ekskul,
            'pembina' => $this->pembina->nama ?? 'Belum di tentukan',
            'nilai' => $this->nilai,
            'waktu' => date('H:i', strtotime($this->jam_mulai)).' - '.date('H:i', strtotime($this->jam_selesai)),
            'hari' => $this->hari?->value ?? '-',
        ];
    }
}
