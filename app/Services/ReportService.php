<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\LogbookStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Logbook;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function recap(int $month, int $year, ?int $studentId = null): array
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $holidayDates = Holiday::query()
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $students = User::query()
            ->with('internProfile')
            ->where('role', UserRole::Mahasiswa)
            ->when($studentId, fn ($q) => $q->whereKey($studentId))
            ->orderBy('name')
            ->get();

        $rows = $students->map(function (User $student) use ($periodStart, $periodEnd, $holidayDates) {
            $counts = Attendance::query()
                ->where('user_id', $student->id)
                ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $hadir = (int) ($counts[AttendanceStatus::Hadir->value] ?? 0);
            $terlambat = (int) ($counts[AttendanceStatus::Terlambat->value] ?? 0);
            $izin = (int) ($counts[AttendanceStatus::Izin->value] ?? 0);
            $sakit = (int) ($counts[AttendanceStatus::Sakit->value] ?? 0);

            $workdays = $this->workdaysFor($student, $periodStart, $periodEnd, $holidayDates);
            $recorded = $hadir + $terlambat + $izin + $sakit;
            $alpha = max(0, $workdays - $recorded);

            $logbookApproved = Logbook::query()
                ->where('user_id', $student->id)
                ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->where('status', LogbookStatus::Approved)
                ->count();

            $logbookTotal = Logbook::query()
                ->where('user_id', $student->id)
                ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->count();

            return [
                'id' => $student->id,
                'name' => $student->name,
                'nim' => $student->nim ?? '-',
                'institution' => $student->internProfile?->institution ?? '-',
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'workdays' => $workdays,
                'logbook_approved' => $logbookApproved,
                'logbook_total' => $logbookTotal,
                'percentage' => $workdays > 0 ? (int) round((($hadir + $terlambat) / $workdays) * 100) : 0,
            ];
        })->values();

        $summary = [
            'students' => $rows->count(),
            'hadir' => $rows->sum('hadir'),
            'terlambat' => $rows->sum('terlambat'),
            'izin' => $rows->sum('izin'),
            'sakit' => $rows->sum('sakit'),
            'alpha' => $rows->sum('alpha'),
            'logbook_approved' => $rows->sum('logbook_approved'),
        ];

        return [
            'rows' => $rows,
            'summary' => $summary,
            'month' => $month,
            'year' => $year,
            'month_name' => $periodStart->translatedFormat('F'),
            'period_label' => $periodStart->translatedFormat('F Y'),
            'office_name' => Setting::get('office_name', 'BMKG Banyuwangi'),
            'office_address' => Setting::get('office_address', ''),
            'generated_at' => now(),
        ];
    }

    private function workdaysFor(User $student, Carbon $periodStart, Carbon $periodEnd, array $holidayDates): int
    {
        $profile = $student->internProfile;

        $start = $periodStart->copy();
        $end = $periodEnd->copy();

        if ($profile?->start_date && $profile->start_date->greaterThan($start)) {
            $start = $profile->start_date->copy()->startOfDay();
        }

        if ($profile?->end_date && $profile->end_date->lessThan($end)) {
            $end = $profile->end_date->copy()->endOfDay();
        }

        if ($end->isFuture()) {
            $end = now()->startOfDay();
        }

        if ($start->greaterThan($end)) {
            return 0;
        }

        $count = 0;

        for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            if ($date->isWeekend() || in_array($date->toDateString(), $holidayDates, true)) {
                continue;
            }

            $count++;
        }

        return $count;
    }
}
