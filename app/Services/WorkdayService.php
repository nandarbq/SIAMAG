<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Setting;
use Carbon\Carbon;

class WorkdayService
{
    private const DEFAULT_WORKDAYS = [1, 2, 3, 4, 5];

    public function workdays(): array
    {
        $days = collect(explode(',', (string) Setting::get('workdays', implode(',', self::DEFAULT_WORKDAYS))))
            ->map(fn (string $day) => (int) trim($day))
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $days ?: self::DEFAULT_WORKDAYS;
    }

    public function isConfiguredWorkday(Carbon $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->workdays(), true);
    }

    public function isWorkday(Carbon $date, ?array $holidayDates = null): bool
    {
        if (! $this->isConfiguredWorkday($date)) {
            return false;
        }

        $holidayDates ??= $this->holidayDatesBetween($date, $date);

        return ! in_array($date->toDateString(), $holidayDates, true);
    }

    public function holidayFor(Carbon $date): ?Holiday
    {
        return Holiday::query()
            ->whereDate('date', $date->toDateString())
            ->first();
    }

    public function holidayDatesBetween(Carbon $start, Carbon $end): array
    {
        return Holiday::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->all();
    }

    public function nonWorkingLabel(Carbon $date): ?string
    {
        if ($holiday = $this->holidayFor($date)) {
            return $holiday->name;
        }

        if ($this->isConfiguredWorkday($date)) {
            return null;
        }

        return $date->isWeekend() ? 'Akhir pekan' : 'Bukan hari kerja';
    }
}
