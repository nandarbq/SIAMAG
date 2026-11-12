<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\LogbookExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class LogbookExportController extends Controller
{
    public function word(LogbookExportService $exportService)
    {
        ['user' => $user, 'profile' => $profile, 'logbooks' => $logbooks, 'officeName' => $officeName] = $this->exportData($exportService);

        $phpWord = new PhpWord;
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginLeft' => 700,
            'marginRight' => 700,
            'marginTop' => 700,
            'marginBottom' => 700,
        ]);

        $section->addText('LOGBOOK MAGANG', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
        $section->addText($officeName, ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        $identity = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 30,
        ]);

        $this->addIdentityRow($identity, 'Nama', $user->name);
        $this->addIdentityRow($identity, 'NIM', $user->nim ?: '-');
        $this->addIdentityRow($identity, 'Institusi', $profile->institution ?: '-');
        $this->addIdentityRow($identity, 'Jurusan', $profile->major ?: '-');
        $this->addIdentityRow($identity, 'Semester', $profile->semester ?: '-');
        $this->addIdentityRow($identity, 'Periode Magang', $profile->start_date->translatedFormat('d F Y').' - '.$profile->end_date->translatedFormat('d F Y'));

        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $headers = ['No', 'Tanggal', 'Aktivitas', 'Uraian Kegiatan', 'Hasil', 'Kendala', 'Waktu'];
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(1200, ['bgColor' => '1E3A8A'])->addText($header, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }

        foreach ($logbooks as $index => $logbook) {
            $table->addRow();
            $table->addCell(500)->addText((string) ($index + 1), ['size' => 8], ['alignment' => Jc::CENTER]);
            $table->addCell(1200)->addText($logbook->date?->translatedFormat('d M Y') ?? '-', ['size' => 8]);
            $table->addCell(1800)->addText($logbook->activity ?: '-', ['size' => 8]);
            $table->addCell(2800)->addText($logbook->description ?: '-', ['size' => 8]);
            $table->addCell(2200)->addText($logbook->result ?: '-', ['size' => 8]);
            $table->addCell(1800)->addText($logbook->obstacle ?: '-', ['size' => 8]);
            $table->addCell(900)->addText($this->timeRange($logbook), ['size' => 8], ['alignment' => Jc::CENTER]);
        }

        if ($logbooks->isEmpty()) {
            $table->addRow();
            $table->addCell(11200, ['gridSpan' => 7])->addText('Belum ada logbook pada periode magang.', ['size' => 9], ['alignment' => Jc::CENTER]);
        }

        $section->addTextBreak(2);
        $section->addText('Banyuwangi, '.now()->translatedFormat('d F Y'), ['size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addText('Mahasiswa Magang', ['size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addTextBreak(3);
        $section->addText($user->name, ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addText('NIM '.($user->nim ?: '-'), ['size' => 10], ['alignment' => Jc::RIGHT]);

        $temp = tempnam(sys_get_temp_dir(), 'siamag').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($temp);

        return response()
            ->download($temp, 'logbook-'.($user->nim ?: $user->id).'.docx')
            ->deleteFileAfterSend(true);
    }

    public function pdf(LogbookExportService $exportService)
    {
        ['user' => $user, 'profile' => $profile, 'logbooks' => $logbooks, 'officeName' => $officeName] = $this->exportData($exportService);

        $pdf = Pdf::loadView('exports.logbook-pdf', [
            'user' => $user,
            'profile' => $profile,
            'logbooks' => $logbooks,
            'office_name' => $officeName,
            'office_address' => Setting::get('office_address', ''),
            'generated_at' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('logbook-'.($user->nim ?: $user->id).'.pdf');
    }

    private function exportData(LogbookExportService $exportService): array
    {
        $user = auth()->user()->load('internProfile');
        $profile = $user->internProfile;

        abort_unless($profile, 403, 'Profil magang belum lengkap.');
        abort_unless($exportService->canExport($profile), 403, 'Export logbook belum dibuka.');

        return [
            'user' => $user,
            'profile' => $profile,
            'logbooks' => $exportService->logbooksFor($user),
            'officeName' => Setting::get('office_name', 'BMKG Banyuwangi'),
        ];
    }

    private function addIdentityRow($table, string $label, string $value): void
    {
        $table->addRow();
        $table->addCell(1800)->addText($label, ['size' => 10]);
        $table->addCell(300)->addText(':', ['size' => 10]);
        $table->addCell(6000)->addText($value, ['size' => 10]);
    }

    private function timeRange($logbook): string
    {
        if (! $logbook->start_time && ! $logbook->end_time) {
            return '-';
        }

        return trim(substr((string) $logbook->start_time, 0, 5).' - '.substr((string) $logbook->end_time, 0, 5));
    }
}
