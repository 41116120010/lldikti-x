<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgendaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();

        return [
            'id' => $this->id,
            'judul_rapat' => $this->judul_rapat,
            'slug' => $this->slug,
            'jenis_rapat' => $this->jenis_rapat,
            'tipe_rapat' => $this->tipe_rapat,
            'lokasi_ruang' => $this->lokasi_ruang,
            'link_meeting' => $this->link_meeting,
            'waktu_mulai' => $this->waktu_mulai?->toIso8601String(),
            'waktu_selesai' => $this->waktu_selesai?->toIso8601String(),
            'rentang_waktu' => $this->rentang_waktu,
            'jadwal_lengkap' => $this->jadwal_lengkap,
            'is_all_units' => (bool) $this->is_all_units,
            'status' => [
                'value' => $this->status,
                'label' => $this->status_meta['label'] ?? $this->status,
            ],
            'surat_edaran' => [
                'url' => $this->surat_edaran_path ? url('/storage/' . ltrim($this->surat_edaran_path, '/')) : null,
                'extension' => $this->surat_edaran_extension,
                'is_pdf' => (bool) $this->is_surat_edaran_pdf,
                'is_image' => (bool) $this->is_surat_edaran_image,
            ],
            'creator' => new UserResource($this->whenLoaded('creator')),
            'pimpinan' => new UserResource($this->whenLoaded('pimpinan')),
            'notulis' => new UserResource($this->whenLoaded('notulis')),
            'units' => UnitResource::collection($this->whenLoaded('units')),
            'attendances_count' => $this->when(isset($this->attendances_count), (int) ($this->attendances_count ?? 0)),
            'is_eligible' => $currentUser ? $this->isUserEligible($currentUser) : false,
            'has_attended' => $currentUser ? $this->hasUserAttended($currentUser) : false,
            'my_attendance' => $this->when($currentUser !== null, function () use ($currentUser) {
                if ($this->relationLoaded('attendances')) {
                    $att = $this->attendances->firstWhere('user_id', $currentUser->id);
                    return $att ? new AttendanceResource($att) : null;
                }
                $att = $this->attendances()->where('user_id', $currentUser->id)->first();
                return $att ? new AttendanceResource($att) : null;
            }),
            'notulensi_html' => $this->formatted_notulensi,
            'kesimpulan_html' => $this->formatted_kesimpulan,
            'documentations' => DocumentationResource::collection($this->whenLoaded('documentations')),
            'attendances' => AttendanceResource::collection($this->whenLoaded('attendances')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
