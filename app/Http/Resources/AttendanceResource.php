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
            'verification_token' => $this->verification_token,
            'user' => new UserResource($this->whenLoaded('user')),
            'agenda' => $this->whenLoaded('agenda', function () {
                return [
                    'id' => $this->agenda->id,
                    'judul_rapat' => $this->agenda->judul_rapat,
                    'slug' => $this->agenda->slug,
                    'jenis_rapat' => $this->agenda->jenis_rapat,
                    'tipe_rapat' => $this->agenda->tipe_rapat,
                    'format_kedinasan' => $this->agenda->format_kedinasan,
                    'lokasi_ruang' => $this->agenda->lokasi_ruang,
                    'link_meeting' => $this->agenda->link_meeting,
                    'waktu_mulai' => $this->agenda->waktu_mulai?->toIso8601String(),
                    'waktu_selesai' => $this->agenda->waktu_selesai?->toIso8601String(),
                    'rentang_waktu' => $this->agenda->rentang_waktu,
                    'jadwal_lengkap' => $this->agenda->jadwal_lengkap,
                    'status' => [
                        'value' => $this->agenda->status,
                        'label' => $this->agenda->status_meta['label'] ?? $this->agenda->status,
                    ],
                    'nomor_ba' => $this->agenda->nomor_ba,
                ];
            }),
            'receipt' => [
                'nomor_bukti' => $this->verification_token,
                'verification_token' => $this->verification_token,
                'institutional_header' => [
                    'kementerian' => 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI',
                    'instansi' => 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X',
                    'alamat' => 'Jl. Khatib Sulaiman Padang, Sumatera Barat',
                ],
                'meeting' => $this->relationLoaded('agenda') ? [
                    'judul_rapat' => $this->agenda->judul_rapat,
                    'nomor_ba' => $this->agenda->nomor_ba,
                    'format_kedinasan' => $this->agenda->format_kedinasan,
                    'tipe_rapat' => $this->agenda->tipe_rapat,
                    'lokasi_ruang' => $this->agenda->lokasi_ruang,
                    'link_meeting' => $this->agenda->link_meeting,
                    'rentang_waktu' => $this->agenda->rentang_waktu,
                    'jadwal_lengkap' => $this->agenda->jadwal_lengkap,
                ] : null,
                'participant' => $this->relationLoaded('user') ? [
                    'name' => $this->user->name,
                    'nip' => $this->user->nip,
                    'jabatan' => $this->user->role_label,
                    'unit_kerja' => $this->user->unit?->nama_unit ?? 'Tingkat Lembaga',
                ] : null,
                'validation' => [
                    'status' => 'tercatat',
                    'status_label' => 'Hadir Sah Terverifikasi',
                    'metode_verifikasi' => 'Swafoto Wajah & Tanda Tangan Digital',
                    'ip_address' => $this->ip_address,
                    'user_agent' => $this->user_agent,
                    'signed_at' => $this->signed_at?->toIso8601String(),
                ],
            ],
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
