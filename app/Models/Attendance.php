<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_id',
        'user_id',
        'signed_at',
        'selfie_path',
        'signature_path',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Agenda, \App\Models\Attendance>
     */
    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, \App\Models\Attendance>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Unit kerja milik pegawai yang melakukan presensi ini.
     *
     * Used by AttendancePolicy on every authorisation check. Reading $attendance->user
     * there would lazy-load the relation on each call; this uses the loaded relation
     * when present and otherwise asks the database once, so the policy never
     * triggers an N+1 or a lazy-loading violation.
     */
    public function attendeeUnitId(): ?int
    {
        if ($this->relationLoaded('user')) {
            return $this->user?->unit_id;
        }

        $unitId = $this->user()->value('unit_id');

        return $unitId === null ? null : (int) $unitId;
    }

    /**
     * The agenda this attendance belongs to, resolved without a lazy load.
     *
     * Used by AttendancePolicy on every authorisation check. Reaching for
     * $attendance->agenda there would lazy-load the relation, which adds a query
     * per check and trips the strict lazy-loading guard. This uses the loaded
     * relation when present and otherwise fetches the row once.
     */
    public function fetchAgenda(): ?Agenda
    {
        if ($this->relationLoaded('agenda')) {
            return $this->agenda;
        }

        return $this->agenda()->first();
    }

    /**
     * Get the full URL for selfie image.
     */
    public function getSelfieUrlAttribute(): ?string
    {
        return $this->selfie_path ? Storage::disk('public')->url($this->selfie_path) : null;
    }

    /**
     * Get the full URL for signature image.
     */
    public function getSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null;
    }

    /**
     * Get protected route URL for selfie image streaming.
     */
    public function getProtectedSelfieUrlAttribute(): ?string
    {
        return $this->selfie_path ? route('attendances.selfie', $this) : null;
    }

    /**
     * Get protected route URL for signature image streaming.
     */
    public function getProtectedSignatureUrlAttribute(): ?string
    {
        return $this->signature_path ? route('attendances.signature', $this) : null;
    }
}
