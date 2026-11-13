<?php

namespace App\Console\Commands;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attendance:sync-recorded-leaves')]
#[Description('Catat absensi untuk seluruh pengajuan izin dan sakit yang sudah tercatat.')]
class SyncRecordedLeaves extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AttendanceService $attendanceService): int
    {
        $leaves = LeaveRequest::query()
            ->with('user.internProfile')
            ->where('status', LeaveStatus::Approved->value)
            ->orderBy('start_date')
            ->get();

        foreach ($leaves as $leave) {
            $attendanceService->markLeave($leave);
        }

        $this->info("Sinkronisasi {$leaves->count()} pengajuan tercatat selesai.");

        return self::SUCCESS;
    }
}
