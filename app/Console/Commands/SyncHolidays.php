<?php

namespace App\Console\Commands;

use App\Services\HolidaySyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('holidays:sync {year? : Tahun yang akan disinkronkan} {--force : Timpa hari libur manual}')]
#[Description('Sinkronkan hari libur nasional dan cuti bersama dari sumber resmi')]
class SyncHolidays extends Command
{
    public function handle(HolidaySyncService $service): int
    {
        $year = $this->argument('year');
        $force = (bool) $this->option('force');
        $years = $year !== null
            ? [(int) $year]
            : [now()->year, now()->addYear()->year];

        $failed = false;

        foreach ($years as $targetYear) {
            if ($targetYear < 2020 || $targetYear > 2100) {
                $this->error('Tahun harus berada di antara 2020 dan 2100.');

                return self::FAILURE;
            }

            try {
                $result = $service->syncYear($targetYear, $force);

                $this->info(sprintf(
                    'Hari libur %d: %d ditambahkan, %d diperbarui, %d dilewati (%s).',
                    $targetYear,
                    $result['created'],
                    $result['updated'],
                    $result['skipped'],
                    $result['source'],
                ));
            } catch (Throwable $exception) {
                $this->error("Gagal menyinkronkan hari libur {$targetYear}: {$exception->getMessage()}");

                if ($year !== null || $targetYear === now()->year) {
                    $failed = true;
                }
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
