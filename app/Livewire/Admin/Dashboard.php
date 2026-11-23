<?php

namespace App\Livewire\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Logbook;
use App\Models\Setting;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\WorkdayService;
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
        $studentIds = User::currentInterns()->pluck('id');

        $totalStudents = $studentIds->count();
        $today = now()->toDateString();

        $todayQuery = Attendance::query()
            ->whereIn('user_id', $studentIds)
            ->whereDate('date', $today);

        $present = (clone $todayQuery)->whereIn('status', [AttendanceStatus::Hadir, AttendanceStatus::Terlambat])->count();
        $late = (clone $todayQuery)->where('status', AttendanceStatus::Terlambat)->count();
        $permit = (clone $todayQuery)->whereIn('status', [AttendanceStatus::Izin, AttendanceStatus::Sakit])->count();
        $recorded = (clone $todayQuery)->distinct('user_id')->count('user_id');

        $attendanceService = app(AttendanceService::class);
        $alpha = $attendanceService->isWorkday(now()->startOfDay())
            ? max(0, $totalStudents - $recorded)
            : 0;

        $chart = $this->buildChart($studentIds, $totalStudents, $attendanceService);

        $workdays = app(WorkdayService::class);
        $todayDate = now()->startOfDay();

        return view('livewire.admin.dashboard', [
            'greeting' => $this->greeting(),
            'isWorkday' => $workdays->isWorkday($todayDate),
            'nonWorkingLabel' => $workdays->nonWorkingLabel($todayDate),
            'officeName' => (string) Setting::get('office_name', 'BMKG Banyuwangi'),
            'workStart' => Setting::get('work_start', '07:30'),
            'workEnd' => Setting::get('work_end', '16:00'),
            'totalStudents' => $totalStudents,
            'present' => $present,
            'late' => $late,
            'permit' => $permit,
            'alpha' => $alpha,
            'ongoingSick' => LeaveRequest::query()
                ->where('type', LeaveType::Sakit->value)
                ->where('status', LeaveStatus::Approved->value)
                ->whereNull('end_date')
                ->whereDate('start_date', '<=', today())
                ->count(),
            'chart' => $chart,
            'recentAttendances' => Attendance::query()
                ->with('user')
                ->whereIn('user_id', $studentIds)
                ->latest('date')
                ->latest('check_in_at')
                ->limit(6)
                ->get(),
            'recentLogbooks' => Logbook::query()
                ->with('user')
                ->latest('date')
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

    private function greeting(): string
    {
        return match (true) {
            now()->hour < 11 => 'Selamat pagi',
            now()->hour < 15 => 'Selamat siang',
            now()->hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }

    private function buildChart($studentIds, int $totalStudents, AttendanceService $attendanceService): array
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
            $alpha[] = $attendanceService->isWorkday($date->copy()->startOfDay())
                ? max(0, $totalStudents - $recorded)
                : 0;
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
