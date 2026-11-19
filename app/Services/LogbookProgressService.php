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
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly InternshipWeekService $weeks,
    ) {}

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
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->count();

        if ($today->lessThan($start)) {
            return $this->empty('not_started') + ['total' => $total];
        }

        $expectedEnd = $today->greaterThan($end) ? $end->copy() : $today->copy();
        $expectedDates = $this->expectedDates($user, $start, $expectedEnd);
        $logbookDates = Logbook::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique();

        $expectedWeeks = $this->weeks->weeks($profile)
            ->filter(fn (array $week) => $expectedDates->contains(
                fn (string $date) => Carbon::parse($date)->betweenIncluded($week['start'], $week['end'])
            ))
            ->map(fn (array $week) => [
                'number' => $week['number'],
                'start' => $week['start'],
                'end' => $week['end'],
                'label' => $week['label'],
                'filled' => $logbookDates->contains(
                    fn (string $date) => Carbon::parse($date)->betweenIncluded($week['start'], $week['end'])
                ),
            ])
            ->values();

        $filled = $expectedWeeks->where('filled', true)->count();
        $missingWeeks = $expectedWeeks->where('filled', false)->values()->map(fn (array $week) => [
            'number' => $week['number'],
            'start' => $week['start'],
            'end' => $week['end'],
            'label' => $week['label'],
        ])->all();
        $expectedCount = $expectedWeeks->count();

        return [
            'expected' => $expectedCount,
            'filled' => $filled,
            'missing' => count($missingWeeks),
            'percentage' => $expectedCount > 0 ? (int) round(($filled / $expectedCount) * 100) : null,
            'missing_weeks' => $missingWeeks,
            'total' => $total,
            'week_status' => $this->weekStatus($profile, $expectedWeeks, $today),
        ];
    }

    private function expectedDates(User $user, Carbon $start, Carbon $end): Collection
    {
        if ($end->lessThan($start)) {
            return collect();
        }

        return Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->whereIn('status', [AttendanceStatus::Hadir, AttendanceStatus::Terlambat])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->filter(fn (string $date) => $this->attendanceService->isWorkday(Carbon::parse($date)))
            ->unique()
            ->values();
    }

    private function weekStatus(InternProfile $profile, Collection $expectedWeeks, Carbon $today): string
    {
        if ($today->lessThan($profile->start_date) || $today->greaterThan($profile->end_date)) {
            return $today->lessThan($profile->start_date) ? 'not_started' : 'outside';
        }

        $currentWeek = $expectedWeeks->firstWhere('number', $this->weeks->number($profile, $today));

        if ($currentWeek === null) {
            return 'not_required';
        }

        return $currentWeek['filled'] ? 'filled' : 'missing';
    }

    private function empty(string $weekStatus): array
    {
        return [
            'expected' => 0,
            'filled' => 0,
            'missing' => 0,
            'percentage' => null,
            'missing_weeks' => [],
            'total' => 0,
            'week_status' => $weekStatus,
        ];
    }
}
