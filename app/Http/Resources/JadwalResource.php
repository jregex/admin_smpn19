<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalResource extends JsonResource
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
            // Format jam dari HH:MM:SS menjadi HH:MM
            'jam' => date('H:i', strtotime($this->jam_mulai)).' - '.date('H:i', strtotime($this->jam_selesai)),
            'mapel' => $this->whenLoaded('mapel', function () {
                return $this->mapel->nama ?? $this->mapel->nama_mapel ?? '-';
            }, '-'),
            'guru' => $this->whenLoaded('guru', function () {
                return $this->guru->name ?? $this->guru->nama ?? '-';
            }, '-'),
            'rombel' => $this->whenLoaded('rombel', function () {
                return $this->rombel->nama ?? $this->rombel->nama_rombel ?? '-';
            }, '-'),
        ];
    }
}
