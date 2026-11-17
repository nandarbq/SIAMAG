<?php

namespace App\Livewire\Mahasiswa\Leaves;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Izin & Sakit')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showModal = false;

    public string $type = LeaveType::Izin->value;

    public string $startDate = '';

    public string $reason = '';

    public $attachment;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->resetValidation();

        $profile = auth()->user()->internProfile;

        if ($profile && ! today()->betweenIncluded($profile->start_date, $profile->end_date)) {
            session()->flash('error', 'Pengajuan izin hanya dapat dilakukan selama periode magang.');

            return;
        }

        $this->startDate = now()->toDateString();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function submit(AttendanceService $attendanceService): void
    {
        $userId = auth()->id();
        $profile = auth()->user()->internProfile;

        $this->validate([
            'type' => ['required', 'in:izin,sakit'],
            'startDate' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($profile) {
                    if (! $profile) {
                        return;
                    }

                    $date = Carbon::parse($value);

                    if ($date->lessThan($profile->start_date) || $date->greaterThan($profile->end_date)) {
                        $fail('Tanggal harus berada di dalam periode magang.');
                    }
                },
            ],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'type.required' => 'Pilih jenis pengajuan.',
            'type.in' => 'Jenis pengajuan tidak valid.',
            'startDate.required' => 'Tanggal wajib diisi.',
            'reason.required' => 'Alasan pengajuan wajib diisi.',
            'reason.min' => 'Alasan minimal 10 karakter.',
            'attachment.mimes' => 'Lampiran harus berupa JPG, PNG, atau PDF.',
            'attachment.max' => 'Ukuran lampiran maksimal 4 MB.',
        ]);

        $type = LeaveType::from($this->type);
        $endDate = $type === LeaveType::Sakit ? null : $this->startDate;
        $attendanceEnd = $endDate ?: today()->toDateString();

        $hasCheckedIn = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $this->startDate)
            ->whereDate('date', '<=', $attendanceEnd)
            ->whereNotNull('check_in_at')
            ->exists();

        if ($hasCheckedIn) {
            $this->addError('startDate', 'Tidak dapat mengajukan izin/sakit karena sudah ada absen masuk pada tanggal tersebut.');

            return;
        }

        $conflict = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', LeaveStatus::Approved->value)
            ->whereDate('start_date', '<=', $endDate ?? '9999-12-31')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $this->startDate);
            })
            ->exists();

        if ($conflict) {
            $this->addError('startDate', 'Terdapat pengajuan lain yang beririsan dengan tanggal tersebut.');

            return;
        }

        $attachmentPath = $this->attachment
            ? $this->attachment->store('leave-attachments/'.$userId, 'local')
            : null;

        $leave = LeaveRequest::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'start_date' => $this->startDate,
            'end_date' => $endDate,
            'reason' => $this->reason,
            'attachment_path' => $attachmentPath,
            'status' => LeaveStatus::Approved,
        ]);

        $attendanceService->markLeave($leave);

        $this->closeModal();
        session()->flash('success', 'Pengajuan '.$type->label().' berhasil dicatat.');
    }

    public function delete(int $leaveId, AttendanceService $attendanceService): void
    {
        $leave = auth()->user()->leaveRequests()->findOrFail($leaveId);

        if ($leave->status !== LeaveStatus::Approved) {
            session()->flash('error', 'Pengajuan ini tidak dapat dibatalkan.');

            return;
        }

        if ($leave->type === LeaveType::Izin && $leave->start_date->lessThan(today())) {
            session()->flash('error', 'Izin yang sudah lewat tidak dapat dibatalkan.');

            return;
        }

        if ($leave->type === LeaveType::Sakit && ! $leave->isOngoing()) {
            session()->flash('error', 'Sakit yang sudah ditutup tidak dapat dibatalkan.');

            return;
        }

        if (! $attendanceService->cancelLeave($leave)) {
            session()->flash('error', 'Pengajuan tidak dapat dibatalkan karena sudah ada absensi masuk.');

            return;
        }

        if ($leave->attachment_path) {
            Storage::disk('local')->delete($leave->attachment_path);
        }

        $leave->delete();
        session()->flash('success', 'Pengajuan berhasil dibatalkan.');
    }

    public function render()
    {
        $userId = auth()->id();

        $leaves = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', LeaveStatus::Approved->value)
            ->latest('start_date')
            ->latest()
            ->paginate(8);

        return view('livewire.mahasiswa.leaves.index', [
            'leaves' => $leaves,
            'types' => LeaveType::cases(),
            'profile' => auth()->user()->internProfile,
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['type', 'startDate', 'reason', 'attachment']);
        $this->type = LeaveType::Izin->value;
    }
}
