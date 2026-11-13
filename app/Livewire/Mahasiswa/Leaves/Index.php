<?php

namespace App\Livewire\Mahasiswa\Leaves;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
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

    public string $status = '';

    public bool $showModal = false;

    public bool $showRecoveryModal = false;

    public ?int $recoveryLeaveId = null;

    public string $recoveryDate = '';

    public string $type = LeaveType::Izin->value;

    public string $startDate = '';

    public string $reason = '';

    public $attachment;

    public function updating(string $property): void
    {
        if ($property === 'status') {
            $this->resetPage();
        }
    }

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

        $conflict = LeaveRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', [LeaveStatus::Pending->value, LeaveStatus::Approved->value])
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

    public function openRecovery(int $leaveId): void
    {
        $leave = auth()->user()->leaveRequests()
            ->where('type', LeaveType::Sakit->value)
            ->whereNull('end_date')
            ->find($leaveId);

        if (! $leave) {
            session()->flash('error', 'Pengajuan sakit yang sedang berlangsung tidak ditemukan.');

            return;
        }

        $this->resetValidation();
        $this->recoveryLeaveId = $leave->id;
        $this->recoveryDate = now()->toDateString();
        $this->showRecoveryModal = true;
    }

    public function closeRecoveryModal(): void
    {
        $this->showRecoveryModal = false;
        $this->recoveryLeaveId = null;
        $this->recoveryDate = '';
        $this->resetValidation();
    }

    public function markRecovered(AttendanceService $attendanceService): void
    {
        $leave = auth()->user()->leaveRequests()
            ->where('type', LeaveType::Sakit->value)
            ->whereNull('end_date')
            ->findOrFail($this->recoveryLeaveId);

        $profile = auth()->user()->internProfile;

        $this->validate([
            'recoveryDate' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($leave, $profile) {
                    $date = Carbon::parse($value)->startOfDay();

                    if ($date->lessThan($leave->start_date)) {
                        $fail('Tanggal sembuh tidak boleh sebelum tanggal mulai sakit.');

                        return;
                    }

                    if ($date->greaterThan(today())) {
                        $fail('Tanggal sembuh tidak boleh melewati hari ini.');

                        return;
                    }

                    if ($profile && $date->greaterThan($profile->end_date)) {
                        $fail('Tanggal sembuh harus berada di dalam periode magang.');
                    }
                },
            ],
        ], [
            'recoveryDate.required' => 'Tanggal sembuh wajib diisi.',
        ]);

        $attendanceService->closeSickLeave($leave, Carbon::parse($this->recoveryDate));

        $this->closeRecoveryModal();
        session()->flash('success', 'Masa sakit berhasil ditutup dan absensi telah disesuaikan.');
    }

    public function delete(int $leaveId, AttendanceService $attendanceService): void
    {
        $leave = auth()->user()->leaveRequests()->findOrFail($leaveId);

        if (! in_array($leave->status, [LeaveStatus::Approved, LeaveStatus::Pending], true)) {
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

    public function resetFilters(): void
    {
        $this->reset('status');
        $this->resetPage();
    }

    public function render()
    {
        $userId = auth()->id();

        $leaves = LeaveRequest::query()
            ->where('user_id', $userId)
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->latest('start_date')
            ->latest()
            ->paginate(8);

        $counts = LeaveRequest::query()
            ->where('user_id', $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $ongoingSick = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('type', LeaveType::Sakit->value)
            ->where('status', LeaveStatus::Approved->value)
            ->whereNull('end_date')
            ->count();

        return view('livewire.mahasiswa.leaves.index', [
            'leaves' => $leaves,
            'counts' => $counts,
            'statuses' => LeaveStatus::visibleCases(),
            'types' => LeaveType::cases(),
            'total' => $counts->sum(),
            'ongoingSick' => $ongoingSick,
            'profile' => auth()->user()->internProfile,
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['type', 'startDate', 'reason', 'attachment']);
        $this->type = LeaveType::Izin->value;
    }
}
