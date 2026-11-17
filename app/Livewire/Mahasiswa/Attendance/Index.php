<?php

namespace App\Livewire\Mahasiswa\Attendance;

use App\Models\Setting;
use App\Services\AttendanceService;
use App\Services\WorkdayService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Absensi')]
class Index extends Component
{
    use WithPagination;

    public ?string $photoToken = null;

    public ?float $lat = null;

    public ?float $lng = null;

    public ?int $accuracy = null;

    public function checkIn(AttendanceService $attendanceService): void
    {
        $this->validatePhoto();

        $attendanceService->checkIn(auth()->user(), [
            'lat' => $this->lat,
            'lng' => $this->lng,
            'accuracy' => $this->accuracy,
            'photo_token' => $this->photoToken,
        ]);

        $this->reset('photoToken');
        session()->flash('success', 'Absen masuk berhasil dicatat.');
    }

    public function checkOut(AttendanceService $attendanceService): void
    {
        $this->validatePhoto();

        $attendanceService->checkOut(auth()->user(), [
            'lat' => $this->lat,
            'lng' => $this->lng,
            'accuracy' => $this->accuracy,
            'photo_token' => $this->photoToken,
        ]);

        $this->reset('photoToken');
        session()->flash('success', 'Absen pulang berhasil dicatat.');
    }

    public function requestRecovery(AttendanceService $attendanceService): void
    {
        if (! $attendanceService->requestSickRecovery(auth()->user())) {
            session()->flash('error', 'Masa sakit tidak dapat dikonfirmasi. Pastikan pengajuan sakit masih berlangsung, lalu coba lagi.');

            return;
        }

        $this->reset('photoToken');
        session()->flash('success', 'Kesembuhan dicatat. Silakan absen masuk untuk menutup masa sakit.');
    }

    private function validatePhoto(): void
    {
        $this->validate([
            'photoToken' => ['nullable', 'string', 'max:2048'],
        ], [
            'photoToken.string' => 'Sesi foto selfie tidak valid.',
            'photoToken.max' => 'Sesi foto selfie tidak valid.',
        ]);
    }

    public function render()
    {
        $user = auth()->user();
        $attendanceService = app(AttendanceService::class);
        $today = $attendanceService->todayFor($user);
        $ongoingSickLeave = $attendanceService->ongoingSickLeaveFor($user);
        $recoveryRequestedToday = $ongoingSickLeave?->hasRecoveryRequestToday() ?? false;
        $requireSelfie = Setting::getBool('require_selfie', true);
        $workdays = app(WorkdayService::class);
        $todayDate = now()->startOfDay();

        return view('livewire.mahasiswa.attendance.index', [
            'today' => $today,
            'isWorkday' => $workdays->isWorkday($todayDate),
            'todayHoliday' => $workdays->holidayFor($todayDate),
            'nonWorkingLabel' => $workdays->nonWorkingLabel($todayDate),
            'ongoingSickLeave' => $ongoingSickLeave,
            'recoveryRequestedToday' => $recoveryRequestedToday,
            'needsSickRecoveryPrompt' => $ongoingSickLeave !== null && ! $recoveryRequestedToday,
            'profile' => $user->internProfile,
            'history' => $user->attendances()->latest('date')->paginate(10),
            'officeLat' => (float) Setting::get('office_lat', '-8.2192000'),
            'officeLng' => (float) Setting::get('office_lng', '114.3692000'),
            'radius' => Setting::getInt('radius_m', 150),
            'maxAccuracy' => Setting::getInt('max_accuracy_m', 100),
            'requireSelfie' => $requireSelfie,
            'requireGeofence' => Setting::getBool('require_geofence', true),
            'photoReady' => ! $requireSelfie || filled($this->photoToken),
        ]);
    }
}
