<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.dashboard')->with('status', 'You have been logged out.');
    }

    private function users(): array
    {
        return [
            ['avatar' => 'SC', 'name' => 'Sarah Chen', 'email' => 'sarah.chen@syncore.co', 'unit' => 'Engineering', 'role' => 'Admin'],
            ['avatar' => 'MW', 'name' => 'Marcus Williams', 'email' => 'm.williams@syncore.co', 'unit' => 'Product', 'role' => 'User'],
            ['avatar' => 'AP', 'name' => 'Aisha Patel', 'email' => 'a.patel@syncore.co', 'unit' => 'Design', 'role' => 'User'],
            ['avatar' => 'JR', 'name' => 'James Rodriguez', 'email' => 'j.rodriguez@syncore.co', 'unit' => 'Marketing', 'role' => 'User'],
            ['avatar' => 'ET', 'name' => 'Emma Thompson', 'email' => 'e.thompson@syncore.co', 'unit' => 'Human Resources', 'role' => 'Admin'],
            ['avatar' => 'DK', 'name' => 'David Kim', 'email' => 'd.kim@syncore.co', 'unit' => 'Finance', 'role' => 'User'],
            ['avatar' => 'LP', 'name' => 'Lisa Park', 'email' => 'l.park@syncore.co', 'unit' => 'Operations', 'role' => 'User'],
            ['avatar' => 'RJ', 'name' => 'Robert Johnson', 'email' => 'r.johnson@syncore.co', 'unit' => 'Engineering', 'role' => 'User'],
        ];
    }

    private function meetingSummary(): array
    {
        return [
            ['title' => 'Monthly IT Evaluation', 'date' => '2026-08-12', 'time' => '09:00', 'location' => 'Conference Room A', 'unit' => 'Engineering', 'host' => 'Sarah Chen', 'present' => 18, 'capacity' => 20, 'status' => 'Completed'],
            ['title' => 'Q3 Strategy Review', 'date' => '2026-08-12', 'time' => '14:00', 'location' => 'Board Room', 'unit' => 'Executive', 'host' => 'Sarah Chen', 'present' => 9, 'capacity' => 12, 'status' => 'Ongoing'],
            ['title' => 'Product Roadmap Planning', 'date' => '2026-08-15', 'time' => '10:30', 'location' => 'Conference Room B', 'unit' => 'Product', 'host' => 'Marcus Williams', 'status' => 'Upcoming'],
            ['title' => 'Security Awareness Training', 'date' => '2026-08-18', 'time' => '13:00', 'location' => 'Training Hall', 'unit' => 'All Departments', 'host' => 'Sarah Chen', 'status' => 'Upcoming'],
        ];
    }

    /**
     * Full dummy meeting list shared by Meeting Management & Meeting Report.
     * TODO(backend): replace with real query, keyed by real meeting id instead of array index.
     */
    private function meetingsAll(): array
    {
        return array_merge($this->meetingSummary(), [
            ['title' => 'Annual Budget Review', 'date' => '2026-08-20', 'time' => '11:00', 'location' => 'Finance Suite', 'unit' => 'Finance', 'host' => 'David Kim', 'status' => 'Upcoming'],
            ['title' => 'Design System Workshop', 'date' => '2026-08-25', 'time' => '09:30', 'location' => 'Studio Lab', 'unit' => 'Design', 'host' => 'Aisha Patel', 'status' => 'Upcoming'],
            ['title' => 'Evaluasi Kinerja Semester 1', 'date' => '2026-07-28', 'time' => '10:00', 'location' => 'Board Room', 'unit' => 'HR', 'host' => 'Emma Thompson', 'present' => 28, 'capacity' => 30, 'status' => 'Completed'],
            ['title' => 'Sprint Retrospective — Jul', 'date' => '2026-07-31', 'time' => '15:00', 'location' => 'Zoom', 'unit' => 'Engineering', 'host' => 'Sarah Chen', 'present' => 16, 'capacity' => 16, 'status' => 'Completed'],
            ['title' => 'Customer Success Review', 'date' => '2026-07-22', 'time' => '13:30', 'location' => 'Conference Room B', 'unit' => 'Product', 'host' => 'Marcus Williams', 'present' => 12, 'capacity' => 15, 'status' => 'Completed'],
        ]);
    }

    /**
     * Dummy report detail per meeting index (only meetings with status Completed have one).
     * TODO(backend): replace with real notulen records related to the meeting.
     */
    private function notulenReports(): array
    {
        return [
            0 => [
                'docNo' => 'RPT-IT-2026-08', 'author' => 'Sarah Chen', 'approvedBy' => 'Direktur IT', 'version' => 'Final v1.0',
                'agenda' => [
                    ['title' => 'Opening', 'body' => ['Rapat dibuka oleh Kepala Unit IT, Ibu Sarah Chen, pada pukul 09.05 WIB. Rapat dihadiri oleh 18 dari 20 anggota unit dan tamu undangan dari departemen terkait.']],
                    ['title' => 'Agenda 1 - Evaluasi Kinerja Infrastruktur Q3 2026', 'body' => ['Uptime server mencapai 99.7%, melampaui target SLA sebesar 99.5%.', 'Insiden jaringan berkurang 22% dibandingkan Q2 2026 setelah penerapan monitoring proaktif.']],
                    ['title' => 'Agenda 2 - Rencana Migrasi Sistem ke Cloud Hybrid', 'body' => ['Proposal migrasi phased approach disetujui, target selesai pada Desember 2026.']],
                ],
            ],
            6 => [
                'docNo' => 'RPT-HR-2026-07', 'author' => 'Emma Thompson', 'approvedBy' => 'Direktur HR', 'version' => 'Final v1.0',
                'agenda' => [
                    ['title' => 'Opening', 'body' => ['Rapat evaluasi kinerja semester 1 dibuka oleh Kepala Unit HR, Ibu Emma Thompson, pukul 10.05 WIB.']],
                    ['title' => 'Agenda 1 - Rekap Pencapaian KPI Semester 1', 'body' => ['Rata-rata pencapaian KPI seluruh unit berada di angka 92%, melampaui target 85%.']],
                    ['title' => 'Agenda 2 - Rencana Pengembangan Talent', 'body' => ['Program mentoring internal akan dimulai kuartal berikutnya.']],
                ],
            ],
            7 => [
                'docNo' => 'RPT-ENG-2026-07', 'author' => 'Sarah Chen', 'approvedBy' => 'Direktur Engineering', 'version' => 'Final v1.0',
                'agenda' => [
                    ['title' => 'Opening', 'body' => ['Sprint retrospective dibuka oleh Scrum Master, pukul 15.00 WIB via Zoom.']],
                    ['title' => 'Agenda 1 - Review Sprint Sebelumnya', 'body' => ['Seluruh 14 story point berhasil diselesaikan tepat waktu.']],
                    ['title' => 'Agenda 2 - Action Items', 'body' => ['Tim sepakat menambah automated testing coverage di sprint berikutnya.']],
                ],
            ],
            8 => [
                'docNo' => 'RPT-PRD-2026-07', 'author' => 'Marcus Williams', 'approvedBy' => 'Direktur Product', 'version' => 'Final v1.0',
                'agenda' => [
                    ['title' => 'Opening', 'body' => ['Rapat Customer Success Review dibuka pukul 13.35 WIB di Conference Room B.']],
                    ['title' => 'Agenda 1 - Customer Health Score', 'body' => ['Skor kesehatan pelanggan rata-rata naik dari 78 ke 85 dibanding kuartal sebelumnya.']],
                    ['title' => 'Agenda 2 - Rencana Onboarding Baru', 'body' => ['Tim menyepakati proses onboarding baru diluncurkan bulan depan.']],
                ],
            ],
        ];
    }

    public function index()
    {
        return view('admin.dashboard', [
            'users' => $this->users(),
            'totalUsers' => count($this->users()),
            'totalAdmins' => 2,
            'totalUnits' => 7,
            'meetings' => $this->meetingSummary(),
        ]);
    }

    public function createNotulen()
    {
        return view('admin.notulen-create', [
            'users' => $this->users(),
            'meeting' => [
                'title' => 'Evaluasi Bulanan Unit IT',
                'date' => '2026-08-12',
                'time' => '09:00 - 11:35 WIB',
                'location' => 'Conference Room A',
                'host' => 'Sarah Chen',
                'participants' => 18,
                'capacity' => 20,
            ],
        ]);
    }

    public function storeNotulen(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'location' => 'required|string|max:255',
            'agenda' => 'required|string|max:1500',
            'conclusion' => 'nullable|string|max:1500',
        ]);

        session(['admin_notulen' => $validated]);

        return redirect()->route('admin.notulen')->with('success', 'Notulen berhasil disimpan.');
    }
    public function notulenIndex()
    {
        $meetings = $this->meetingsAll();
        $reports = $this->notulenReports();

        $completed = [];
        foreach ($meetings as $i => $m) {
            if ($m['status'] === 'Completed') {
                $present = $m['present'] ?? null;
                $capacity = $m['capacity'] ?? null;
                $completed[] = [
                    'index' => $i,
                    'title' => $m['title'],
                    'date' => $m['date'],
                    'unit' => $m['unit'],
                    'present' => $present,
                    'capacity' => $capacity,
                    'percent' => ($present !== null && $capacity) ? round($present / $capacity * 100) : null,
                    'docNo' => $reports[$i]['docNo'] ?? '—',
                ];
            }
        }

        return view('admin.notulen-list', ['reports' => $completed]);
    }

    public function notulen($index = 0)
    {
        $index = (int) $index;
        $meetings = $this->meetingsAll();
        $meeting = $meetings[$index] ?? $meetings[0];
        $meeting['attendance'] = $meeting['present'] ?? 0;

        $reports = $this->notulenReports();
        $report = $reports[$index] ?? [
            'docNo' => 'RPT-GEN-2026-00',
            'author' => $meeting['host'] ?? 'Admin',
            'approvedBy' => '—',
            'version' => 'Draft',
            'agenda' => [
                ['title' => 'Opening', 'body' => ['Belum ada notulen tercatat untuk meeting ini.']],
            ],
        ];

        return view('admin.notulen', [
            'notulen' => session('admin_notulen'),
            'users' => $this->users(),
            'meeting' => $meeting,
            'report' => $report,
        ]);
    }

    public function editNotulen()
    {
        $saved = session('admin_notulen', []);

        return view('admin.notulen-editor', [
            'notulen' => $saved,
            'meeting' => [
                'title' => 'Evaluasi Bulanan Unit IT',
                'date' => '2026-08-12',
                'time' => '09:00 - 11:35 WIB',
                'location' => 'Conference Room A',
                'host' => 'Sarah Chen',
                'participants' => 18,
                'capacity' => 20,
            ],
        ]);
    }

    public function meetings()
    {
        $meetings = $this->meetingsAll();

        return view('admin.meetings', compact('meetings'));
    }
}