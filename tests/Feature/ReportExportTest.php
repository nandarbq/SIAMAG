<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_pdf_excel_and_word_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => Carbon::parse('2026-11-01'),
            'end_date' => Carbon::parse('2026-12-31'),
        ]);

        $parameters = ['month' => 11, 'year' => 2026, 'student_id' => $student->id];

        $this->actingAs($admin)
            ->get(route('admin.reports.pdf', $parameters))
            ->assertOk()
            ->assertDownload('rekap-absensi-2026-11.pdf');

        $this->actingAs($admin)
            ->get(route('admin.reports.excel', $parameters))
            ->assertOk()
            ->assertDownload('rekap-absensi-2026-11.xlsx');

        $this->actingAs($admin)
            ->get(route('admin.reports.word', $parameters))
            ->assertOk()
            ->assertDownload('rekap-absensi-2026-11.docx');
    }

    public function test_students_cannot_download_reports(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->get(route('admin.reports.pdf', ['month' => 11, 'year' => 2026]))
            ->assertForbidden();
    }
}
