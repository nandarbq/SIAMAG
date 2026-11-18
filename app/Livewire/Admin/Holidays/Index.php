<?php

namespace App\Livewire\Admin\Holidays;

use App\Models\Holiday;
use App\Services\HolidaySyncService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

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
            'date' => ['required', 'date', function (string $attribute, mixed $value, \Closure $fail) {
                $query = Holiday::query()->whereDate('date', $value);

                if ($this->editingId) {
                    $query->whereKeyNot($this->editingId);
                }

                if ($query->exists()) {
                    $fail('Tanggal tersebut sudah terdaftar sebagai hari libur.');
                }
            }],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'date.required' => 'Tanggal wajib diisi.',
            'date.unique' => 'Tanggal tersebut sudah terdaftar sebagai hari libur.',
            'name.required' => 'Nama hari libur wajib diisi.',
        ]);

        $validated['source'] = Holiday::SOURCE_MANUAL;

        if ($this->editingId) {
            Holiday::query()->findOrFail($this->editingId)->update($validated);
        } else {
            Holiday::query()->create($validated);
        }

        $this->closeForm();
        session()->flash('success', 'Hari libur berhasil disimpan.');
    }

    public function sync(HolidaySyncService $service): void
    {
        $this->resetValidation('year');

        $validated = $this->validate([
            'year' => ['required', 'integer', 'between:2020,2100'],
        ], [
            'year.required' => 'Tahun wajib diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.between' => 'Tahun harus berada di antara 2020 dan 2100.',
        ]);

        try {
            $result = $service->syncYear((int) $validated['year']);
        } catch (Throwable $exception) {
            Log::error('Holiday sync failed from admin panel.', [
                'year' => $validated['year'],
                'message' => $exception->getMessage(),
            ]);

            session()->flash('error', 'Gagal menyinkronkan hari libur. Silakan coba lagi.');

            return;
        }

        session()->flash('success', sprintf(
            'Sinkronisasi %d selesai: %d ditambahkan, %d diperbarui, %d dilewati.',
            $result['year'],
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));
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
