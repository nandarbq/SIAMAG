<?php

namespace App\Livewire\Admin\Holidays;

use App\Models\Holiday;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hari Libur')]
class Index extends Component
{
    public string $year = '';

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $date = '';

    public string $name = '';

    public function mount(): void
    {
        $this->year = (string) now()->year;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'date', 'name']);
        $this->showForm = true;
        $this->date = now()->toDateString();
    }

    public function openEdit(int $holidayId): void
    {
        $holiday = Holiday::query()->findOrFail($holidayId);

        $this->editingId = $holiday->id;
        $this->date = $holiday->date->format('Y-m-d');
        $this->name = $holiday->name;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->reset(['showForm', 'editingId', 'date', 'name']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'date' => ['required', 'date', Rule::unique('holidays', 'date')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'date.required' => 'Tanggal wajib diisi.',
            'date.unique' => 'Tanggal tersebut sudah terdaftar sebagai hari libur.',
            'name.required' => 'Nama hari libur wajib diisi.',
        ]);

        if ($this->editingId) {
            Holiday::query()->findOrFail($this->editingId)->update($validated);
        } else {
            Holiday::query()->create($validated);
        }

        $this->closeForm();
        session()->flash('success', 'Hari libur berhasil disimpan.');
    }

    public function delete(int $holidayId): void
    {
        Holiday::query()->findOrFail($holidayId)->delete();

        session()->flash('success', 'Hari libur berhasil dihapus.');
    }

    public function render()
    {
        $holidays = Holiday::query()
            ->when($this->year !== '', fn ($q) => $q->whereYear('date', $this->year))
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('date')
            ->get();

        return view('livewire.admin.holidays.index', compact('holidays'));
    }
}
