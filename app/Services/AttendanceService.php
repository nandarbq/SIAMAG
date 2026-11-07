<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class AttendanceService
{
    public function __construct(private readonly GeofenceService $geofence) {}

    public function checkIn(User $user, array $data): Attendance
    {
        $date = now()->startOfDay();

        $this->ensureCanAttend($user, $date);

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($attendance?->check_in_at) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda sudah melakukan absen masuk hari ini.',
            ]);
        }

        [$distance, $accuracy] = $this->validateLocation($data);

        $workStart = Carbon::parse($date->toDateString().' '.Setting::get('work_start', '07:30'));
        $tolerance = Setting::getInt('late_tolerance_min', 15);
        $threshold = $workStart->copy()->addMinutes($tolerance);
        $now = now();

        $lateMinutes = $now->greaterThan($threshold)
            ? (int) $threshold->diffInMinutes($now)
            : 0;

        $status = $lateMinutes > 0 ? AttendanceStatus::Terlambat : AttendanceStatus::Hadir;

        $photoPath = $this->storePhoto($user, $date, $data['photo'] ?? null, 'in');

        return Attendance::query()->updateOrCreate(
            ['user_id' => $user->id, 'date' => $date->toDateString()],
            [
                'check_in_at' => $now,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'check_in_photo' => $photoPath,
                'check_in_lat' => $this->valueOrNull($data, 'lat'),
                'check_in_lng' => $this->valueOrNull($data, 'lng'),
                'check_in_distance' => $distance,
                'check_in_accuracy' => $accuracy,
                'check_in_ip' => request()->ip(),
            ]
        );
    }

    public function checkOut(User $user, array $data): Attendance
    {
        $date = now()->startOfDay();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if (! $attendance?->check_in_at) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda belum melakukan absen masuk hari ini.',
            ]);
        }

        if ($attendance->check_out_at) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda sudah melakukan absen pulang hari ini.',
            ]);
        }

        [$distance, $accuracy] = $this->validateLocation($data);

        $photoPath = $this->storePhoto($user, $date, $data['photo'] ?? null, 'out');

        $attendance->update([
            'check_out_at' => now(),
            'check_out_photo' => $photoPath,
            'check_out_lat' => $this->valueOrNull($data, 'lat'),
            'check_out_lng' => $this->valueOrNull($data, 'lng'),
            'check_out_distance' => $distance,
            'check_out_accuracy' => $accuracy,
            'check_out_ip' => request()->ip(),
        ]);

        return $attendance->refresh();
    }

    public function correct(Attendance $attendance, array $data): Attendance
    {
        $attendance->update([
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'check_in_at' => $data['check_in_at'] ?: $attendance->check_in_at,
            'check_out_at' => $data['check_out_at'] ?: $attendance->check_out_at,
        ]);

        return $attendance->refresh();
    }

    public function markLeave(LeaveRequest $leave): void
    {
        $holidayDates = Holiday::query()
            ->whereBetween('date', [$leave->start_date, $leave->end_date])
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        for ($date = $leave->start_date->copy(); $date->lessThanOrEqualTo($leave->end_date); $date->addDay()) {
            if ($date->isWeekend() || in_array($date->toDateString(), $holidayDates, true)) {
                continue;
            }

            $attendance = Attendance::query()
                ->where('user_id', $leave->user_id)
                ->whereDate('date', $date)
                ->first();

            if ($attendance?->check_in_at) {
                continue;
            }

            Attendance::query()->updateOrCreate(
                ['user_id' => $leave->user_id, 'date' => $date->toDateString()],
                [
                    'status' => $leave->type->value,
                    'note' => 'Pengajuan '.$leave->type->label().' disetujui.',
                ]
            );
        }
    }

    public function todayFor(User $user): ?Attendance
    {
        return Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();
    }

    public function isWorkday(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        return ! Holiday::query()->whereDate('date', $date->toDateString())->exists();
    }

    private function ensureCanAttend(User $user, Carbon $date): void
    {
        $profile = $user->internProfile;

        if (! $profile) {
            throw ValidationException::withMessages([
                'attendance' => 'Profil magang belum diisi. Hubungi admin untuk melengkapi data.',
            ]);
        }

        if ($date->lessThan($profile->start_date) || $date->greaterThan($profile->end_date)) {
            throw ValidationException::withMessages([
                'attendance' => 'Tanggal hari ini berada di luar periode magang Anda.',
            ]);
        }

        if (! $this->isWorkday($date)) {
            throw ValidationException::withMessages([
                'attendance' => 'Hari ini bukan hari kerja atau merupakan hari libur.',
            ]);
        }
    }

    private function validateLocation(array $data): array
    {
        $requireGeofence = Setting::getBool('require_geofence', true);

        if (! $requireGeofence) {
            return [null, null];
        }

        if (! isset($data['lat'], $data['lng'])) {
            throw ValidationException::withMessages([
                'attendance' => 'Lokasi GPS tidak terdeteksi. Aktifkan izin lokasi lalu coba lagi.',
            ]);
        }

        $accuracy = isset($data['accuracy']) ? (int) $data['accuracy'] : null;
        $maxAccuracy = Setting::getInt('max_accuracy_m', 100);

        if ($accuracy !== null && $accuracy > $maxAccuracy) {
            throw ValidationException::withMessages([
                'attendance' => "Akurasi GPS terlalu rendah ({$accuracy} m). Maksimal {$maxAccuracy} m.",
            ]);
        }

        $distance = $this->geofence->distanceInMeters(
            (float) $data['lat'],
            (float) $data['lng'],
            (float) Setting::get('office_lat', '-8.2192000'),
            (float) Setting::get('office_lng', '114.3692000'),
        );

        if ($distance > Setting::getInt('radius_m', 150)) {
            throw ValidationException::withMessages([
                'attendance' => "Anda berada {$distance} m dari kantor. Absensi hanya bisa dilakukan di dalam radius "
                    .Setting::getInt('radius_m', 150).' m.',
            ]);
        }

        return [$distance, $accuracy];
    }

    private function storePhoto(User $user, Carbon $date, ?UploadedFile $photo, string $type): ?string
    {
        $requireSelfie = Setting::getBool('require_selfie', true);

        if (! $photo) {
            if ($requireSelfie) {
                throw ValidationException::withMessages([
                    'photo' => 'Foto selfie wajib diambil untuk absensi.',
                ]);
            }

            return null;
        }

        $path = 'attendance/'.$user->id.'/'.$date->format('Y-m-d').'-'.$type.'.jpg';

        try {
            $image = ImageManager::usingDriver('gd')
                ->decodePath($photo->getRealPath())
                ->scaleDown(width: 900)
                ->encodeUsingFileExtension('jpg', quality: 80);

            Storage::disk('local')->put($path, (string) $image);
        } catch (\Throwable) {
            $path = $photo->store('attendance/'.$user->id, 'local');
        }

        return $path;
    }

    private function valueOrNull(array $data, string $key): ?float
    {
        return isset($data[$key]) ? (float) $data[$key] : null;
    }
}
