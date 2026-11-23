<?php

namespace App\Livewire\Admin\Groups;

use App\Models\InternGroup;
use Livewire\Component;

class Show extends Component
{
    public InternGroup $group;

    public bool $embedded = false;

    public function mount(InternGroup $group, bool $embedded = false): void
    {
        $this->group = $group;
        $this->embedded = $embedded;
    }

    public function delete()
    {
        if ($this->group->students()->exists()) {
            $this->dispatch('group-delete-failed', message: "Kelompok {$this->group->code} masih memiliki anggota. Pindahkan anggota terlebih dahulu.");

            return;
        }

        $code = $this->group->code;
        $this->group->delete();

        $message = "Kelompok {$code} berhasil dihapus.";

        if ($this->embedded) {
            $this->dispatch('group-deleted', message: $message);

            return;
        }

        session()->flash('success', $message);

        return redirect()->route('admin.groups');
    }

    public function render()
    {
        return view('livewire.admin.groups.show', [
            'students' => $this->group->students()
                ->with('internProfile')
                ->orderBy('name')
                ->get(),
            'hasMember' => $this->group->internProfiles()->exists(),
        ]);
    }
}
