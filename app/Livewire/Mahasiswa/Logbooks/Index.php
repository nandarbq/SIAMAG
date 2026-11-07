<?php

namespace App\Livewire\Mahasiswa\Logbooks;

use App\Enums\LogbookStatus;
use App\Models\Logbook;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Logbook Harian')]
class Index extends Component
{
    use WithPagination;

    public string $status = '';

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
        if (in_array($property, ['status', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->resetValidation();

        $profile = auth()->user()->internProfile;

        if ($profile && ! now()->betweenIncluded($profile->start_date, $profile->end_date)) {
            session()->flash('error', 'Anda hanya dapat mengisi logbook selama periode magang.');

            return;
        }

        $existing = auth()->user()->logbooks()->whereDate('date', today())->first();

        if ($existing && $existing->isEditable()) {
            $this->openEdit($existing->id);

            return;
        }

        if ($existing) {
            session()->flash('error', 'Logbook hari ini sudah dikirim dan sedang menunggu review.');

            return;
        }

        $this->date = now()->toDateString();
        $this->startTime = Setting::get('work_start', '07:30');
        $this->endTime = Setting::get('work_end', '16:00');
        $this->showModal = true;
    }

    public function openEdit(int $logbookId): void
    {
        $logbook = auth()->user()->logbooks()->findOrFail($logbookId);

        if (! $logbook->isEditable()) {
            session()->flash('error', 'Logbook ini tidak dapat diubah karena sudah dikirim.');

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

    public function saveDraft(): void
    {
        $this->persist(LogbookStatus::Draft);
    }

    public function submit(): void
    {
        $this->persist(LogbookStatus::Submitted);
    }

    public function submitExisting(int $logbookId): void
    {
        $logbook = auth()->user()->logbooks()->findOrFail($logbookId);

        if (! $logbook->isEditable()) {
            session()->flash('error', 'Logbook ini sudah dikirim.');

            return;
        }

        $logbook->update([
            'status' => LogbookStatus::Submitted,
            'review_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        session()->flash('success', 'Logbook berhasil dikirim untuk review.');
    }

    public function delete(int $logbookId): void
    {
        $logbook = auth()->user()->logbooks()->findOrFail($logbookId);

        if (! $logbook->isEditable()) {
            session()->flash('error', 'Logbook yang sudah dikirim tidak dapat dihapus.');

            return;
        }

        $logbook->delete();
        session()->flash('success', 'Logbook berhasil dihapus.');
    }

    public function resetFilters(): void
    {
        $this->reset(['status', 'search']);
        $this->resetPage();
    }

    public function render()
    {
        $userId = auth()->id();
        $profile = auth()->user()->internProfile;

        $logbooks = Logbook::query()
            ->with('reviewer')
            ->where('user_id', $userId)
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('activity', 'like', $search)
                    ->orWhere('description', 'like', $search));
            })
            ->latest('date')
            ->latest()
            ->paginate(8);

        $counts = Logbook::query()
            ->where('user_id', $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.mahasiswa.logbooks.index', [
            'logbooks' => $logbooks,
            'counts' => $counts,
            'statuses' => LogbookStatus::cases(),
            'profile' => $profile,
            'total' => $counts->sum(),
        ]);
    }

    private function persist(LogbookStatus $status): void
    {
        $userId = auth()->id();
        $profile = auth()->user()->internProfile;

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
                Rule::unique('logbooks', 'date')
                    ->where(fn ($query) => $query->where('user_id', $userId))
                    ->ignore($this->logbookId),
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

        $logbook = $this->logbookId
            ? auth()->user()->logbooks()->findOrFail($this->logbookId)
            : new Logbook(['user_id' => $userId]);

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
            'status' => $status,
            'review_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ])->save();

        $this->closeModal();

        session()->flash(
            'success',
            $status === LogbookStatus::Submitted
                ? 'Logbook berhasil dikirim untuk review.'
                : 'Logbook berhasil disimpan sebagai draft.'
        );
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
