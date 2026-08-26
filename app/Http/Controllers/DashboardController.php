<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();

        // Base Statistics depending on Role
        $stats = [
            'total_agendas' => Agenda::visibleTo($user)->count(),
            'ongoing_agendas' => Agenda::visibleTo($user)->where('status', 'ongoing')->count(),
            'upcoming_agendas' => Agenda::visibleTo($user)->where('status', 'scheduled')->count(),
            'completed_agendas' => Agenda::visibleTo($user)->where('status', 'completed')->count(),
        ];

        if ($user->isAdministrator()) {
            $stats['total_users'] = User::count();
            $stats['total_units'] = Unit::count();
            $stats['total_attendances'] = Attendance::count();
            $recentLogs = ActivityLog::with('user.unit')->latest('id')->limit(8)->get();
        } elseif ($user->isAdmin()) {
            $stats['total_users'] = User::forUnit($user->unit_id)->count();
            $stats['total_attendances'] = Attendance::whereHas('user', function ($q) use ($user) {
                $q->where('unit_id', $user->unit_id);
            })->count();
            $recentLogs = ActivityLog::whereHas('user', function ($q) use ($user) {
                $q->where('unit_id', $user->unit_id);
            })->latest('id')->limit(8)->get();
        } else {
            // Staff
            $stats['my_attendances'] = Attendance::where('user_id', $user->id)->count();
            $recentLogs = ActivityLog::where('user_id', $user->id)->latest('id')->limit(5)->get();
        }

        // Active / Ongoing Agendas available right now
        $activeAgendas = Agenda::visibleTo($user)
            ->with(['creator', 'units', 'attendances'])
            ->whereIn('status', ['ongoing', 'scheduled'])
            ->orderBy('waktu_mulai', 'asc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('user', 'stats', 'recentLogs', 'activeAgendas'));
    }
}
