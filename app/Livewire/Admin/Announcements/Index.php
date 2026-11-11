<?php

namespace App\Livewire\Admin\Announcements;

use App\Models\Announcement;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengumuman')]
class Index extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $body = '';

    public bool $is_active = true;

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'body']);
        $this->is_active = true;
        $this->showForm = true;
    }

    public function openEdit(int $announcementId): void
    {
        $announcement = Announcement::query()->findOrFail($announcementId);

        $this->editingId = $announcement->id;
        $this->title = $announcement->title;
        $this->body = $announcement->body;
        $this->is_active = (bool) $announcement->is_active;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->reset(['showForm', 'editingId', 'title', 'body']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ], [
            'title.required' => 'Judul pengumuman wajib diisi.',
            'body.required' => 'Isi pengumuman wajib diisi.',
        ]);

        $data = [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Announcement::query()->findOrFail($this->editingId)->update($data);
        } else {
            Announcement::query()->create($data + [
                'published_at' => now(),
                'created_by' => auth()->id(),
            ]);
        }

        $this->closeForm();
        session()->flash('success', 'Pengumuman berhasil disimpan.');
    }

    public function toggleActive(int $announcementId): void
    {
        $announcement = Announcement::query()->findOrFail($announcementId);
        $announcement->update(['is_active' => ! $announcement->is_active]);

        session()->flash('success', 'Status pengumuman berhasil diperbarui.');
    }

    public function delete(int $announcementId): void
    {
        Announcement::query()->findOrFail($announcementId)->delete();

        session()->flash('success', 'Pengumuman berhasil dihapus.');
    }

    public function render()
    {
        $announcements = Announcement::query()
            ->when($this->search !== '', fn ($q) => $q
                ->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('body', 'like', '%'.$this->search.'%'))
            ->latest('published_at')
            ->get();

        return view('livewire.admin.announcements.index', compact('announcements'));
    }
}
