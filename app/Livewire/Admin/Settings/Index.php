<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengaturan')]
class Index extends Component
{
    public string $office_name = '';

    public string $office_address = '';

    public string $office_lat = '';

    public string $office_lng = '';

    public string $radius_m = '';

    public string $max_accuracy_m = '';

    public string $work_start = '';

    public string $work_end = '';

    public string $late_tolerance_min = '';

    public bool $require_selfie = true;

    public bool $require_geofence = true;

    public function mount(): void
    {
        $this->office_name = (string) Setting::get('office_name', 'BMKG Banyuwangi');
        $this->office_address = (string) Setting::get('office_address', '');
        $this->office_lat = (string) Setting::get('office_lat', '-8.2192000');
        $this->office_lng = (string) Setting::get('office_lng', '114.3692000');
        $this->radius_m = (string) Setting::get('radius_m', '150');
        $this->max_accuracy_m = (string) Setting::get('max_accuracy_m', '100');
        $this->work_start = (string) Setting::get('work_start', '07:30');
        $this->work_end = (string) Setting::get('work_end', '16:00');
        $this->late_tolerance_min = (string) Setting::get('late_tolerance_min', '15');
        $this->require_selfie = Setting::getBool('require_selfie', true);
        $this->require_geofence = Setting::getBool('require_geofence', true);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'office_name' => ['required', 'string', 'max:255'],
            'office_address' => ['nullable', 'string', 'max:500'],
            'office_lat' => ['required', 'numeric', 'between:-90,90'],
            'office_lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_m' => ['required', 'integer', 'min:10', 'max:5000'],
            'max_accuracy_m' => ['required', 'integer', 'min:10', 'max:1000'],
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i', 'after:work_start'],
            'late_tolerance_min' => ['required', 'integer', 'min:0', 'max:120'],
        ], [
            'office_name.required' => 'Nama kantor wajib diisi.',
            'office_lat.required' => 'Latitude kantor wajib diisi.',
            'office_lat.between' => 'Latitude harus berada di antara -90 dan 90.',
            'office_lng.required' => 'Longitude kantor wajib diisi.',
            'office_lng.between' => 'Longitude harus berada di antara -180 dan 180.',
            'radius_m.min' => 'Radius minimal 10 meter.',
            'max_accuracy_m.min' => 'Akurasi minimal 10 meter.',
            'work_end.after' => 'Jam pulang harus setelah jam masuk.',
        ]);

        foreach ($validated as $key => $value) {
            Setting::put($key, $value);
        }

        Setting::put('require_selfie', $this->require_selfie ? '1' : '0');
        Setting::put('require_geofence', $this->require_geofence ? '1' : '0');

        session()->flash('success', 'Pengaturan berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.admin.settings.index');
    }
}
