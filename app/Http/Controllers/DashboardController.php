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

        // Aggregate the status tallies in a single pass. The simple equalities
        // collapse into one query; `upcoming` keeps its own because its predicate
        // is a date comparison rather than an equality.
        $agendaCounts = Agenda::visibleTo($user)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) AS ongoing")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed")
            ->first();

        $stats = [
            'total_agendas'     => (int) ($agendaCounts->total ?? 0),
            'ongoing_agendas'   => (int) ($agendaCounts->ongoing ?? 0),
            'completed_agendas' => (int) ($agendaCounts->completed ?? 0),
            'upcoming_agendas'  => Agenda::visibleTo($user)->upcoming()->count(),
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

        // Active / Ongoing Agendas available right now with pagination, prioritized by ongoing first.
        //
        // The full attendances relation is deliberately not loaded. The card needs
        // two things: the total headcount (withCount) and this user's own record
        // for the "Bukti" link, so the relation is constrained to the current user
        // and holds at most one row per agenda. Loading it whole would drag
        // selfie_path, signature_path, ip_address and user_agent (TEXT) for every
        // attendee of every card just to render a number.
        $activeAgendas = Agenda::visibleTo($user)
            ->with([
                // creator.unit, not creator: the card prints the organising
                // unit's code, so loading only `creator` left `unit` to be
                // lazy-loaded per row.
                'creator.unit',
                'units',
                'attendances' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->withCount('attendances')
            ->relevantForDashboard()
            ->orderByRaw("CASE WHEN status = 'ongoing' THEN 1 ELSE 2 END ASC")
            ->orderBy('waktu_mulai', 'asc')
            ->paginate(5, ['*'], 'page_agendas')
            ->withQueryString();

        return view('dashboard', compact('user', 'stats', 'activeAgendas'));
    }
}
