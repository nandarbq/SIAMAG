<?php

namespace App\Services;

use App\Models\InternProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InternshipWeekService
{
    public function count(InternProfile $profile): int
    {
        $start = $profile->start_date->copy()->startOfDay();
        $end = $profile->end_date->copy()->startOfDay();

        return (int) ceil(($start->diffInDays($end) + 1) / 7);
    }

    public function number(InternProfile $profile, Carbon $date): int
    {
        $start = $profile->start_date->copy()->startOfDay();
        $days = $start->diffInDays($date->copy()->startOfDay());

        return min($this->count($profile), max(1, (int) floor($days / 7) + 1));
    }

    public function range(InternProfile $profile, int $week): array
    {
        $start = $profile->start_date->copy()->startOfDay()->addDays(($week - 1) * 7);
        $end = $start->copy()->addDays(6);
        $internshipEnd = $profile->end_date->copy()->startOfDay();

        if ($end->greaterThan($internshipEnd)) {
            $end = $internshipEnd;
        }

        return ['start' => $start, 'end' => $end];
    }

    public function label(InternProfile $profile, Carbon $date): string
    {
        $week = $this->number($profile, $date);

        return $this->labelForWeek($profile, $week);
    }

    public function labelForWeek(InternProfile $profile, int $week): string
    {
        ['start' => $start, 'end' => $end] = $this->range($profile, $week);

        return sprintf(
            'Pekan %d · %s–%s',
            $week,
            $start->translatedFormat('d M'),
            $end->translatedFormat('d M Y')
        );
    }

    public function weeks(InternProfile $profile): Collection
    {
        return collect(range(1, $this->count($profile)))->map(function (int $week) use ($profile) {
            $range = $this->range($profile, $week);

            return [
                'number' => $week,
                'start' => $range['start'],
                'end' => $range['end'],
                'label' => $this->labelForWeek($profile, $week),
            ];
        });
    }
}
