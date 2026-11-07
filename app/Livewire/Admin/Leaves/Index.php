<?php

namespace App\Livewire\Admin\Leaves;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use App\Notifications\WorkflowNotification;
use App\Services\AttendanceService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Izin & Sakit')]
class Index extends Component
{
    use WithPagination;

    public string $status = LeaveStatus::Pending->value;

    public string $type = '';

    public string $search = '';

    public int $perPage = 12;

    public ?int $reviewingId = null;

    public string $reviewNote = '';

    public function updating(string $property): void
    {
        if (in_array($property, ['status', 'type', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function openReview(int $leaveId): void
    {
        $leave = LeaveRequest::query()->findOrFail($leaveId);

        $this->reviewingId = $leave->id;
        $this->reviewNote = $leave->review_note ?? '';
    }

    public function closeReview(): void
    {
        $this->reset(['reviewingId', 'reviewNote']);
        $this->resetValidation();
    }

    public function approve(int $leaveId, AttendanceService $attendanceService): void
    {
        $leave = LeaveRequest::query()->with('user')->findOrFail($leaveId);

        if ($leave->status !== LeaveStatus::Pending) {
            $this->closeReview();
            session()->flash('error', 'Pengajuan ini sudah diproses.');

            return;
        }

        $leave->update([
            'status' => LeaveStatus::Approved,
            'review_note' => $this->reviewNote ?: 'Pengajuan disetujui.',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $attendanceService->markLeave($leave);

        $leave->user?->notify(new WorkflowNotification(
            'Pengajuan '.$leave->type->label().' disetujui',
            'Pengajuan '.$leave->type->label().' Anda untuk periode '
                .$leave->start_date->translatedFormat('d M').' — '.$leave->end_date->translatedFormat('d M Y').' telah disetujui.',
            route('mahasiswa.leaves'),
            'calendar',
        ));

        $this->closeReview();
        session()->flash('success', 'Pengajuan berhasil disetujui dan absensi telah diperbarui.');
    }

    public function reject(int $leaveId): void
    {
        $this->validate([
            'reviewNote' => ['required', 'string', 'max:500'],
        ], [
            'reviewNote.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $leave = LeaveRequest::query()->with('user')->findOrFail($leaveId);

        if ($leave->status !== LeaveStatus::Pending) {
            $this->closeReview();
            session()->flash('error', 'Pengajuan ini sudah diproses.');

            return;
        }

        $leave->update([
            'status' => LeaveStatus::Rejected,
            'review_note' => $this->reviewNote,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $leave->user?->notify(new WorkflowNotification(
            'Pengajuan '.$leave->type->label().' ditolak',
            'Pengajuan Anda ditolak: '.$this->reviewNote,
            route('mahasiswa.leaves'),
            'calendar',
        ));

        $this->closeReview();
        session()->flash('success', 'Pengajuan ditolak dengan catatan.');
    }

    public function resetFilters(): void
    {
        $this->reset(['type', 'search']);
        $this->status = LeaveStatus::Pending->value;
        $this->resetPage();
    }

    public function render()
    {
        $leaves = LeaveRequest::query()
            ->with(['user', 'reviewer'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('reason', 'like', $search)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $search)->orWhere('nim', 'like', $search)));
            })
            ->latest()
            ->paginate($this->perPage);

        $counts = LeaveRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.leaves.index', [
            'leaves' => $leaves,
            'counts' => $counts,
            'statuses' => LeaveStatus::cases(),
            'types' => LeaveType::cases(),
            'reviewing' => $this->reviewingId ? LeaveRequest::query()->with('user')->find($this->reviewingId) : null,
        ]);
    }
}
