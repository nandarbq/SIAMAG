<?php

namespace App\Livewire\Admin\Students;

use App\Enums\AttendanceStatus;
use App\Enums\LogbookStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\StudentFileCleaner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Mahasiswa')]
class Show extends Component
{
    public User $student;

    public function mount(User $student): void
    {
        abort_unless($student->role === UserRole::Mahasiswa, 404);

        $this->student = $student->load('internProfile');
    }

    public function toggleActive(): void
    {
        $this->student->update(['is_active' => ! $this->student->is_active]);

        session()->flash('success', $this->student->is_active
            ? 'Akun mahasiswa berhasil diaktifkan.'
            : 'Akun mahasiswa berhasil dinonaktifkan.');
    }

    public function delete()
    {
        $name = $this->student->name;
        app(StudentFileCleaner::class)->deleteFor($this->student);
        $this->student->delete();

        session()->flash('success', "Data mahasiswa {$name} berhasil dihapus.");

        return redirect()->route('admin.students');
    }

    public function render()
    {
        $attendanceCounts = $this->student->attendances()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.students.show', [
            'profile' => $this->student->internProfile,
            'attendanceCounts' => $attendanceCounts,
            'hadir' => (int) ($attendanceCounts[AttendanceStatus::Hadir->value] ?? 0),
            'terlambat' => (int) ($attendanceCounts[AttendanceStatus::Terlambat->value] ?? 0),
            'izinSakit' => (int) ($attendanceCounts[AttendanceStatus::Izin->value] ?? 0) + (int) ($attendanceCounts[AttendanceStatus::Sakit->value] ?? 0),
            'logbookApproved' => $this->student->logbooks()->where('status', LogbookStatus::Approved)->count(),
            'attendances' => $this->student->attendances()->latest('date')->limit(10)->get(),
            'logbooks' => $this->student->logbooks()->latest('date')->limit(8)->get(),
            'leaves' => $this->student->leaveRequests()->latest()->limit(5)->get(),
        ]);
    }
}
