<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\InternProfile;
use App\Models\Logbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LogbookProgressService
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function for(User $user): array
    {
        $profile = $user->internProfile;

        if (! $profile) {
            return $this->empty('no_profile');
        }

        $today = today();
        $start = $profile->start_date->copy()->startOfDay();
        $end = $profile->end_date->copy()->startOfDay();

        $total = Logbook::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->count();

        if ($today->lessThan($start)) {
            return $this->empty('not_started') + ['total' => $total];
        }

        $expectedEnd = $today->greaterThan($end) ? $end->copy() : $today->copy()->subDay();

        $expectedDates = $this->expectedDates($user, $start, $expectedEnd);
        $logbookDates = Logbook::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique();

        $filled = $expectedDates->intersect($logbookDates)->count();
        $missingDates = $expectedDates->diff($logbookDates)->values();
        $expectedCount = $expectedDates->count();

        return [
            'expected' => $expectedCount,
            'filled' => $filled,
            'missing' => $missingDates->count(),
            'percentage' => $expectedCount > 0 ? (int) round(($filled / $expectedCount) * 100) : null,
            'missing_dates' => $missingDates->all(),
            'total' => $total,
            'today_status' => $this->todayStatus($user, $profile, $today),
        ];
    }

    private function expectedDates(User $user, Carbon $start, Carbon $end): Collection
    {
        if ($end->lessThan($start)) {
            return collect();
        }

        return Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', [AttendanceStatus::Hadir, AttendanceStatus::Terlambat])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->filter(fn (string $date) => $this->attendanceService->isWorkday(Carbon::parse($date)))
            ->unique()
            ->values();
    }

    private function todayStatus(User $user, InternProfile $profile, Carbon $today): string
    {
        if ($today->lessThan($profile->start_date) || $today->greaterThan($profile->end_date)) {
            return 'outside';
        }

        if (! $this->attendanceService->isWorkday($today)) {
            return 'holiday';
        }

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today->toDateString())
            ->first();

        if (! $attendance || ! in_array($attendance->status, [AttendanceStatus::Hadir, AttendanceStatus::Terlambat], true)) {
            if ($attendance && in_array($attendance->status, [AttendanceStatus::Izin, AttendanceStatus::Sakit, AttendanceStatus::Libur], true)) {
                return 'excused';
            }

            return 'not_attended';
        }

        $hasLogbook = Logbook::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today->toDateString())
            ->exists();

        return $hasLogbook ? 'filled' : 'missing';
    }

    private function empty(string $todayStatus): array
    {
        return [
            'expected' => 0,
            'filled' => 0,
            'missing' => 0,
            'percentage' => null,
            'missing_dates' => [],
            'total' => 0,
            'today_status' => $todayStatus,
        ];
    }
}
