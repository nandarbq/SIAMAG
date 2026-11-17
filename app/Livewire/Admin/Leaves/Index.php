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

    public string $type = '';

    public string $search = '';

    public int $perPage = 12;

    public ?int $reviewingId = null;

    public function updating(string $property): void
    {
        if (in_array($property, ['type', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function updatedSearch(mixed $value): void
    {
        $this->type = '';
    }

    public function openReview(int $leaveId): void
    {
        $leave = LeaveRequest::query()
            ->where('status', LeaveStatus::Approved->value)
            ->findOrFail($leaveId);

        $this->reviewingId = $leave->id;
    }

    public function closeReview(): void
    {
        $this->reset('reviewingId');
        $this->resetValidation();
    }

    public function render()
    {
        $leaves = LeaveRequest::query()
            ->with('user')
            ->where('status', LeaveStatus::Approved->value)
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('reason', 'like', $search)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $search)->orWhere('nim', 'like', $search)));
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.leaves.index', [
            'leaves' => $leaves,
            'types' => LeaveType::cases(),
            'reviewing' => $this->reviewingId ? LeaveRequest::query()->with('user')->find($this->reviewingId) : null,
        ]);
    }
}
