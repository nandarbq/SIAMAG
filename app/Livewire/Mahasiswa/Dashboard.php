<?php

namespace App\Livewire\Mahasiswa;

use App\Enums\AttendanceStatus;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\InternProfile;
use App\Models\Logbook;
use App\Models\Setting;
use App\Services\LogbookProgressService;
use App\Services\WorkdayService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard Mahasiswa')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $profile = $user->internProfile;
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthCounts = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $monthStart->toDateString())
            ->whereDate('date', '<=', $monthEnd->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $workdays = app(WorkdayService::class);
        $todayDate = now()->startOfDay();
        $isWorkday = $workdays->isWorkday($todayDate);
        $nonWorkingLabel = $workdays->nonWorkingLabel($todayDate);
        $logbookProgress = app(LogbookProgressService::class)->for($user);

        return view('livewire.mahasiswa.dashboard', [
            'profile' => $profile,
            'today' => $today,
            'isWorkday' => $isWorkday,
            'todayHoliday' => $workdays->holidayFor($todayDate),
            'nonWorkingLabel' => $nonWorkingLabel,
            'hadir' => (int) ($monthCounts[AttendanceStatus::Hadir->value] ?? 0),
            'terlambat' => (int) ($monthCounts[AttendanceStatus::Terlambat->value] ?? 0),
            'izinSakit' => (int) ($monthCounts[AttendanceStatus::Izin->value] ?? 0) + (int) ($monthCounts[AttendanceStatus::Sakit->value] ?? 0),
            'logbookProgress' => $logbookProgress,
            'todayFocus' => $this->buildTodayFocus($isWorkday, $today, $logbookProgress, $nonWorkingLabel, $profile),
            'recentLogbooks' => Logbook::query()
                ->where('user_id', $user->id)
                ->latest('date')
                ->limit(5)
                ->get(),
            'announcements' => Announcement::query()
                ->where('is_active', true)
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'week' => $this->buildWeek($user->id, $workdays),
            'officeName' => (string) Setting::get('office_name', 'BMKG Banyuwangi'),
            'greeting' => $this->greeting(),
            'workStart' => Setting::get('work_start', '07:30'),
            'workEnd' => Setting::get('work_end', '16:00'),
        ]);
    }

    private function greeting(): string
    {
        return match (true) {
            now()->hour < 11 => 'Selamat pagi',
            now()->hour < 15 => 'Selamat siang',
            now()->hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }

    /**
     * @param  array{expected: int, filled: int, missing: int, percentage: int|null, today_status: string}  $logbookProgress
     * @return array{title: string, body: string, cta_label: string|null, cta_url: string|null, items: array<int, array{label: string, value: string, hint: string, icon: string, tone: string}>}
     */
    private function buildTodayFocus(bool $isWorkday, ?Attendance $today, array $logbookProgress, ?string $nonWorkingLabel, ?InternProfile $profile): array
    {
        $todayStatus = $logbookProgress['today_status'];

        [$title, $body, $ctaLabel, $ctaUrl] = match (true) {
            $profile === null => [
                'Lengkapi profil magang Anda.',
                'Data institusi, jurusan, dan periode magang diperlukan sebelum absensi dimulai.',
                'Buka profil',
                route('mahasiswa.profile'),
            ],
            ! $isWorkday => [
                'Hari ini libur.',
                ($nonWorkingLabel ?? 'Hari ini libur').'. Tidak perlu absensi atau logbook.',
                null,
                null,
            ],
            $todayStatus === 'not_started' => [
                'Magang belum dimulai.',
                'Anda bisa mulai absensi dan logbook sesuai tanggal awal periode magang.',
                null,
                null,
            ],
            $todayStatus === 'outside' => [
                'Periode magang sudah berakhir.',
                'Absensi dan logbook tidak lagi diperlukan untuk periode ini.',
                null,
                null,
            ],
            $today === null => [
                'Absen masuk belum tercatat.',
                'Mulai hari dengan absen masuk agar kehadiran dan logbook tercatat rapi.',
                'Absen sekarang',
                route('mahasiswa.attendance'),
            ],
            $todayStatus === 'missing' => [
                'Logbook hari ini belum diisi.',
                'Luangkan waktu sebentar untuk mencatat aktivitas magang hari ini.',
                'Isi logbook',
                route('mahasiswa.logbooks'),
            ],
            $todayStatus === 'excused' => [
                'Izin atau sakit hari ini tercatat.',
                'Tidak perlu mengisi logbook untuk hari ini. Jaga kesehatan Anda.',
                null,
                null,
            ],
            default => [
                'Administrasi hari ini beres.',
                'Absensi dan logbook sudah lengkap. Lanjutkan aktivitas magang dengan tenang.',
                'Buka logbook',
                route('mahasiswa.logbooks'),
            ],
        };

        $attendanceValue = match (true) {
            ! $isWorkday => 'Libur',
            $today?->status === AttendanceStatus::Izin => 'Izin',
            $today?->status === AttendanceStatus::Sakit => 'Sakit',
            $today?->status === AttendanceStatus::Libur => 'Libur',
            $today?->status === AttendanceStatus::Alpha => 'Alpha',
            $today !== null => 'Sudah masuk',
            default => 'Belum absen',
        };
        $attendanceHint = match (true) {
            ! $isWorkday => 'Tidak perlu absen',
            $today?->status === AttendanceStatus::Izin => 'Izin tercatat',
            $today?->status === AttendanceStatus::Sakit => 'Sakit tercatat',
            $today?->status === AttendanceStatus::Libur => 'Tidak perlu absen',
            $today?->status === AttendanceStatus::Alpha => 'Tidak hadir',
            $today?->check_in_at !== null => 'Masuk '.$today->check_in_at->format('H:i'),
            default => 'Belum ada catatan',
        };
        $attendanceTone = match (true) {
            ! $isWorkday => 'bg-sky-400/15 text-sky-300 ring-sky-300/20',
            in_array($today?->status, [AttendanceStatus::Izin, AttendanceStatus::Libur], true) => 'bg-sky-400/15 text-sky-300 ring-sky-300/20',
            $today?->status === AttendanceStatus::Sakit => 'bg-violet-400/15 text-violet-300 ring-violet-300/20',
            $today?->status === AttendanceStatus::Alpha => 'bg-rose-400/15 text-rose-300 ring-rose-300/20',
            $today !== null => 'bg-emerald-400/15 text-emerald-300 ring-emerald-300/20',
            default => 'bg-amber-400/15 text-amber-300 ring-amber-300/20',
        };

        $logbookValue = match ($todayStatus) {
            'filled' => 'Sudah diisi',
            'missing' => 'Belum diisi',
            'holiday' => 'Libur',
            'excused' => 'Izin/Sakit',
            'not_attended' => 'Belum absen',
            default => 'Belum ada data',
        };
        $logbookHint = match ($todayStatus) {
            'filled' => $logbookProgress['filled'].' dari '.$logbookProgress['expected'].' hari',
            'missing' => 'Isi sebelum hari berganti',
            'holiday', 'excused' => 'Tidak perlu diisi',
            'not_attended' => 'Absen masuk dulu',
            default => 'Belum ada data',
        };
        $logbookTone = match ($todayStatus) {
            'filled' => 'bg-emerald-400/15 text-emerald-300 ring-emerald-300/20',
            'missing' => 'bg-rose-400/15 text-rose-300 ring-rose-300/20',
            'holiday', 'excused' => 'bg-sky-400/15 text-sky-300 ring-sky-300/20',
            default => 'bg-amber-400/15 text-amber-300 ring-amber-300/20',
        };

        return [
            'title' => $title,
            'body' => $body,
            'cta_label' => $ctaLabel,
            'cta_url' => $ctaUrl,
            'items' => [
                ['label' => 'Absensi', 'value' => $attendanceValue, 'hint' => $attendanceHint, 'icon' => 'camera', 'tone' => $attendanceTone],
                ['label' => 'Logbook', 'value' => $logbookValue, 'hint' => $logbookHint, 'icon' => 'book', 'tone' => $logbookTone],
                [
                    'label' => 'Progres',
                    'value' => $profile !== null ? $profile->progressPercent().'%' : '—',
                    'hint' => $profile !== null ? $profile->remainingDays().' hari tersisa' : 'Lengkapi profil',
                    'icon' => 'trending-up',
                    'tone' => 'bg-white/10 text-accent-300 ring-white/15',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{date: Carbon, day_label: string, day: string, isToday: bool, tone: string, icon: string, status_label: string}>
     */
    private function buildWeek(int $userId, WorkdayService $workdays): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->startOfDay();

        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->date->toDateString());

        $holidays = Holiday::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (Holiday $holiday) => $holiday->date->toDateString());

        $holidayDates = $holidays->keys()->all();
        $days = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $attendance = $attendances->get($date->toDateString());
            $isWorkday = $workdays->isWorkday($date, $holidayDates);

            [$tone, $icon, $statusLabel] = match (true) {
                $attendance?->status === AttendanceStatus::Hadir => ['bg-emerald-50 text-emerald-700 ring-emerald-600/10', 'check-circle', 'Hadir'],
                $attendance?->status === AttendanceStatus::Terlambat => ['bg-amber-50 text-amber-700 ring-amber-600/10', 'clock', 'Telat'],
                $attendance?->status === AttendanceStatus::Izin => ['bg-brand-50 text-brand-700 ring-brand-600/10', 'calendar', 'Izin'],
                $attendance?->status === AttendanceStatus::Sakit => ['bg-brand-50 text-brand-700 ring-brand-600/10', 'calendar', 'Sakit'],
                ! $isWorkday => ['bg-sky-50 text-sky-700 ring-sky-600/10', 'calendar', $date->isWeekend() ? 'Weekend' : 'Libur'],
                default => ['bg-slate-50 text-slate-400 ring-slate-500/10', 'clock', 'Belum'],
            };

            $days[] = [
                'date' => $date,
                'day_label' => $date->translatedFormat('D'),
                'day' => $date->format('d'),
                'isToday' => $date->isToday(),
                'tone' => $tone,
                'icon' => $icon,
                'status_label' => $statusLabel,
            ];
        }

        return $days;
    }
}
