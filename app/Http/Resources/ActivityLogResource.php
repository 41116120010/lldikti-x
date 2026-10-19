<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $categoryKey = ActivityLog::resolveCategory($this->activity_type);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'activity_type' => $this->activity_type,
            'category' => [
                'value' => $categoryKey,
                'label' => match ($categoryKey) {
                    ActivityLog::CATEGORY_LOGIN => 'Login',
                    ActivityLog::CATEGORY_PRESENSI => 'Presensi',
                    ActivityLog::CATEGORY_AGENDA => 'Agenda',
                    ActivityLog::CATEGORY_USER => 'User',
                    default => 'Lainnya',
                },
            ],
            'description' => $this->description,
            'target_model' => $this->target_model,
            'target_id' => $this->target_id,
            'properties' => $this->properties,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
