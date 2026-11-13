<?php

namespace App\Console\Commands;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attendance:sync-ongoing-sick-leaves')]
#[Description('Catat absensi sakit berlangsung sampai hari ini.')]
class SyncOngoingSickLeaves extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AttendanceService $attendanceService): int
    {
        $leaves = LeaveRequest::query()
            ->with('user.internProfile')
            ->where('type', LeaveType::Sakit->value)
            ->where('status', LeaveStatus::Approved->value)
            ->whereNull('end_date')
            ->whereDate('start_date', '<=', today())
            ->get();

        foreach ($leaves as $leave) {
            $attendanceService->markLeave($leave);
        }

        $this->info("Sinkronisasi {$leaves->count()} pengajuan sakit berlangsung selesai.");

        return self::SUCCESS;
    }
}
