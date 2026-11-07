<?php

namespace App\Livewire\Admin\Attendances;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Data Absensi')]
class Index extends Component
{
    use WithPagination;

    public string $date = '';

    public string $status = '';

    public string $search = '';

    public int $perPage = 15;

    public ?int $editingId = null;

    public string $editStatus = '';

    public string $editNote = '';

    public string $editCheckIn = '';

    public string $editCheckOut = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['date', 'status', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function openCorrect(int $attendanceId): void
    {
        $attendance = Attendance::query()->findOrFail($attendanceId);

        $this->editingId = $attendance->id;
        $this->editStatus = $attendance->status->value;
        $this->editNote = $attendance->note ?? '';
        $this->editCheckIn = $attendance->check_in_at?->format('H:i') ?? '';
        $this->editCheckOut = $attendance->check_out_at?->format('H:i') ?? '';
    }

    public function closeCorrect(): void
    {
        $this->reset(['editingId', 'editStatus', 'editNote', 'editCheckIn', 'editCheckOut']);
        $this->resetValidation();
    }

    public function saveCorrect(AttendanceService $service): void
    {
        $validated = $this->validate([
            'editStatus' => ['required', Rule::enum(AttendanceStatus::class)],
            'editNote' => ['nullable', 'string', 'max:500'],
            'editCheckIn' => ['nullable', 'date_format:H:i'],
            'editCheckOut' => ['nullable', 'date_format:H:i'],
        ]);

        $attendance = Attendance::query()->findOrFail($this->editingId);
        $date = $attendance->date->format('Y-m-d');

        $service->correct($attendance, [
            'status' => AttendanceStatus::from($validated['editStatus']),
            'note' => $validated['editNote'] ?: null,
            'check_in_at' => $validated['editCheckIn'] ? Carbon::parse($date.' '.$validated['editCheckIn']) : null,
            'check_out_at' => $validated['editCheckOut'] ? Carbon::parse($date.' '.$validated['editCheckOut']) : null,
        ]);

        $this->closeCorrect();
        session()->flash('success', 'Data absensi berhasil dikoreksi.');
    }

    public function resetFilters(): void
    {
        $this->reset(['date', 'status', 'search']);
        $this->date = now()->toDateString();
        $this->resetPage();
    }

    public function render()
    {
        $query = Attendance::query()
            ->with('user')
            ->when($this->date !== '', fn ($q) => $q->whereDate('date', $this->date))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->whereHas('user', fn ($q) => $q
                    ->where('name', 'like', $search)
                    ->orWhere('nim', 'like', $search));
            });

        $summary = (clone $query)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $attendances = $query
            ->latest('date')
            ->latest('check_in_at')
            ->paginate($this->perPage);

        return view('livewire.admin.attendances.index', [
            'attendances' => $attendances,
            'summary' => $summary,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }
}
