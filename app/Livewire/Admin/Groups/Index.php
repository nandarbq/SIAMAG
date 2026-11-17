<?php

namespace App\Livewire\Admin\Groups;

use App\Models\InternGroup;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public int $perPage = 10;

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function delete(InternGroup $group): void
    {
        if ($group->students()->exists()) {
            $this->dispatch('group-delete-failed', message: "Kelompok {$group->code} masih memiliki anggota. Pindahkan anggota terlebih dahulu.");

            return;
        }

        $code = $group->code;
        $group->delete();

        $this->dispatch('group-deleted', message: "Kelompok {$code} berhasil dihapus.");
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    public function render()
    {
        $search = '%'.$this->search.'%';

        $groups = InternGroup::query()
            ->withCount('students')
            ->when($this->search !== '', function ($query) use ($search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search)
                    ->orWhere('institution', 'like', $search));
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.admin.groups.index', compact('groups'));
    }
}
