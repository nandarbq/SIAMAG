<?php

namespace App\Livewire\Admin\Logbooks;

use App\Enums\LogbookStatus;
use App\Models\Logbook;
use App\Notifications\WorkflowNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Review Logbook')]
class Index extends Component
{
    use WithPagination;

    public string $status = LogbookStatus::Submitted->value;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 12;

    public ?int $reviewingId = null;

    public string $reviewNote = '';

    public function updating(string $property): void
    {
        if (in_array($property, ['status', 'search', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function openReview(int $logbookId): void
    {
        $logbook = Logbook::query()->findOrFail($logbookId);

        $this->reviewingId = $logbook->id;
        $this->reviewNote = $logbook->review_note ?? '';
    }

    public function closeReview(): void
    {
        $this->reset(['reviewingId', 'reviewNote']);
        $this->resetValidation();
    }

    public function approve(int $logbookId): void
    {
        $logbook = Logbook::query()->with('user')->findOrFail($logbookId);

        if ($logbook->status !== LogbookStatus::Submitted) {
            $this->closeReview();
            session()->flash('error', 'Logbook ini sudah diproses.');

            return;
        }

        $logbook->update([
            'status' => LogbookStatus::Approved,
            'review_note' => $this->reviewNote ?: 'Logbook disetujui.',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $logbook->user?->notify(new WorkflowNotification(
            'Logbook disetujui',
            "Logbook {$logbook->date->translatedFormat('d M Y')} Anda telah disetujui.",
            route('mahasiswa.logbooks'),
            'book',
        ));

        $this->closeReview();
        session()->flash('success', 'Logbook berhasil disetujui.');
    }

    public function reject(int $logbookId): void
    {
        $this->validate([
            'reviewNote' => ['required', 'string', 'max:500'],
        ], [
            'reviewNote.required' => 'Catatan revisi wajib diisi saat menolak logbook.',
        ]);

        $logbook = Logbook::query()->with('user')->findOrFail($logbookId);

        if ($logbook->status !== LogbookStatus::Submitted) {
            $this->closeReview();
            session()->flash('error', 'Logbook ini sudah diproses.');

            return;
        }

        $logbook->update([
            'status' => LogbookStatus::Rejected,
            'review_note' => $this->reviewNote,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $logbook->user?->notify(new WorkflowNotification(
            'Logbook perlu revisi',
            "Logbook {$logbook->date->translatedFormat('d M Y')} perlu diperbaiki: {$this->reviewNote}",
            route('mahasiswa.logbooks'),
            'book',
        ));

        $this->closeReview();
        session()->flash('success', 'Logbook dikembalikan untuk revisi.');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo']);
        $this->status = LogbookStatus::Submitted->value;
        $this->resetPage();
    }

    public function render()
    {
        $logbooks = Logbook::query()
            ->with(['user', 'reviewer'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($q) => $q
                    ->where('activity', 'like', $search)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $search)->orWhere('nim', 'like', $search)));
            })
            ->latest('date')
            ->latest()
            ->paginate($this->perPage);

        $counts = Logbook::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.logbooks.index', [
            'logbooks' => $logbooks,
            'counts' => $counts,
            'statuses' => LogbookStatus::cases(),
            'reviewing' => $this->reviewingId ? Logbook::query()->with('user')->find($this->reviewingId) : null,
        ]);
    }
}
