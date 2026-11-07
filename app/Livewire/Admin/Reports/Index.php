<?php

namespace App\Livewire\Admin\Reports;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ReportService;
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

    public function render(ReportService $reportService)
    {
        $report = $reportService->recap(
            (int) $this->month,
            (int) $this->year,
            $this->studentId !== '' ? (int) $this->studentId : null,
        );

        $students = User::query()
            ->where('role', UserRole::Mahasiswa)
            ->orderBy('name')
            ->get(['id', 'name', 'nim']);

        return view('livewire.admin.reports.index', [
            'report' => $report,
            'students' => $students,
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [
                $month => now()->setMonth($month)->translatedFormat('F'),
            ]),
            'years' => range(now()->year - 2, now()->year + 1),
        ]);
    }
}
