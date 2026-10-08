<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersByDateRange;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    use FiltersByDateRange;
    /**
     * Display a listing of system activity logs.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $currentUser = Auth::user();
        $query = ActivityLog::with('user.unit')->latest('id');

        // Filter by Activity Type
        if ($type = $request->input('type')) {
            $query->where('activity_type', $type);
        }

        // Filter by Specific User
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
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
                $q->whereLike('description', "%{$search}%")
                  ->orWhereLike('ip_address', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->whereLike('name', "%{$search}%")
                         ->orWhereLike('nip', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(15)->withQueryString();

        // Both dropdowns are bounded. Loading every employee in the agency to
        // populate a <select> grows linearly with headcount and turns a routine
        // page view into a full table read.
        $activityTypes = ActivityLog::query()
            ->distinct()
            ->orderBy('activity_type')
            ->limit(50)
            ->pluck('activity_type');

        $users = User::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'nip']);

        return view('logs.index', compact('logs', 'activityTypes', 'users'));
    }
}
