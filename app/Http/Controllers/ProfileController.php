<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByDateRange;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use FiltersByDateRange;

    /**
     * Display the user's profile edit form.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $user->load('unit');

        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information and password.
     */
    public function update(UpdateProfileRequest $request, UserService $userService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $userService->updateProfile($user, $request->validated());

        return redirect()->route('profile.edit')->with('success', 'Profil akun Anda berhasil diperbarui.');
    }

    /**
     * Display the authenticated user's activity logs.
     */
    public function logs(Request $request): View
    {
        $user = Auth::user();
        $query = ActivityLog::where('user_id', $user->id)->latest('id');

        // Filter by Activity Type
        if ($type = $request->input('type')) {
            $query->where('activity_type', $type);
        }

        // Filter by Date Range (timestamp comparison — keeps created_at index usable)
        $this->applyDateRange(
            $query,
            'created_at',
            $request->input('start_date'),
            $request->input('end_date'),
        );

        // Search in description or IP
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(10)->withQueryString();
        $activityTypes = ActivityLog::where('user_id', $user->id)->distinct()->pluck('activity_type');

        return view('profile.logs', compact('user', 'logs', 'activityTypes'));
    }
}
