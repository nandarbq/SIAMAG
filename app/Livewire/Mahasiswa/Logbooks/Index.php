<?php

namespace App\Livewire\Mahasiswa\Logbooks;

use App\Models\Logbook;
use App\Models\Setting;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Logbook Harian')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $logbookId = null;

    public string $date = '';

    public string $activity = '';

    public string $description = '';

    public string $startTime = '';

    public string $endTime = '';

    public string $result = '';

    public string $obstacle = '';

    public function updating(string $property): void
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->resetValidation();

        $profile = auth()->user()->internProfile;

        if ($profile && ! today()->betweenIncluded($profile->start_date, $profile->end_date)) {
            session()->flash('error', 'Anda hanya dapat mengisi logbook selama periode magang.');

            return;
        }

        $existing = auth()->user()->logbooks()->whereDate('date', today())->first();

        if ($existing) {
            $this->openEdit($existing->id);

            return;
        }

        $this->date = now()->toDateString();
        $this->startTime = Setting::get('work_start', '07:30');
        $this->endTime = Setting::get('work_end', '16:00');
        $this->showModal = true;
    }

    public function openCreateForDate(string $date): void
    {
        $this->resetForm();
        $this->resetValidation();

        $profile = auth()->user()->internProfile;
        $date = Carbon::parse($date)->startOfDay();

        if ($date->greaterThan(today())) {
            session()->flash('error', 'Logbook tidak dapat diisi untuk tanggal yang akan datang.');

            return;
        }

        if ($profile && ! $date->betweenIncluded($profile->start_date, $profile->end_date)) {
            session()->flash('error', 'Tanggal logbook harus berada di dalam periode magang.');

            return;
        }

        $existing = auth()->user()->logbooks()->whereDate('date', $date->toDateString())->first();

        if ($existing) {
            $this->openEdit($existing->id);

            return;
        }

        $this->date = $date->toDateString();
        $this->startTime = Setting::get('work_start', '07:30');
        $this->endTime = Setting::get('work_end', '16:00');
        $this->showModal = true;
    }

    public function openEdit(int $logbookId): void
    {
        $logbook = auth()->user()->logbooks()->findOrFail($logbookId);

        if (! $logbook->isEditable()) {
            session()->flash('error', 'Logbook ini tidak dapat diubah karena sudah terkunci.');

            return;
        }

        $this->resetValidation();
        $this->logbookId = $logbook->id;
        $this->date = $logbook->date->toDateString();
        $this->activity = $logbook->activity;
        $this->description = $logbook->description ?? '';
        $this->startTime = $logbook->start_time ? substr($logbook->start_time, 0, 5) : '';
        $this->endTime = $logbook->end_time ? substr($logbook->end_time, 0, 5) : '';
        $this->result = $logbook->result ?? '';
        $this->obstacle = $logbook->obstacle ?? '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->persist();
    }

    public function delete(int $logbookId): void
    {
        $logbook = auth()->user()->logbooks()->findOrFail($logbookId);

        if (! $logbook->isEditable()) {
            session()->flash('error', 'Logbook yang sudah terkunci tidak dapat dihapus.');

            return;
        }

        $logbook->delete();
        session()->flash('success', 'Logbook berhasil dihapus.');
    }

    public function resetFilters(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    public function render()
    {
        $userId = auth()->id();
        $profile = auth()->user()->internProfile;

        $logbooks = Logbook::query()
            ->where('user_id', $userId)
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('activity', 'like', $search)
                    ->orWhere('description', 'like', $search));
            })
            ->latest('date')
            ->latest()
            ->paginate(8);

        return view('livewire.mahasiswa.logbooks.index', [
            'logbooks' => $logbooks,
            'profile' => $profile,
            'total' => Logbook::query()->where('user_id', $userId)->count(),
        ]);
    }

    private function persist(): void
    {
        $userId = auth()->id();
        $profile = auth()->user()->internProfile;

        $logbook = $this->logbookId
            ? auth()->user()->logbooks()->findOrFail($this->logbookId)
            : null;

        if ($logbook && ! $logbook->isEditable()) {
            session()->flash('error', 'Logbook ini tidak dapat diubah karena sudah terkunci.');

            return;
        }

        $this->validate([
            'date' => [
                'required',
                'date',
                'before_or_equal:today',
                function (string $attribute, mixed $value, \Closure $fail) use ($profile) {
                    if (! $profile) {
                        return;
                    }

                    $date = Carbon::parse($value);

                    if ($date->lessThan($profile->start_date) || $date->greaterThan($profile->end_date)) {
                        $fail('Tanggal logbook harus berada di dalam periode magang.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) use ($userId) {
                    $exists = Logbook::query()
                        ->where('user_id', $userId)
                        ->whereDate('date', $value)
                        ->when($this->logbookId, fn ($query) => $query->whereKeyNot($this->logbookId))
                        ->exists();

                    if ($exists) {
                        $fail('Anda sudah memiliki logbook pada tanggal tersebut.');
                    }
                },
            ],
            'activity' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'startTime' => ['nullable', 'date_format:H:i'],
            'endTime' => ['nullable', 'date_format:H:i', 'after:startTime'],
            'result' => ['nullable', 'string', 'max:2000'],
            'obstacle' => ['nullable', 'string', 'max:2000'],
        ], [
            'date.required' => 'Tanggal logbook wajib diisi.',
            'date.before_or_equal' => 'Logbook tidak dapat diisi untuk tanggal yang akan datang.',
            'date.unique' => 'Anda sudah memiliki logbook pada tanggal tersebut.',
            'activity.required' => 'Aktivitas utama wajib diisi.',
            'activity.max' => 'Aktivitas maksimal 200 karakter.',
            'endTime.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $logbook ??= new Logbook(['user_id' => $userId]);

        $attendance = auth()->user()->attendances()
            ->whereDate('date', $this->date)
            ->first();

        $logbook->fill([
            'attendance_id' => $attendance?->id,
            'date' => $this->date,
            'activity' => $this->activity,
            'description' => $this->description ?: null,
            'start_time' => $this->startTime ?: null,
            'end_time' => $this->endTime ?: null,
            'result' => $this->result ?: null,
            'obstacle' => $this->obstacle ?: null,
        ])->save();

        $this->closeModal();

        session()->flash('success', 'Logbook berhasil disimpan.');
    }

    private function resetForm(): void
    {
        $this->reset([
            'logbookId',
            'date',
            'activity',
            'description',
            'startTime',
            'endTime',
            'result',
            'obstacle',
        ]);
    }
}
