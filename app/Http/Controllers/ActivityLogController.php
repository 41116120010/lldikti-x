<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of system activity logs.
     */
    public function index(Request $request): View
    {
        $currentUser = Auth::user();

        if (!$currentUser->isAdministrator()) {
            abort(403, 'Akses ditolak. Hanya Administrator yang dapat melihat log aktivitas.');
        }

        $query = ActivityLog::with('user.unit')->latest('id');

        // Filter by Activity Type
        if ($type = $request->input('type')) {
            $query->where('activity_type', $type);
        }

        // Filter by Specific User
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // Filter by Date Range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Search in description or IP
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('nip', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(15)->withQueryString();
        $activityTypes = ActivityLog::distinct()->pluck('activity_type');
        $users = User::orderBy('name')->get(['id', 'name', 'nip']);

        return view('logs.index', compact('logs', 'activityTypes', 'users'));
    }
}
