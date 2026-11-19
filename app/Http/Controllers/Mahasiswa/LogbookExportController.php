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
        ['user' => $user, 'profile' => $profile, 'rows' => $rows, 'officeName' => $officeName] = $this->exportData($exportService);

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
        $section->addText('Tabel Logbook Kegiatan', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
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

        $headers = ['No', 'Pekan', 'Kegiatan', 'Uraian Kegiatan', 'Dokumentasi'];
        $widths = [600, 2200, 2600, 7000, 2000];
        $table->addRow(null, ['tblHeader' => true]);

        foreach ($headers as $index => $header) {
            $table->addCell($widths[$index], ['bgColor' => '1E3A8A'])->addText($header, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }

        foreach ($rows as $row) {
            $table->addRow();
            $table->addCell(600)->addText((string) $row['number'], ['size' => 8], ['alignment' => Jc::CENTER]);
            $table->addCell(2200)->addText($row['week'], ['size' => 8]);
            $table->addCell(2600)->addText($row['activity'], ['size' => 8]);
            $table->addCell(7000)->addText($row['description'], ['size' => 8]);

            $photoCell = $table->addCell(2000);

            if ($row['photo_binary']) {
                $photoCell->addImage($row['photo_binary'], ['width' => 80, 'height' => 60]);
            } else {
                $photoCell->addText('-', ['size' => 8], ['alignment' => Jc::CENTER]);
            }
        }

        if ($rows->isEmpty()) {
            $table->addRow();
            $table->addCell(14400, ['gridSpan' => 5])->addText('Belum ada logbook pada periode magang.', ['size' => 9], ['alignment' => Jc::CENTER]);
        }

        $section->addTextBreak(2);
        $section->addText('Banyuwangi, '.now()->translatedFormat('d F Y'), ['size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addText('Mahasiswa Magang', ['size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addTextBreak(3);
        $section->addText($user->name, ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addText('NIM '.($user->nim ?: '-'), ['size' => 10], ['alignment' => Jc::RIGHT]);

        $temp = tempnam(sys_get_temp_dir(), 'siamag');
        IOFactory::createWriter($phpWord, 'Word2007')->save($temp);

        return response()
            ->download($temp, 'logbook-'.($user->nim ?: $user->id).'.docx')
            ->deleteFileAfterSend(true);
    }

    public function pdf(LogbookExportService $exportService)
    {
        ['user' => $user, 'profile' => $profile, 'rows' => $rows, 'officeName' => $officeName] = $this->exportData($exportService);

        $pdf = Pdf::loadView('exports.logbook-pdf', [
            'user' => $user,
            'profile' => $profile,
            'rows' => $rows,
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
            'rows' => $exportService->rowsFor($user),
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
}
