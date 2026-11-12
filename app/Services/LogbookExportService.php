<?php

namespace App\Services;

use App\Models\InternProfile;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LogbookExportService
{
    public function leadDays(): int
    {
        return max(1, min(30, Setting::getInt('logbook_export_lead_days', 7)));
    }

    public function opensAt(InternProfile $profile): Carbon
    {
        $start = $profile->start_date->copy()->startOfDay();
        $opensAt = $profile->end_date->copy()->startOfDay()->subDays($this->leadDays());

        return $opensAt->lessThan($start) ? $start : $opensAt;
    }

    public function canExport(InternProfile $profile): bool
    {
        return today()->greaterThanOrEqualTo($this->opensAt($profile));
    }

    public function logbooksFor(User $user): Collection
    {
        $profile = $user->internProfile;

        if (! $profile) {
            return collect();
        }

        return $user->logbooks()
            ->whereBetween('date', [$profile->start_date->toDateString(), $profile->end_date->toDateString()])
            ->orderBy('date')
            ->get();
    }
}
