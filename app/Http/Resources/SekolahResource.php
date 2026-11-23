<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SekolahResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nama_sekolah' => $this->nama_sekolah,
            'npsn' => $this->npsn,
            'jenjang' => $this->jenjang,
            'visi' => $this->visi,
            'alamat' => $this->alamat,
        ];
    }
}
