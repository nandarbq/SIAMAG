<?php

namespace App\Services;

use App\Models\InternProfile;
use App\Models\Logbook;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class LogbookExportService
{
    public function __construct(
        private readonly InternshipWeekService $weeks,
    ) {}

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
            ->whereDate('date', '>=', $profile->start_date->toDateString())
            ->whereDate('date', '<=', $profile->end_date->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    public function rowsFor(User $user): Collection
    {
        $profile = $user->internProfile;

        if (! $profile) {
            return collect();
        }

        return $this->logbooksFor($user)->values()->map(function (Logbook $logbook, int $index) use ($profile) {
            $photo = $this->thumbnailBinary($logbook);

            return [
                'number' => $index + 1,
                'week' => $this->weeks->label($profile, $logbook->date),
                'activity' => $logbook->activity ?: '-',
                'description' => $logbook->description ?: '-',
                'photo_binary' => $photo,
                'photo_data_uri' => $photo ? 'data:image/jpeg;base64,'.base64_encode($photo) : null,
            ];
        });
    }

    private function thumbnailBinary(Logbook $logbook): ?string
    {
        if (! $logbook->attachment_path) {
            return null;
        }

        try {
            $disk = Storage::disk('local');

            if (! $disk->exists($logbook->attachment_path)) {
                return null;
            }

            $manager = ImageManager::usingDriver(Driver::class);
            $image = $manager->decodeBinary($disk->get($logbook->attachment_path));

            return (string) $image->scaleDown(width: 320)->encodeUsingFileExtension('jpg', quality: 75);
        } catch (\Throwable $exception) {
            Log::warning('Gagal membuat thumbnail dokumentasi logbook.', [
                'logbook_id' => $logbook->id,
                'attachment_path' => $logbook->attachment_path,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
