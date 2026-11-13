<?php

namespace App\Livewire\Admin\Leaves;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Izin & Sakit')]
class Index extends Component
{
    use WithPagination;

    public string $status = LeaveStatus::Approved->value;

    public string $type = '';

    public string $search = '';

    public int $perPage = 12;

    public ?int $reviewingId = null;

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
    }

    public function closeReview(): void
    {
        $this->reset('reviewingId');
        $this->resetValidation();
    }

    public function resetFilters(): void
    {
        $this->reset(['type', 'search']);
        $this->status = LeaveStatus::Approved->value;
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
            'statuses' => LeaveStatus::visibleCases(),
            'types' => LeaveType::cases(),
            'reviewing' => $this->reviewingId ? LeaveRequest::query()->with('user')->find($this->reviewingId) : null,
        ]);
    }
}
