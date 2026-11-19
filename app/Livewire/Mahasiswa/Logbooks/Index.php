<?php

namespace App\Livewire\Mahasiswa\Logbooks;

use App\Enums\AttendanceStatus;
use App\Models\InternProfile;
use App\Models\Logbook;
use App\Services\InternshipWeekService;
use App\Services\LogbookExportService;
use App\Services\LogbookProgressService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Logbook Magang')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    private const PER_PAGE = 10;

    public string $search = '';

    public bool $showModal = false;

    public ?int $logbookId = null;

    public string $date = '';

    public string $activity = '';

    public string $description = '';

    public $photo;

    public ?string $existingPhoto = null;

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

        if ($this->isOutsideInternshipPeriod($profile)) {
            session()->flash('error', 'Anda hanya dapat mengisi logbook selama periode magang.');

            return;
        }

        $existing = auth()->user()->logbooks()->whereDate('date', today())->first();

        if ($existing) {
            $this->openEdit($existing->id);

            return;
        }

        $this->date = now()->toDateString();
        $this->showModal = true;
    }

    public function openCreateForWeek(int $week): void
    {
        $this->resetForm();
        $this->resetValidation();

        $profile = auth()->user()->internProfile;
        $weeks = app(InternshipWeekService::class);

        if ($this->isOutsideInternshipPeriod($profile)) {
            session()->flash('error', 'Anda hanya dapat mengisi logbook selama periode magang.');

            return;
        }

        if (! $profile || $week < 1 || $week > $weeks->count($profile)) {
            session()->flash('error', 'Pekan logbook tidak valid.');

            return;
        }

        ['start' => $weekStart, 'end' => $weekEnd] = $weeks->range($profile, $week);
        $todayAttended = today()->betweenIncluded($weekStart, $weekEnd) && $this->hasAttendedOn(today());
        $date = $todayAttended
            ? today()->copy()
            : ($this->firstAttendedDateInWeek($weekStart, $weekEnd)
                ?? (today()->betweenIncluded($weekStart, $weekEnd) ? today()->copy() : $weekStart->copy()));

        if ($date->greaterThan(today())) {
            session()->flash('error', 'Logbook tidak dapat diisi untuk tanggal yang akan datang.');

            return;
        }

        $existing = auth()->user()->logbooks()->whereDate('date', $date->toDateString())->first();

        if ($existing) {
            $this->openEdit($existing->id);

            return;
        }

        $this->date = $date->toDateString();
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
        $this->photo = null;
        $this->existingPhoto = $logbook->attachment_path;
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

        if ($logbook->attachment_path) {
            Storage::disk('local')->delete($logbook->attachment_path);
        }

        $logbook->delete();
        $this->resetPageAfterDelete();
        session()->flash('success', 'Logbook berhasil dihapus.');
    }

    public function render()
    {
        $userId = auth()->id();
        $profile = auth()->user()->internProfile;

        $logbooks = $this->logbooksQuery()
            ->latest('date')
            ->latest()
            ->paginate(self::PER_PAGE);

        $exportService = app(LogbookExportService::class);

        return view('livewire.mahasiswa.logbooks.index', [
            'logbooks' => $logbooks,
            'profile' => $profile,
            'total' => Logbook::query()->where('user_id', $userId)->count(),
            'progress' => app(LogbookProgressService::class)->for(auth()->user()),
            'exportOpen' => $profile ? $exportService->canExport($profile) : false,
            'exportOpensAt' => $profile ? $exportService->opensAt($profile) : null,
        ]);
    }

    private function logbooksQuery(): Builder
    {
        return Logbook::query()
            ->where('user_id', auth()->id())
            ->when($this->search !== '', function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('activity', 'like', $search)
                    ->orWhere('description', 'like', $search));
            });
    }

    private function resetPageAfterDelete(): void
    {
        $lastPage = max(1, (int) ceil($this->logbooksQuery()->count() / self::PER_PAGE));

        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }
    }

    private function isOutsideInternshipPeriod(?InternProfile $profile): bool
    {
        return $profile !== null && ! today()->betweenIncluded($profile->start_date, $profile->end_date);
    }

    private function firstAttendedDateInWeek(Carbon $start, Carbon $end): ?Carbon
    {
        $date = auth()->user()->attendances()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->whereIn('status', [AttendanceStatus::Hadir, AttendanceStatus::Terlambat])
            ->orderBy('date')
            ->value('date');

        return $date ? Carbon::parse($date)->startOfDay() : null;
    }

    private function hasAttendedOn(Carbon $date): bool
    {
        return auth()->user()->attendances()
            ->whereDate('date', $date->toDateString())
            ->whereIn('status', [AttendanceStatus::Hadir, AttendanceStatus::Terlambat])
            ->exists();
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

        if (! $logbook && $this->isOutsideInternshipPeriod($profile)) {
            session()->flash('error', 'Anda hanya dapat mengisi logbook selama periode magang.');

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
            'photo' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->existingPhoto === null),
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ], [
            'date.required' => 'Tanggal logbook wajib diisi.',
            'date.before_or_equal' => 'Logbook tidak dapat diisi untuk tanggal yang akan datang.',
            'date.unique' => 'Anda sudah memiliki logbook pada tanggal tersebut.',
            'activity.required' => 'Kegiatan wajib diisi.',
            'activity.max' => 'Kegiatan maksimal 200 karakter.',
            'photo.required' => 'Dokumentasi wajib diunggah.',
            'photo.image' => 'Dokumentasi harus berupa gambar.',
            'photo.mimes' => 'Dokumentasi harus berformat JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran dokumentasi maksimal 4 MB.',
        ]);

        $logbook ??= new Logbook(['user_id' => $userId]);

        $oldPhoto = $logbook->attachment_path;
        $attachmentPath = $oldPhoto;

        if ($this->photo) {
            $attachmentPath = $this->photo->store('logbook-photos/'.$userId, 'local');
        }

        $attendance = auth()->user()->attendances()
            ->whereDate('date', $this->date)
            ->first();

        $logbook->fill([
            'attendance_id' => $attendance?->id,
            'date' => $this->date,
            'activity' => $this->activity,
            'description' => $this->description ?: null,
            'attachment_path' => $attachmentPath,
        ])->save();

        if ($oldPhoto && $oldPhoto !== $attachmentPath) {
            Storage::disk('local')->delete($oldPhoto);
        }

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
            'photo',
            'existingPhoto',
        ]);
    }
}
