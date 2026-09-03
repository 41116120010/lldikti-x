<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Agenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'judul_rapat',
        'slug',
        'jenis_rapat',
        'tipe_rapat',
        'lokasi_ruang',
        'link_meeting',
        'waktu_mulai',
        'waktu_selesai',
        'is_all_units',
        'surat_edaran_path',
        'notulensi',
        'kesimpulan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'is_all_units' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($agenda) {
            if (empty($agenda->slug)) {
                $baseSlug = Str::slug($agenda->judul_rapat);
                $uniqueSlug = $baseSlug . '-' . Str::lower(Str::random(6));
                $agenda->slug = $uniqueSlug;
            }
        });
    }

    /**
     * Relationship to the user who created the agenda.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to units invited to this agenda.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'agenda_units')
            ->withTimestamps();
    }

    /**
     * Relationship to attendances recorded for this agenda.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relationship to photo documentations of this agenda.
     */
    public function documentations(): HasMany
    {
        return $this->hasMany(AgendaDocumentation::class)->orderBy('sort_order');
    }

    /**
     * Scope for ongoing agendas.
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', 'ongoing');
    }

    /**
     * Scope for scheduled / upcoming agendas.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for completed agendas.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for agendas visible to a specific user based on unit & permissions.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdministrator()) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($user) {
            $sub->where('is_all_units', true);

            if ($user->unit_id) {
                $sub->orWhereHas('units', function (Builder $uQuery) use ($user) {
                    $uQuery->where('units.id', $user->unit_id);
                });
            }
        });
    }

    /**
     * Check if a specific user is eligible to attend this agenda.
     */
    public function isUserEligible(User $user): bool
    {
        // Administrator has global oversight and can attend any agenda across all units
        if ($user->isAdministrator()) {
            return true;
        }

        if ($this->is_all_units) {
            return true;
        }

        if (!$user->unit_id) {
            return false;
        }

        return $this->units()->where('units.id', $user->unit_id)->exists();
    }

    /**
     * Check if a user has already checked in.
     */
    public function hasUserAttended(User $user): bool
    {
        return $this->attendances()->where('user_id', $user->id)->exists();
    }
}
