<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
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
            'nama_unit' => $this->nama_unit,
            'kode_unit' => $this->kode_unit,
            'deskripsi' => $this->deskripsi,
            'is_active' => (bool) $this->is_active,
            'users_count' => $this->whenCounted('users'),
            'agendas_count' => $this->whenCounted('agendas'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
