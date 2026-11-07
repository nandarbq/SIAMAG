<?php

namespace App\Livewire\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LogbookStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Logbook;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard Admin')]
class Dashboard extends Component
{
    public function render()
    {
        $studentIds = User::query()
            ->where('role', UserRole::Mahasiswa)
            ->where('is_active', true)
            ->pluck('id');

        $totalStudents = $studentIds->count();
        $today = now()->toDateString();

        $todayQuery = Attendance::query()
            ->whereIn('user_id', $studentIds)
            ->whereDate('date', $today);

        $present = (clone $todayQuery)->whereIn('status', [AttendanceStatus::Hadir, AttendanceStatus::Terlambat])->count();
        $late = (clone $todayQuery)->where('status', AttendanceStatus::Terlambat)->count();
        $permit = (clone $todayQuery)->whereIn('status', [AttendanceStatus::Izin, AttendanceStatus::Sakit])->count();
        $recorded = (clone $todayQuery)->distinct('user_id')->count('user_id');
        $alpha = max(0, $totalStudents - $recorded);

        $chart = $this->buildChart($studentIds, $totalStudents);

        return view('livewire.admin.dashboard', [
            'totalStudents' => $totalStudents,
            'present' => $present,
            'late' => $late,
            'permit' => $permit,
            'alpha' => $alpha,
            'pendingLogbooks' => Logbook::query()->where('status', LogbookStatus::Submitted)->count(),
            'pendingLeaves' => LeaveRequest::query()->where('status', LeaveStatus::Pending)->count(),
            'chart' => $chart,
            'recentAttendances' => Attendance::query()
                ->with('user')
                ->whereIn('user_id', $studentIds)
                ->latest('date')
                ->latest('check_in_at')
                ->limit(6)
                ->get(),
            'pendingReviews' => Logbook::query()
                ->with('user')
                ->where('status', LogbookStatus::Submitted)
                ->latest()
                ->limit(5)
                ->get(),
            'announcements' => Announcement::query()
                ->where('is_active', true)
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }

    private function buildChart($studentIds, int $totalStudents): array
    {
        $labels = [];
        $present = [];
        $late = [];
        $permit = [];
        $alpha = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = Carbon::parse($date)->translatedFormat('D, d/m');

            $counts = Attendance::query()
                ->whereIn('user_id', $studentIds)
                ->whereDate('date', $date->toDateString())
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $hadir = (int) ($counts[AttendanceStatus::Hadir->value] ?? 0);
            $terlambat = (int) ($counts[AttendanceStatus::Terlambat->value] ?? 0);
            $izin = (int) ($counts[AttendanceStatus::Izin->value] ?? 0);
            $sakit = (int) ($counts[AttendanceStatus::Sakit->value] ?? 0);
            $recorded = $hadir + $terlambat + $izin + $sakit;

            $present[] = $hadir;
            $late[] = $terlambat;
            $permit[] = $izin + $sakit;
            $alpha[] = $date->isWeekend() ? 0 : max(0, $totalStudents - $recorded);
        }

        return [
            'labels' => $labels,
            'present' => $present,
            'late' => $late,
            'permit' => $permit,
            'alpha' => $alpha,
        ];
    }
}
