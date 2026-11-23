<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiswaResource extends JsonResource
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
            'nama' => $this->nama,
            'nisn' => $this->nisn,
            'no_telp' => $this->no_telp,
            'agama' => $this->agama,
            'jenis_kelamin' => $this->jenis_kelamin ?? '-',
            'foto' => $this->foto,
            'kelas_id' => $this->rombel ? (string) $this->rombel->kelas_id : null,
        ];
    }
}
