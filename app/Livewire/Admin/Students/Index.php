<?php

namespace App\Livewire\Admin\Students;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Data Mahasiswa')]
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

    public function toggleActive(User $user): void
    {
        abort_unless($user->role === UserRole::Mahasiswa, 404);

        $user->update(['is_active' => ! $user->is_active]);

        session()->flash('success', $user->is_active
            ? "Akun {$user->name} berhasil diaktifkan."
            : "Akun {$user->name} berhasil dinonaktifkan.");
    }

    public function delete(User $user): void
    {
        abort_unless($user->role === UserRole::Mahasiswa, 404);

        $name = $user->name;
        $user->delete();

        session()->flash('success', "Data mahasiswa {$name} berhasil dihapus.");
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    public function render()
    {
        $students = User::query()
            ->with('internProfile')
            ->where('role', UserRole::Mahasiswa)
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('nim', 'like', $search));
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.students.index', compact('students'));
    }
}
