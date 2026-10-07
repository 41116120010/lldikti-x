<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'agenda_id' => $this->agenda_id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'signed_at' => $this->signed_at?->toIso8601String(),
            'selfie_url' => $this->selfie_path ? url("/api/v1/attendances/{$this->id}/selfie") : null,
            'signature_url' => $this->signature_path ? url("/api/v1/attendances/{$this->id}/signature") : null,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
