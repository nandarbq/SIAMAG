<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
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

        if ($attendance && in_array($attendance->status, [AttendanceStatus::Izin, AttendanceStatus::Sakit, AttendanceStatus::Libur], true)) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda tercatat '.$attendance->status->label().' pada hari ini sehingga tidak perlu absen.',
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

        $photoPath = $this->resolveSelfiePath($user, $data['photo_token'] ?? null);

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

        $photoPath = $this->resolveSelfiePath($user, $data['photo_token'] ?? null);

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
        $checkInAt = array_key_exists('check_in_at', $data)
            ? $data['check_in_at']
            : $attendance->check_in_at;

        $checkOutAt = array_key_exists('check_out_at', $data)
            ? $data['check_out_at']
            : $attendance->check_out_at;

        $lateMinutes = 0;

        if ($data['status'] === AttendanceStatus::Terlambat && $checkInAt) {
            $workStart = Carbon::parse($checkInAt->toDateString().' '.Setting::get('work_start', '07:30'));
            $threshold = $workStart->copy()->addMinutes(Setting::getInt('late_tolerance_min', 15));

            $lateMinutes = $checkInAt->greaterThan($threshold)
                ? (int) $threshold->diffInMinutes($checkInAt)
                : 0;
        }

        $attendance->update([
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'late_minutes' => $lateMinutes,
        ]);

        return $attendance->refresh();
    }

    public function markLeave(LeaveRequest $leave): void
    {
        $endDate = $leave->end_date?->copy() ?? now()->startOfDay();
        $profileEnd = $leave->user?->internProfile?->end_date;

        if ($profileEnd && $endDate->greaterThan($profileEnd)) {
            $endDate = $profileEnd->copy()->startOfDay();
        }

        if ($endDate->lessThan($leave->start_date)) {
            return;
        }

        $holidayDates = Holiday::query()
            ->whereBetween('date', [$leave->start_date, $endDate])
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        for ($date = $leave->start_date->copy(); $date->lessThanOrEqualTo($endDate); $date->addDay()) {
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

            if ($attendance) {
                $attendance->update([
                    'status' => $leave->type->value,
                    'note' => 'Pengajuan '.$leave->type->label().' tercatat.',
                ]);

                continue;
            }

            Attendance::query()->create([
                'user_id' => $leave->user_id,
                'date' => $date->toDateString(),
                'status' => $leave->type->value,
                'note' => 'Pengajuan '.$leave->type->label().' tercatat.',
            ]);
        }
    }

    public function closeSickLeave(LeaveRequest $leave, Carbon $returnDate): bool
    {
        $returnDate = $returnDate->copy()->startOfDay();

        if ($leave->type !== LeaveType::Sakit || ! $leave->isOngoing()) {
            return false;
        }

        if ($returnDate->lessThanOrEqualTo($leave->start_date)) {
            return false;
        }

        $leave->update([
            'end_date' => $returnDate->copy()->subDay()->toDateString(),
        ]);

        $this->clearSickAttendanceFrom($leave, $returnDate);
        $this->markLeave($leave);

        return true;
    }

    public function cancelLeave(LeaveRequest $leave): bool
    {
        $attendances = Attendance::query()
            ->where('user_id', $leave->user_id)
            ->whereDate('date', '>=', $leave->start_date)
            ->whereDate('date', '<=', $leave->end_date ?? today())
            ->where('status', $leave->type->value)
            ->whereIn('note', [
                'Pengajuan '.$leave->type->label().' tercatat.',
                'Pengajuan '.$leave->type->label().' disetujui.',
            ]);

        if ($attendances->clone()->whereNotNull('check_in_at')->exists()) {
            return false;
        }

        $attendances->delete();

        return true;
    }

    private function clearSickAttendanceFrom(LeaveRequest $leave, Carbon $returnDate): void
    {
        Attendance::query()
            ->where('user_id', $leave->user_id)
            ->whereDate('date', '>=', $returnDate->toDateString())
            ->where('status', AttendanceStatus::Sakit)
            ->whereNull('check_in_at')
            ->whereIn('note', [
                'Pengajuan Sakit tercatat.',
                'Pengajuan Sakit disetujui.',
            ])
            ->delete();
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

        $leave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', LeaveStatus::Approved->value)
            ->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->first();

        if ($leave) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda tercatat '.$leave->type->label().' pada hari ini sehingga tidak perlu absen.',
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

        if (! isset($data['accuracy']) || (int) $data['accuracy'] <= 0) {
            throw ValidationException::withMessages([
                'attendance' => 'Akurasi GPS tidak terdeteksi. Aktifkan izin lokasi lalu coba lagi.',
            ]);
        }

        $accuracy = (int) $data['accuracy'];
        $maxAccuracy = Setting::getInt('max_accuracy_m', 100);

        if ($accuracy > $maxAccuracy) {
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

    public function storeSelfie(User $user, string $photo): string
    {
        $binary = $this->decodeSelfie($user, $photo);

        try {
            $manager = ImageManager::usingDriver(Driver::class);
            $image = $manager->decodeBinary($binary);
            $image = $image->scaleDown(width: 900)->encodeUsingFileExtension('jpg', quality: 80);
        } catch (\Throwable $exception) {
            $this->logSelfieFailure($user, 'decode', $exception, strlen($photo));

            throw ValidationException::withMessages([
                'photo' => 'Foto selfie tidak valid. Ambil ulang foto.',
            ]);
        }

        $path = 'attendance/'.$user->id.'/'.Str::uuid().'.jpg';

        Storage::disk('local')->put($path, (string) $image);

        return Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'path' => $path,
        ], JSON_THROW_ON_ERROR));
    }

    private function decodeSelfie(User $user, string $photo): string
    {
        $comma = strpos($photo, ',');

        if (! str_starts_with($photo, 'data:image/') || $comma === false) {
            $this->logSelfieFailure($user, 'format', null, strlen($photo));

            throw ValidationException::withMessages([
                'photo' => 'Foto selfie tidak valid. Ambil ulang foto.',
            ]);
        }

        $metadata = substr($photo, 5, $comma - 5);
        $mimeType = strtolower(explode(';', $metadata)[0]);

        if (! str_contains($metadata, ';base64') || ! in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png'], true)) {
            $this->logSelfieFailure($user, 'mime', null, strlen($photo));

            throw ValidationException::withMessages([
                'photo' => 'Format foto selfie tidak didukung. Ambil ulang foto.',
            ]);
        }

        $binary = base64_decode(substr($photo, $comma + 1), true);

        if ($binary === false || $binary === '') {
            $this->logSelfieFailure($user, 'base64', null, strlen($photo));

            throw ValidationException::withMessages([
                'photo' => 'Foto selfie tidak valid. Ambil ulang foto.',
            ]);
        }

        $info = @getimagesizefromstring($binary);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            $this->logSelfieFailure($user, 'image', null, strlen($photo));

            throw ValidationException::withMessages([
                'photo' => 'Foto selfie tidak valid. Ambil ulang foto.',
            ]);
        }

        return $binary;
    }

    private function logSelfieFailure(User $user, string $stage, ?\Throwable $exception, int $payloadLength): void
    {
        Log::warning('Attendance selfie processing failed', [
            'user_id' => $user->id,
            'stage' => $stage,
            'exception' => $exception ? $exception::class : null,
            'message' => $exception?->getMessage(),
            'payload_length' => $payloadLength,
        ]);
    }

    public function resolveSelfiePath(User $user, ?string $token): ?string
    {
        if (! $token) {
            if (Setting::getBool('require_selfie', true)) {
                throw ValidationException::withMessages([
                    'photo' => 'Foto selfie wajib diambil untuk absensi.',
                ]);
            }

            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'photo' => 'Sesi foto selfie tidak valid. Ambil ulang foto.',
            ]);
        }

        $userId = (int) ($payload['user_id'] ?? 0);
        $path = $payload['path'] ?? null;

        if ($userId !== $user->id || ! is_string($path) || ! str_starts_with($path, 'attendance/'.$user->id.'/')) {
            throw ValidationException::withMessages([
                'photo' => 'Sesi foto selfie tidak valid. Ambil ulang foto.',
            ]);
        }

        if (! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages([
                'photo' => 'Foto selfie tidak ditemukan. Ambil ulang foto.',
            ]);
        }

        return $path;
    }

    private function valueOrNull(array $data, string $key): ?float
    {
        return isset($data[$key]) ? (float) $data[$key] : null;
    }
}
