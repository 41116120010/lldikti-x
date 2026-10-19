<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isProfileContext = $request->routeIs('api.v1.profile.*', 'api.v1.auth.me')
            || $request->boolean('with_stats')
            || ($request->user() && $request->user()->id === $this->id && $request->routeIs('api.v1.auth.*'));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nip' => $this->nip,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'jabatan' => $this->jabatan,
            'pangkat_golongan' => $this->pangkat_golongan,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'role' => [
                'value' => $this->role?->value ?? (string) $this->role,
                'label' => $this->role_label,
            ],
            'is_active' => (bool) $this->is_active,
            'unit_id' => $this->unit_id,
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'security' => $this->when($isProfileContext, fn () => $this->resolveSecurityMeta()),
            'attendance_stats' => $this->when($isProfileContext, fn () => $this->resolveAttendanceStats()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Resolve attendance statistics for the user profile view.
     */
    protected function resolveAttendanceStats(): array
    {
        $user = $this->resource;

        // Total agendas relevant to this user
        $totalAgendas = Agenda::visibleTo($user)->count();
        $completedAgendas = Agenda::visibleTo($user)->where('status', 'completed')->count();

        // Total attendances recorded by this user
        $totalAttended = $user->attendances()->count();

        // Attendances in completed agendas
        $completedAttended = $user->attendances()
            ->whereHas('agenda', fn ($q) => $q->where('status', 'completed'))
            ->count();

        $attendanceRate = $completedAgendas === 0
            ? 100.0
            : round(min(($completedAttended / $completedAgendas) * 100, 100.0), 1);

        // On-time attendances (signed_at <= agenda.waktu_mulai)
        $onTimeCount = $user->attendances()
            ->whereHas('agenda', fn ($q) => $q->whereColumn('attendances.signed_at', '<=', 'agendas.waktu_mulai'))
            ->count();

        $onTimeRate = $totalAttended === 0
            ? 100.0
            : round(($onTimeCount / $totalAttended) * 100, 1);

        return [
            'total_agendas' => $totalAgendas,
            'total_agendas_label' => (string) $totalAgendas,
            'total_attended' => $totalAttended,
            'attendance_rate' => $attendanceRate,
            'attendance_rate_label' => "{$attendanceRate}%",
            'on_time_count' => $onTimeCount,
            'on_time_rate' => $onTimeRate,
            'on_time_rate_label' => "{$onTimeRate}%",
            'period_label' => 'Tahun Anggaran ' . now('Asia/Jakarta')->year,
        ];
    }

    /**
     * Resolve security metadata for the user profile view.
     */
    protected function resolveSecurityMeta(): array
    {
        $user = $this->resource;

        $lastPasswordLog = ActivityLog::where('user_id', $user->id)
            ->whereIn('activity_type', ['AUTH_CHANGE_PASSWORD', 'PASSWORD_RESET', 'AUTH_RESET_PASSWORD'])
            ->latest('id')
            ->first();

        $lastPasswordChangedAt = $lastPasswordLog?->created_at ?? $user->created_at;

        $lastPasswordChangedLabel = 'Belum pernah diubah';
        if ($lastPasswordChangedAt) {
            $diffDays = (int) $lastPasswordChangedAt->diffInDays(now('Asia/Jakarta'));
            if ($diffDays === 0) {
                $lastPasswordChangedLabel = 'Hari ini';
            } elseif ($diffDays === 1) {
                $lastPasswordChangedLabel = 'Kemarin';
            } else {
                $lastPasswordChangedLabel = "Terakhir diperbarui {$diffDays} hari yang lalu";
            }
        }

        $lastLoginLog = ActivityLog::where('user_id', $user->id)
            ->where('activity_type', 'AUTH_API_LOGIN')
            ->latest('id')
            ->first();

        $lastLoginAt = $lastLoginLog?->created_at;
        $lastLoginLabel = null;
        if ($lastLoginAt) {
            $diffMinutes = (int) $lastLoginAt->diffInMinutes(now('Asia/Jakarta'));
            if ($diffMinutes < 1) {
                $lastLoginLabel = 'Baru saja';
            } elseif ($diffMinutes < 60) {
                $lastLoginLabel = "{$diffMinutes} menit yang lalu";
            } elseif ($diffMinutes < 1440) {
                $diffHours = (int) floor($diffMinutes / 60);
                $lastLoginLabel = "{$diffHours} jam yang lalu";
            } else {
                $diffDays = (int) floor($diffMinutes / 1440);
                $lastLoginLabel = "{$diffDays} hari yang lalu";
            }
        }

        return [
            'last_password_changed_at' => $lastPasswordChangedAt?->toIso8601String(),
            'last_password_changed_label' => $lastPasswordChangedLabel,
            'last_login_at' => $lastLoginAt?->toIso8601String(),
            'last_login_label' => $lastLoginLabel,
        ];
    }
}
