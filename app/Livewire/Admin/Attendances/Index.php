<?php

namespace App\Livewire\Admin\Attendances;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Closure;
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

    public ?int $viewingId = null;

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

    public function updatedSearch(mixed $value): void
    {
        $this->status = '';
        $this->date = now()->toDateString();
    }

    public function openCorrect(int $attendanceId): void
    {
        $attendance = Attendance::query()->findOrFail($attendanceId);

        $this->viewingId = null;
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

    public function openDetail(int $attendanceId): void
    {
        $attendance = Attendance::query()->findOrFail($attendanceId);

        $this->editingId = null;
        $this->viewingId = $attendance->id;
    }

    public function closeDetail(): void
    {
        $this->reset('viewingId');
    }

    public function saveCorrect(AttendanceService $service): void
    {
        $validated = $this->validate([
            'editStatus' => ['required', Rule::enum(AttendanceStatus::class)],
            'editNote' => ['nullable', 'string', 'max:500'],
            'editCheckIn' => [
                'nullable',
                'bail',
                'required_if:editStatus,'.AttendanceStatus::Hadir->value,
                'required_if:editStatus,'.AttendanceStatus::Terlambat->value,
                'date_format:H:i',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $status = AttendanceStatus::tryFrom($this->editStatus);

                    if ($status === null || $this->statusUsesTimes($status) || blank($value)) {
                        return;
                    }

                    $fail('Jam masuk tidak boleh diisi untuk status '.$status->label().'.');
                },
            ],
            'editCheckOut' => [
                'nullable',
                'bail',
                'date_format:H:i',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $status = AttendanceStatus::tryFrom($this->editStatus);

                    if ($status === null) {
                        return;
                    }

                    if (! $this->statusUsesTimes($status) && filled($value)) {
                        $fail('Jam pulang tidak boleh diisi untuk status '.$status->label().'.');
                    }

                    if (blank($value) || blank($this->editCheckIn)) {
                        return;
                    }

                    if (Carbon::parse($this->editCheckIn)->greaterThanOrEqualTo(Carbon::parse($value))) {
                        $fail('Jam pulang harus setelah jam masuk.');
                    }
                },
            ],
        ], [
            'editCheckIn.required_if' => 'Jam masuk wajib diisi untuk status Hadir atau Terlambat.',
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
            'viewing' => $this->viewingId
                ? Attendance::query()->with('user')->find($this->viewingId)
                : null,
        ]);
    }

    private function statusUsesTimes(AttendanceStatus $status): bool
    {
        return in_array($status, [AttendanceStatus::Hadir, AttendanceStatus::Terlambat], true);
    }
}
