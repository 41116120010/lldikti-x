<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'unit_id',
        'name',
        'nip',
        'username',
        'email',
        'password',
        'role',
        'phone',
        'avatar_path',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    /**
     * Display label for the role, so templates never interpolate the raw value.
     */
    public function getRoleLabelAttribute(): string
    {
        return $this->role?->label() ?? '-';
    }

    /**
     * Display official civil servant title / designation (Jabatan Kedinasan).
     */
    public function getJabatanAttribute(): string
    {
        if ($this->isAdministrator()) {
            return 'Administrator Sistem Instansi';
        }

        if ($this->isAdmin()) {
            $unitName = $this->unit?->nama_unit ?? 'Unit Kerja';

            return 'Admin ' . $unitName;
        }

        $unitName = $this->unit?->nama_unit ?? 'LLDIKTI Wilayah X';

        return 'Pegawai ASN • ' . $unitName;
    }

    /**
     * Display official civil servant rank / grade (Pangkat & Golongan Ruang).
     */
    public function getPangkatGolonganAttribute(): ?string
    {
        if ($this->isAdministrator()) {
            return 'Pembina Utama Muda (Gol. IV/c)';
        }

        if ($this->isAdmin()) {
            return 'Penata Tingkat I (Gol. III/d)';
        }

        return 'Penata Muda (Gol. III/a)';
    }

    /**
     * Relationship to the user's unit.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Relationship to agendas created by this user.
     *
     * @return HasMany<Agenda, $this>
     */
    public function createdAgendas(): HasMany
    {
        return $this->hasMany(Agenda::class, 'created_by');
    }

    /**
     * Relationship to attendances recorded by this user.
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relationship to activity logs triggered by this user.
     *
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Helper methods for roles.
     *
     * Three names for two concepts used to live here (isStaff and isPegawai were
     * the same method), and callers picked whichever they remembered. One name
     * per role now.
     */
    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    /**
     * Scope for active users
     *
     * @param  Builder<User>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users in a specific unit
     *
     * @param  Builder<User>  $query
     */
    public function scopeForUnit(Builder $query, ?int $unitId): Builder
    {
        if ($unitId === null) {
            return $query;
        }

        return $query->where('unit_id', $unitId);
    }

    /**
     * Send password reset notification using SIPERAPAT Gov-Tech standard template.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
