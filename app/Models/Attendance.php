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

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
