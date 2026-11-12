<?php

namespace App\Livewire\Admin\Logbooks;

use App\Models\Logbook;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Monitoring Logbook')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 12;

    public ?int $viewingId = null;

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function openDetail(int $logbookId): void
    {
        $this->viewingId = Logbook::query()->findOrFail($logbookId)->id;
    }

    public function closeDetail(): void
    {
        $this->reset('viewingId');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $logbooks = Logbook::query()
            ->with('user')
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('activity', 'like', $search)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $search)->orWhere('nim', 'like', $search)));
            })
            ->latest('date')
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.logbooks.index', [
            'logbooks' => $logbooks,
            'viewing' => $this->viewingId ? Logbook::query()->with('user')->find($this->viewingId) : null,
        ]);
    }
}
