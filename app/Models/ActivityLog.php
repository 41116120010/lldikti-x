<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const CATEGORY_LOGIN = 'login';
    public const CATEGORY_AGENDA = 'agenda';
    public const CATEGORY_PRESENSI = 'presensi';
    public const CATEGORY_USER = 'user';
    public const CATEGORY_OTHER = 'lainnya';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'target_model',
        'target_id',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve macro category from raw activity type.
     */
    public static function resolveCategory(string $activityType): string
    {
        $type = strtoupper(trim($activityType));

        if (str_starts_with($type, 'AUTH_') || str_contains($type, 'LOGIN') || str_contains($type, 'LOGOUT')) {
            return self::CATEGORY_LOGIN;
        }

        if (str_contains($type, 'ATTENDANCE') || str_contains($type, 'CHECK_IN') || str_contains($type, 'PRESENSI')) {
            return self::CATEGORY_PRESENSI;
        }

        if (
            str_contains($type, 'AGENDA')
            || str_contains($type, 'EXPORT_')
            || str_contains($type, 'REPORT')
            || str_contains($type, 'NOTULEN')
            || str_contains($type, 'DOCUMENTATION')
        ) {
            return self::CATEGORY_AGENDA;
        }

        if (
            str_contains($type, 'USER')
            || str_contains($type, 'UNIT')
            || str_contains($type, 'PROFILE')
            || str_contains($type, 'PASSWORD')
        ) {
            return self::CATEGORY_USER;
        }

        return self::CATEGORY_OTHER;
    }

    /**
     * Scope query to filter activity logs by macro category.
     *
     * @param Builder $query
     * @param string|null $category
     * @return Builder
     */
    public function scopeForCategory(Builder $query, ?string $category): Builder
    {
        if (! $category || $category === 'all' || $category === 'semua') {
            return $query;
        }

        $cat = strtolower(trim($category));

        return match ($cat) {
            self::CATEGORY_LOGIN => $query->where(function ($q) {
                $q->whereLike('activity_type', 'AUTH_%')
                    ->orWhereLike('activity_type', '%LOGIN%')
                    ->orWhereLike('activity_type', '%LOGOUT%');
            }),
            self::CATEGORY_PRESENSI => $query->where(function ($q) {
                $q->whereLike('activity_type', '%ATTENDANCE%')
                    ->orWhereLike('activity_type', '%CHECK_IN%')
                    ->orWhereLike('activity_type', '%PRESENSI%');
            }),
            self::CATEGORY_AGENDA => $query->where(function ($q) {
                $q->whereLike('activity_type', '%AGENDA%')
                    ->orWhereLike('activity_type', '%EXPORT_%')
                    ->orWhereLike('activity_type', '%REPORT%')
                    ->orWhereLike('activity_type', '%NOTULEN%')
                    ->orWhereLike('activity_type', '%DOCUMENTATION%');
            }),
            self::CATEGORY_USER => $query->where(function ($q) {
                $q->whereLike('activity_type', '%USER%')
                    ->orWhereLike('activity_type', '%UNIT%')
                    ->orWhereLike('activity_type', '%PROFILE%')
                    ->orWhereLike('activity_type', '%PASSWORD%');
            }),
            default => $query,
        };
    }
}
