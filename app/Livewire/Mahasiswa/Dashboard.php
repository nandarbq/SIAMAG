<?php

namespace App\Livewire\Mahasiswa;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LogbookStatus;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Setting;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard Mahasiswa')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $profile = $user->internProfile;
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthCounts = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $chart = $this->buildChart($user->id);

        return view('livewire.mahasiswa.dashboard', [
            'profile' => $profile,
            'today' => $today,
            'hadir' => (int) ($monthCounts[AttendanceStatus::Hadir->value] ?? 0),
            'terlambat' => (int) ($monthCounts[AttendanceStatus::Terlambat->value] ?? 0),
            'izinSakit' => (int) ($monthCounts[AttendanceStatus::Izin->value] ?? 0) + (int) ($monthCounts[AttendanceStatus::Sakit->value] ?? 0),
            'logbookApproved' => Logbook::query()
                ->where('user_id', $user->id)
                ->where('status', LogbookStatus::Approved)
                ->count(),
            'logbookPending' => Logbook::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [LogbookStatus::Draft, LogbookStatus::Rejected])
                ->count(),
            'leavePending' => $user->leaveRequests()->where('status', LeaveStatus::Pending)->count(),
            'recentLogbooks' => Logbook::query()
                ->where('user_id', $user->id)
                ->latest('date')
                ->limit(5)
                ->get(),
            'announcements' => Announcement::query()
                ->where('is_active', true)
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'chart' => $chart,
            'workStart' => Setting::get('work_start', '07:30'),
            'workEnd' => Setting::get('work_end', '16:00'),
        ]);
    }

    private function buildChart(int $userId): array
    {
        $labels = [];
        $present = [];
        $late = [];
        $permit = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = Carbon::parse($date)->translatedFormat('D, d/m');

            $status = Attendance::query()
                ->where('user_id', $userId)
                ->whereDate('date', $date->toDateString())
                ->value('status');

            $present[] = $status === AttendanceStatus::Hadir ? 1 : 0;
            $late[] = $status === AttendanceStatus::Terlambat ? 1 : 0;
            $permit[] = in_array($status, [AttendanceStatus::Izin, AttendanceStatus::Sakit], true) ? 1 : 0;
        }

        return [
            'labels' => $labels,
            'present' => $present,
            'late' => $late,
            'permit' => $permit,
        ];
    }
}
