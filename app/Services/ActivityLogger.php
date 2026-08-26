<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Record an activity log entry.
     *
     * @param string $type Activity type (e.g. AUTH_LOGIN, CREATE_AGENDA)
     * @param string $description Human-readable description
     * @param string|null $targetModel Target model class name (e.g. Agenda::class)
     * @param int|null $targetId Target model ID
     * @param array|null $properties Old/New payload or context properties
     * @param User|null $user Custom user override, or current authenticated user
     * @return ActivityLog
     */
    public static function log(
        string $type,
        string $description,
        ?string $targetModel = null,
        ?int $targetId = null,
        ?array $properties = null,
        ?User $user = null
    ): ActivityLog {
        $actor = $user ?? Auth::user();

        return ActivityLog::create([
            'user_id' => $actor?->id,
            'activity_type' => strtoupper($type),
            'description' => $description,
            'target_model' => $targetModel,
            'target_id' => $targetId,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
