<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('attendance:prune-photos {--days=30 : Jumlah hari foto disimpan}')]
#[Description('Hapus foto selfie absensi yang lebih lama dari batas penyimpanan.')]
class PruneAttendancePhotos extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $disk = Storage::disk('local');
        $cutoffDate = now()->subDays($days)->startOfDay();
        $deletedFiles = 0;
        $clearedAttendances = 0;

        $attendances = Attendance::query()
            ->where('date', '<', $cutoffDate)
            ->where(function ($query) {
                $query->whereNotNull('check_in_photo')
                    ->orWhereNotNull('check_out_photo');
            })
            ->get();

        foreach ($attendances as $attendance) {
            foreach (['check_in_photo', 'check_out_photo'] as $column) {
                $path = $attendance->{$column};

                if ($path && $disk->exists($path)) {
                    $disk->delete($path);
                    $deletedFiles++;
                }
            }

            $attendance->forceFill([
                'check_in_photo' => null,
                'check_out_photo' => null,
            ])->save();

            $clearedAttendances++;
        }

        $cutoff = now()->subDays($days)->getTimestamp();

        foreach ($disk->allFiles('attendance') as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
                $deletedFiles++;
            }
        }

        $this->info("Menghapus {$deletedFiles} file foto dan membersihkan {$clearedAttendances} data absensi.");

        return self::SUCCESS;
    }
}
