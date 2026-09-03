<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Attendance;
use App\Models\Unit;
use App\Models\User;
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
        } elseif ($user->isAdmin()) {
            $stats['total_users'] = User::forUnit($user->unit_id)->count();
            $stats['total_attendances'] = Attendance::whereHas('user', function ($q) use ($user) {
                $q->where('unit_id', $user->unit_id);
            })->count();
        } else {
            // Staff
            $stats['my_attendances'] = Attendance::where('user_id', $user->id)->count();
        }

        // Active / Ongoing Agendas available right now with pagination
        $activeAgendas = Agenda::visibleTo($user)
            ->with(['creator', 'units', 'attendances'])
            ->whereIn('status', ['ongoing', 'scheduled'])
            ->orderBy('waktu_mulai', 'asc')
            ->paginate(5, ['*'], 'page_agendas')
            ->withQueryString();

        return view('dashboard', compact('user', 'stats', 'activeAgendas'));
    }
}
