<?php

namespace App\Livewire\Mahasiswa\Attendance;

use App\Models\Setting;
use App\Services\AttendanceService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Absensi')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $photo;

    public ?float $lat = null;

    public ?float $lng = null;

    public ?int $accuracy = null;

    public function checkIn(AttendanceService $attendanceService): void
    {
        $this->validate([
            'photo' => ['nullable', 'image', 'max:5120'],
        ], [
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        $attendanceService->checkIn(auth()->user(), [
            'lat' => $this->lat,
            'lng' => $this->lng,
            'accuracy' => $this->accuracy,
            'photo' => $this->photo,
        ]);

        $this->reset('photo');
        session()->flash('success', 'Absen masuk berhasil dicatat.');
    }

    public function checkOut(AttendanceService $attendanceService): void
    {
        $this->validate([
            'photo' => ['nullable', 'image', 'max:5120'],
        ], [
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        $attendanceService->checkOut(auth()->user(), [
            'lat' => $this->lat,
            'lng' => $this->lng,
            'accuracy' => $this->accuracy,
            'photo' => $this->photo,
        ]);

        $this->reset('photo');
        session()->flash('success', 'Absen pulang berhasil dicatat.');
    }

    public function render()
    {
        $user = auth()->user();
        $today = app(AttendanceService::class)->todayFor($user);
        $requireSelfie = Setting::getBool('require_selfie', true);

        return view('livewire.mahasiswa.attendance.index', [
            'today' => $today,
            'profile' => $user->internProfile,
            'history' => $user->attendances()->latest('date')->paginate(10),
            'officeLat' => (float) Setting::get('office_lat', '-8.2192000'),
            'officeLng' => (float) Setting::get('office_lng', '114.3692000'),
            'radius' => Setting::getInt('radius_m', 150),
            'maxAccuracy' => Setting::getInt('max_accuracy_m', 100),
            'requireSelfie' => $requireSelfie,
            'requireGeofence' => Setting::getBool('require_geofence', true),
            'photoReady' => ! $requireSelfie || $this->photo !== null,
        ]);
    }
}
