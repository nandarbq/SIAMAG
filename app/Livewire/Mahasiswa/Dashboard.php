<?php

namespace App\Livewire\Mahasiswa;

use App\Enums\AttendanceStatus;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Logbook;
use App\Models\Setting;
use App\Services\LogbookProgressService;
use App\Services\WorkdayService;
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
            ->whereDate('date', '>=', $monthStart->toDateString())
            ->whereDate('date', '<=', $monthEnd->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $workdays = app(WorkdayService::class);
        $todayDate = now()->startOfDay();

        return view('livewire.mahasiswa.dashboard', [
            'profile' => $profile,
            'today' => $today,
            'isWorkday' => $workdays->isWorkday($todayDate),
            'todayHoliday' => $workdays->holidayFor($todayDate),
            'nonWorkingLabel' => $workdays->nonWorkingLabel($todayDate),
            'hadir' => (int) ($monthCounts[AttendanceStatus::Hadir->value] ?? 0),
            'terlambat' => (int) ($monthCounts[AttendanceStatus::Terlambat->value] ?? 0),
            'izinSakit' => (int) ($monthCounts[AttendanceStatus::Izin->value] ?? 0) + (int) ($monthCounts[AttendanceStatus::Sakit->value] ?? 0),
            'logbookProgress' => app(LogbookProgressService::class)->for($user),
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
            'week' => $this->buildWeek($user->id, $workdays),
            'officeName' => (string) Setting::get('office_name', 'BMKG Banyuwangi'),
            'greeting' => $this->greeting(),
            'workStart' => Setting::get('work_start', '07:30'),
            'workEnd' => Setting::get('work_end', '16:00'),
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

    /**
     * @return array<int, array{date: Carbon, day_label: string, day: string, isToday: bool, tone: string, icon: string, status_label: string}>
     */
    private function buildWeek(int $userId, WorkdayService $workdays): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->startOfDay();

        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->date->toDateString());

        $holidays = Holiday::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (Holiday $holiday) => $holiday->date->toDateString());

        $holidayDates = $holidays->keys()->all();
        $days = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $attendance = $attendances->get($date->toDateString());
            $isWorkday = $workdays->isWorkday($date, $holidayDates);

            [$tone, $icon, $statusLabel] = match (true) {
                $attendance?->status === AttendanceStatus::Hadir => ['bg-emerald-50 text-emerald-700 ring-emerald-600/10', 'check-circle', 'Hadir'],
                $attendance?->status === AttendanceStatus::Terlambat => ['bg-amber-50 text-amber-700 ring-amber-600/10', 'clock', 'Telat'],
                $attendance?->status === AttendanceStatus::Izin => ['bg-brand-50 text-brand-700 ring-brand-600/10', 'calendar', 'Izin'],
                $attendance?->status === AttendanceStatus::Sakit => ['bg-brand-50 text-brand-700 ring-brand-600/10', 'calendar', 'Sakit'],
                ! $isWorkday => ['bg-sky-50 text-sky-700 ring-sky-600/10', 'calendar', $date->isWeekend() ? 'Weekend' : 'Libur'],
                default => ['bg-slate-50 text-slate-400 ring-slate-500/10', 'clock', 'Belum'],
            };

            $days[] = [
                'date' => $date,
                'day_label' => $date->translatedFormat('D'),
                'day' => $date->format('d'),
                'isToday' => $date->isToday(),
                'tone' => $tone,
                'icon' => $icon,
                'status_label' => $statusLabel,
            ];
        }

        return $days;
    }
}
