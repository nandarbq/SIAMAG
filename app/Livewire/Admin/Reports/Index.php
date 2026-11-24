<?php

namespace App\Livewire\Admin\Reports;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Laporan')]
class Index extends Component
{
    public string $month = '';

    public string $year = '';

    public string $studentId = '';

    public function mount(): void
    {
        $this->month = (string) now()->month;
        $this->year = (string) now()->year;
    }

    public function updatedMonth(mixed $value): void
    {
        $this->validateOnly('month');
    }

    public function updatedYear(mixed $value): void
    {
        $this->validateOnly('year');
    }

    public function updatedStudentId(mixed $value): void
    {
        if ($this->studentId === '') {
            $this->resetValidation('studentId');

            return;
        }

        $this->validateOnly('studentId');
    }

    protected function rules(): array
    {
        return ReportService::filterRules('studentId');
    }

    protected function messages(): array
    {
        return ReportService::filterMessages('studentId');
    }

    public function render(ReportService $reportService)
    {
        $filters = $this->validatedFilters();

        $report = $reportService->recap(
            (int) ($filters['month'] ?? now()->month),
            (int) ($filters['year'] ?? now()->year),
            isset($filters['studentId']) ? (int) $filters['studentId'] : null,
        );

        $students = User::query()
            ->where('role', UserRole::Mahasiswa)
            ->orderBy('name')
            ->get(['id', 'name', 'nim']);

        return view('livewire.admin.reports.index', [
            'report' => $report,
            'students' => $students,
            'month' => $filters['month'],
            'year' => $filters['year'],
            'studentId' => $filters['studentId'] ?? null,
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [
                $month => now()->setMonth($month)->translatedFormat('F'),
            ]),
            'years' => range(now()->year - 2, now()->year + 1),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(): array
    {
        $validator = Validator::make([
            'month' => $this->month,
            'year' => $this->year,
            'studentId' => $this->studentId !== '' ? $this->studentId : null,
        ], ReportService::filterRules('studentId'), ReportService::filterMessages('studentId'));

        if ($validator->fails()) {
            return [
                'month' => now()->month,
                'year' => now()->year,
                'studentId' => null,
            ];
        }

        return $validator->validated();
    }
}
