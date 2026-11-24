<?php

namespace App\Livewire\Admin\Groups;

use App\Models\InternGroup;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?InternGroup $group = null;

    public bool $embedded = false;

    public string $code = '';

    public string $name = '';

    public string $institution = '';

    public string $division = '';

    public string $field_supervisor = '';

    public string $supervisor_phone = '';

    public string $start_date = '';

    public string $end_date = '';

    public bool $is_active = true;

    public bool $is_individual = false;

    public function mount(?InternGroup $group = null, bool $embedded = false): void
    {
        $this->embedded = $embedded;

        if ($group && $group->exists) {
            $this->group = $group;
            $this->code = $group->code;
            $this->name = $group->name;
            $this->institution = $group->institution;
            $this->division = $group->division ?? '';
            $this->field_supervisor = $group->field_supervisor ?? '';
            $this->supervisor_phone = $group->supervisor_phone ?? '';
            $this->start_date = $group->start_date?->format('Y-m-d') ?? '';
            $this->end_date = $group->end_date?->format('Y-m-d') ?? '';
            $this->is_active = (bool) $group->is_active;
            $this->is_individual = (bool) $group->is_individual;

            return;
        }

        $this->start_date = now()->toDateString();
        $this->end_date = now()->addMonths(4)->toDateString();
        $this->code = $this->nextGroupCode();
    }

    public function updatedIsIndividual(): void
    {
        $this->code = $this->withIndividualMarker($this->code, $this->is_individual);
    }

    public function save()
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('intern_groups', 'code')->ignore($this->group?->id)],
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'field_supervisor' => ['nullable', 'string', 'max:255'],
            'supervisor_phone' => ['nullable', 'string', 'max:30'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'code.required' => 'Kode kelompok wajib diisi.',
            'code.unique' => 'Kode kelompok sudah digunakan.',
            'name.required' => 'Nama kelompok wajib diisi.',
            'institution.required' => 'Institusi / kampus wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        $validated['division'] = $validated['division'] ?: null;
        $validated['field_supervisor'] = $validated['field_supervisor'] ?: null;
        $validated['supervisor_phone'] = $validated['supervisor_phone'] ?: null;
        $validated['is_active'] = $this->is_active;
        $validated['is_individual'] = $this->is_individual;

        if ($this->is_individual && $this->group && $this->group->internProfiles()->count() > 1) {
            $this->addError('is_individual', 'Kelompok dengan lebih dari satu anggota tidak bisa ditandai sebagai magang mandiri.');

            return;
        }

        $message = $this->group
            ? 'Kelompok magang berhasil diperbarui.'
            : 'Kelompok magang berhasil ditambahkan.';

        if ($this->group) {
            $this->group->update($validated);
        } else {
            InternGroup::query()->create($validated);
        }

        if ($this->embedded) {
            $this->dispatch('group-saved', message: $message);

            return;
        }

        session()->flash('success', $message);

        return redirect()->route('admin.groups');
    }

    public function render()
    {
        return view('livewire.admin.groups.form');
    }

    private function nextGroupCode(): string
    {
        $year = now()->year;

        $lastSequence = InternGroup::query()
            ->where('code', 'like', sprintf('MAG-%d-%%', $year))
            ->pluck('code')
            ->map(fn (string $code) => $this->codeSequence($code, $year))
            ->filter(fn (?int $sequence) => $sequence !== null)
            ->max() ?? 0;

        return sprintf('MAG-%d-%03d', $year, $lastSequence + 1);
    }

    private function codeSequence(string $code, int $year): ?int
    {
        if (! preg_match(sprintf('/^MAG-%d-(?:IND-)?(\d{3})$/', $year), $code, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function withIndividualMarker(string $code, bool $isIndividual): string
    {
        if ($isIndividual && preg_match('/^MAG-(\d{4})-(\d{3})$/', $code, $matches)) {
            return sprintf('MAG-%s-IND-%s', $matches[1], $matches[2]);
        }

        if (! $isIndividual && preg_match('/^MAG-(\d{4})-IND-(\d{3})$/', $code, $matches)) {
            return sprintf('MAG-%s-%s', $matches[1], $matches[2]);
        }

        return $code;
    }
}
