<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class ReportExportController extends Controller
{
    public function excel(Request $request, ReportService $reportService)
    {
        $data = $this->reportData($request, $reportService);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Absensi');

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'REKAP ABSENSI & LOGBOOK MAGANG');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', $data['office_name'].' · '.$data['period_label']);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

        $headers = ['Nama', 'NIM', 'Institusi', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha', 'Logbook Disetujui', 'Kehadiran (%)'];
        $sheet->fromArray($headers, null, 'A4');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A4:J4')->applyFromArray($headerStyle);

        $rowNumber = 5;

        foreach ($data['rows'] as $row) {
            $sheet->fromArray([
                $row['name'],
                $row['nim'],
                $row['institution'],
                $row['hadir'],
                $row['terlambat'],
                $row['izin'],
                $row['sakit'],
                $row['alpha'],
                $row['logbook_approved'],
                $row['percentage'],
            ], null, 'A'.$rowNumber);
            $rowNumber++;
        }

        $totalRow = $rowNumber;
        $sheet->fromArray([
            'TOTAL',
            '',
            '',
            $data['summary']['hadir'],
            $data['summary']['terlambat'],
            $data['summary']['izin'],
            $data['summary']['sakit'],
            $data['summary']['alpha'],
            $data['summary']['logbook_approved'],
            '',
        ], null, 'A'.$totalRow);
        $sheet->getStyle('A'.$totalRow.':J'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$totalRow.':J'.$totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EFF6FF');

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A4:J'.$totalRow)->getBorders()->getAllBorders()->setBorderStyle(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
        );

        $temp = tempnam(sys_get_temp_dir(), 'siamag').'.xlsx';
        (new Xlsx($spreadsheet))->save($temp);
        $spreadsheet->disconnectWorksheets();

        return response()->download($temp, $this->filename($data, 'xlsx'))->deleteFileAfterSend(true);
    }

    public function pdf(Request $request, ReportService $reportService)
    {
        $data = $this->reportData($request, $reportService);

        $pdf = Pdf::loadView('exports.report-pdf', $data)->setPaper('a4', 'landscape');

        return $pdf->download($this->filename($data, 'pdf'));
    }

    public function word(Request $request, ReportService $reportService)
    {
        $data = $this->reportData($request, $reportService);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginLeft' => 600,
            'marginRight' => 600,
        ]);

        $section->addText('REKAP ABSENSI & LOGBOOK MAGANG', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
        $section->addText($data['office_name'].' · '.$data['period_label'], ['italic' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $headers = ['No', 'Nama', 'NIM', 'Institusi', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha', 'Logbook', 'Kehadiran'];
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(800, ['bgColor' => '1E3A8A'])->addText($header, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }

        foreach ($data['rows'] as $index => $row) {
            $table->addRow();
            $table->addCell(500)->addText((string) ($index + 1), ['size' => 9]);
            $table->addCell(2200)->addText($row['name'], ['size' => 9]);
            $table->addCell(1200)->addText($row['nim'], ['size' => 9]);
            $table->addCell(2400)->addText($row['institution'], ['size' => 9]);
            $table->addCell(700)->addText((string) $row['hadir'], ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(800)->addText((string) $row['terlambat'], ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(700)->addText((string) $row['izin'], ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(700)->addText((string) $row['sakit'], ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(700)->addText((string) $row['alpha'], ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(900)->addText($row['logbook_approved'].'/'.$row['logbook_total'], ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(900)->addText($row['percentage'].'%', ['size' => 9], ['alignment' => Jc::CENTER]);
        }

        $table->addRow();
        foreach (['TOTAL', '', '', '', $data['summary']['hadir'], $data['summary']['terlambat'], $data['summary']['izin'], $data['summary']['sakit'], $data['summary']['alpha'], $data['summary']['logbook_approved'], ''] as $cell) {
            $table->addCell(800, ['bgColor' => 'EFF6FF'])->addText((string) $cell, ['bold' => true, 'size' => 9]);
        }

        $section->addTextBreak(2);
        $section->addText('Banyuwangi, '.$data['generated_at']->translatedFormat('d F Y'), ['size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addTextBreak(3);
        $section->addText('Administrator SIAMAG', ['bold' => true, 'size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addText('BMKG Banyuwangi', ['size' => 10], ['alignment' => Jc::RIGHT]);

        $temp = tempnam(sys_get_temp_dir(), 'siamag').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($temp);

        return response()->download($temp, $this->filename($data, 'docx'))->deleteFileAfterSend(true);
    }

    private function reportData(Request $request, ReportService $reportService): array
    {
        $month = min(12, max(1, (int) $request->input('month', now()->month)));
        $year = (int) $request->input('year', now()->year);
        $studentId = $request->filled('student_id') ? (int) $request->input('student_id') : null;

        return $reportService->recap($month, $year, $studentId);
    }

    private function filename(array $data, string $extension): string
    {
        return 'rekap-absensi-'.$data['year'].'-'.str_pad((string) $data['month'], 2, '0', STR_PAD_LEFT).'.'.$extension;
    }
}
