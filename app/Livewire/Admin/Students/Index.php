<?php

namespace App\Livewire\Admin\Students;

use App\Enums\UserRole;
use App\Models\InternGroup;
use App\Models\User;
use App\Services\StudentFileCleaner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Data Mahasiswa')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'students';

    public string $groupView = 'list';

    public ?int $groupId = null;

    public string $groupNotice = '';

    public string $groupNoticeType = 'success';

    public string $search = '';

    public string $status = '';

    public int $perPage = 10;

    public function mount(): void
    {
        $groupId = request()->integer('group');

        if ($groupId > 0 && InternGroup::query()->whereKey($groupId)->exists()) {
            $this->tab = 'groups';
            $this->groupView = request()->boolean('edit') ? 'form' : 'show';
            $this->groupId = $groupId;

            return;
        }

        if (request()->boolean('new')) {
            $this->tab = 'groups';
            $this->groupView = 'form';
        }
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['tab', 'search', 'status', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function updatedSearch(mixed $value): void
    {
        $this->status = '';
    }

    #[On('create-group')]
    public function createGroup(): void
    {
        $this->tab = 'groups';
        $this->groupView = 'form';
        $this->groupId = null;
        $this->clearGroupNotice();
    }

    #[On('edit-group')]
    public function editGroup(int $groupId): void
    {
        $this->openGroup('form', $groupId);
    }

    #[On('show-group')]
    public function showGroup(int $groupId): void
    {
        $this->openGroup('show', $groupId);
    }

    #[On('group-saved')]
    public function groupSaved(string $message): void
    {
        $this->groupView = 'list';
        $this->groupId = null;
        $this->groupNotice = $message;
        $this->groupNoticeType = 'success';
    }

    #[On('group-deleted')]
    public function groupDeleted(string $message): void
    {
        $this->groupView = 'list';
        $this->groupId = null;
        $this->groupNotice = $message;
        $this->groupNoticeType = 'success';
    }

    #[On('group-delete-failed')]
    public function groupDeleteFailed(string $message): void
    {
        $this->groupNotice = $message;
        $this->groupNoticeType = 'error';
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab === 'groups' ? 'groups' : 'students';
        $this->groupView = 'list';
        $this->groupId = null;
        $this->clearGroupNotice();
    }

    #[On('back-to-groups')]
    public function backToGroupList(): void
    {
        $this->groupView = 'list';
        $this->groupId = null;
        $this->clearGroupNotice();
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
        app(StudentFileCleaner::class)->deleteFor($user);
        $user->delete();

        session()->flash('success', "Data mahasiswa {$name} berhasil dihapus.");
    }

    public function render()
    {
        $search = '%'.$this->search.'%';

        $students = User::query()
            ->with('internProfile.group')
            ->where('role', UserRole::Mahasiswa)
            ->when($this->search !== '', function ($query) use ($search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('nim', 'like', $search));
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $groups = InternGroup::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $group = $this->groupId
            ? InternGroup::query()->find($this->groupId)
            : null;

        return view('livewire.admin.students.index', compact('students', 'groups', 'group'));
    }

    private function openGroup(string $view, int $groupId): void
    {
        abort_unless(InternGroup::query()->whereKey($groupId)->exists(), 404);

        $this->tab = 'groups';
        $this->groupView = $view;
        $this->groupId = $groupId;
        $this->clearGroupNotice();
    }

    private function clearGroupNotice(): void
    {
        $this->groupNotice = '';
        $this->groupNoticeType = 'success';
    }
}
