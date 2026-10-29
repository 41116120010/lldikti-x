<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

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
        $hasNotulensi = ! empty(trim(strip_tags((string) $this->notulensi)));
        $isEligible = $currentUser ? $this->isUserEligible($currentUser) : false;
        $hasAttended = $currentUser ? $this->hasUserAttended($currentUser) : false;

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
            'allowed_transitions' => $this->allowed_transitions,
            'can_start_now' => (bool) $this->can_start_now,
            'is_overdue' => (bool) $this->is_overdue,
            'earliest_start_at' => $this->earliest_start_at?->toIso8601String(),
            'effective_end_at' => $this->effective_end_at?->toIso8601String(),
            'surat_edaran' => [
                'url' => $this->surat_edaran_path ? url('/storage/' . ltrim($this->surat_edaran_path, '/')) : null,
                'stream_url' => $this->surat_edaran_path ? url("/api/v1/agendas/{$this->id}/surat-edaran") : null,
                'extension' => $this->surat_edaran_extension,
                'is_pdf' => (bool) $this->is_surat_edaran_pdf,
                'is_image' => (bool) $this->is_surat_edaran_image,
            ],
            'creator' => new UserResource($this->whenLoaded('creator')),
            'pimpinan' => new UserResource($this->whenLoaded('pimpinan')),
            'notulis' => new UserResource($this->whenLoaded('notulis')),
            'nama_pimpinan' => $this->nama_pimpinan,
            'nama_notulis' => $this->nama_notulis,
            'units' => UnitResource::collection($this->whenLoaded('units')),
            'attendances_count' => $this->when(isset($this->attendances_count), (int) ($this->attendances_count ?? 0)),
            'is_eligible' => $isEligible,
            'has_attended' => $hasAttended,
            'my_attendance' => $this->when($currentUser !== null, function () use ($currentUser) {
                if ($this->relationLoaded('attendances')) {
                    $att = $this->attendances->firstWhere('user_id', $currentUser->id);
                    return $att ? new AttendanceResource($att) : null;
                }
                $att = $this->attendances()->where('user_id', $currentUser->id)->first();
                return $att ? new AttendanceResource($att) : null;
            }),
            'has_notulensi' => $hasNotulensi,
            'notulensi_html' => $this->formatted_notulensi,
            'kesimpulan_html' => $this->formatted_kesimpulan,
            'notulensi_raw' => $this->notulensi,
            'kesimpulan_raw' => $this->kesimpulan,
            'documentations' => DocumentationResource::collection($this->whenLoaded('documentations')),
            'attendances' => AttendanceResource::collection($this->whenLoaded('attendances')),
            'nomor_ba' => $this->nomor_ba,
            'total_invited' => $this->total_invited,
            'total_attended' => $this->total_attended,
            'unattended_count' => $this->unattended_count,
            'participation_rate' => $this->participation_rate,
            'participation_rate_label' => $this->participation_rate_label,
            'report_config' => $this->when(
                $this->relationLoaded('documentations') || $request->routeIs('api.v1.agendas.show', 'api.v1.agendas.notulen*', 'api.v1.reports.agenda'),
                fn () => $this->resolved_report_config
            ),
            'permissions' => $currentUser ? [
                'can_update' => Gate::forUser($currentUser)->allows('update', $this->resource),
                'can_delete' => Gate::forUser($currentUser)->allows('delete', $this->resource),
                'can_manage_status' => Gate::forUser($currentUser)->allows('manageStatus', $this->resource),
                'can_manage_minutes' => Gate::forUser($currentUser)->allows('manageMinutes', $this->resource),
                'can_attend' => $this->status === 'ongoing' && $isEligible && ! $hasAttended,
                'can_export' => Gate::forUser($currentUser)->allows('view', $this->resource),
            ] : null,
            'export_links' => [
                'pdf' => url("/api/v1/reports/agendas/{$this->id}/export/pdf"),
                'word' => url("/api/v1/reports/agendas/{$this->id}/export/word"),
                'csv' => url("/api/v1/reports/agendas/{$this->id}/export/csv"),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

