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
use App\Services\WorkdayService;
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

        $workdays = app(WorkdayService::class);
        $todayDate = now()->startOfDay();
        $holidayDates = $workdays->holidayDatesBetween($todayDate->copy()->subDays(6), $todayDate);

        $alpha = $workdays->isWorkday($todayDate, $holidayDates)
            ? max(0, $totalStudents - $recorded)
            : 0;

        $chart = $this->buildChart($studentIds, $totalStudents, $workdays, $holidayDates);

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

    private function buildChart($studentIds, int $totalStudents, WorkdayService $workdays, array $holidayDates): array
    {
        $dates = collect(range(0, 6))
            ->map(fn (int $daysAgo) => now()->subDays($daysAgo))
            ->reverse()
            ->values();

        $counts = Attendance::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('date', [$dates->first()->toDateString(), $dates->last()->toDateString()])
            ->selectRaw('date, status, count(*) as total')
            ->groupBy('date', 'status')
            ->get()
            ->mapWithKeys(function (Attendance $attendance) {
                return [$attendance->date->toDateString().'|'.$attendance->status->value => (int) $attendance->total];
            });

        $labels = [];
        $present = [];
        $late = [];
        $permit = [];
        $alpha = [];

        foreach ($dates as $date) {
            $labels[] = $date->translatedFormat('D, d/m');

            $key = $date->toDateString();
            $hadir = (int) ($counts[$key.'|'.AttendanceStatus::Hadir->value] ?? 0);
            $terlambat = (int) ($counts[$key.'|'.AttendanceStatus::Terlambat->value] ?? 0);
            $izin = (int) ($counts[$key.'|'.AttendanceStatus::Izin->value] ?? 0);
            $sakit = (int) ($counts[$key.'|'.AttendanceStatus::Sakit->value] ?? 0);
            $recorded = $hadir + $terlambat + $izin + $sakit;

            $present[] = $hadir;
            $late[] = $terlambat;
            $permit[] = $izin + $sakit;
            $alpha[] = $workdays->isWorkday($date->copy()->startOfDay(), $holidayDates)
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
