<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfoResource extends JsonResource
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
            'judul' => $this->judul,
            'isi' => $this->isi,
            'is_pin' => $this->is_pin,
            'pembuat' => $this->pembuat->name,
            'gambar' => $this->gambar,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'target_audiens' => $this->target_audiens,
        ];
    }
}
