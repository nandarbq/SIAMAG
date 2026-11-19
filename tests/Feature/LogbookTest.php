<?php

namespace Tests\Feature;

use App\Livewire\Admin\Logbooks\Index as AdminLogbookIndex;
use App\Livewire\Mahasiswa\Logbooks\Index as StudentLogbookIndex;
use App\Models\Logbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LogbookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));
        Storage::fake('local');
    }

    public function test_student_can_save_a_logbook(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->set('activity', 'Analisis data curah hujan')
            ->set('description', 'Mengolah data observasi harian.')
            ->set('photo', UploadedFile::fake()->image('kegiatan.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $logbook = Logbook::query()->where('user_id', $student->id)->firstOrFail();

        $this->assertSame('2026-11-09', $logbook->date->toDateString());
        $this->assertSame('Analisis data curah hujan', $logbook->activity);
        $this->assertSame('Mengolah data observasi harian.', $logbook->description);
        $this->assertNotNull($logbook->attachment_path);
    }

    public function test_logbooks_table_does_not_have_unused_columns(): void
    {
        foreach ([
            'start_time',
            'end_time',
            'result',
            'obstacle',
            'status',
            'review_note',
            'reviewed_by',
            'reviewed_at',
        ] as $column) {
            $this->assertFalse(Schema::hasColumn('logbooks', $column));
        }
    }

    public function test_logbook_form_only_shows_activity_description_and_photo(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->assertSee('Kegiatan')
            ->assertSee('Uraian Kegiatan')
            ->assertSee('Ambil Foto')
            ->assertSee('Pilih File')
            ->assertDontSee('Jam Mulai')
            ->assertDontSee('Jam Selesai')
            ->assertDontSee('Hasil')
            ->assertDontSee('Kendala');
    }

    public function test_student_can_open_logbook_form_on_final_internship_day(): void
    {
        $this->travelTo(Carbon::parse('2026-12-31 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->assertSet('showModal', true);
    }

    public function test_student_can_edit_a_logbook_within_the_internship_period(): void
    {
        $student = $this->makeStudent();

        $logbook = Logbook::factory()->for($student)->create([
            'date' => '2026-11-08',
            'activity' => 'Aktivitas asli',
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openEdit', $logbook->id)
            ->assertSet('logbookId', $logbook->id)
            ->set('activity', 'Aktivitas baru')
            ->set('photo', UploadedFile::fake()->image('edit.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Aktivitas baru', $logbook->fresh()->activity);
    }

    public function test_student_can_open_a_missing_logbook_date(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreateForDate', '2026-11-05')
            ->assertSet('showModal', true)
            ->assertSet('date', '2026-11-05');
    }

    public function test_student_cannot_open_a_future_or_out_of_period_logbook_date(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreateForDate', '2026-11-10')
            ->assertSet('showModal', false);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreateForDate', '2026-10-31')
            ->assertSet('showModal', false);
    }

    public function test_opening_an_existing_missing_date_loads_it_for_editing(): void
    {
        $student = $this->makeStudent();

        $logbook = Logbook::factory()->for($student)->create([
            'date' => '2026-11-08',
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreateForDate', '2026-11-08')
            ->assertSet('showModal', true)
            ->assertSet('logbookId', $logbook->id);
    }

    public function test_student_cannot_create_two_logbooks_on_the_same_date(): void
    {
        $student = $this->makeStudent();

        Logbook::factory()->for($student)->create([
            'date' => '2026-11-09',
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->set('date', '2026-11-09')
            ->set('activity', 'Aktivitas lain')
            ->set('photo', UploadedFile::fake()->image('kegiatan.jpg'))
            ->call('save')
            ->assertHasErrors(['date']);
    }

    public function test_student_cannot_edit_a_logbook_after_internship_ends(): void
    {
        $this->travelTo(Carbon::parse('2027-01-05 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        $logbook = Logbook::factory()->for($student)->create([
            'date' => '2026-12-30',
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openEdit', $logbook->id)
            ->assertSet('logbookId', null)
            ->assertSet('showModal', false);
    }

    public function test_student_cannot_overwrite_a_logbook_after_internship_ends(): void
    {
        $this->travelTo(Carbon::parse('2027-01-05 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        $logbook = Logbook::factory()->for($student)->create([
            'date' => '2026-12-30',
            'activity' => 'Aktivitas asli',
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->set('logbookId', $logbook->id)
            ->set('date', '2026-12-30')
            ->set('activity', 'Aktivitas palsu')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Aktivitas asli', $logbook->fresh()->activity);
    }

    public function test_student_cannot_open_today_logbook_form_after_internship_ends(): void
    {
        $this->travelTo(Carbon::parse('2027-01-05 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->assertSet('showModal', false);
    }

    public function test_student_cannot_open_a_missing_logbook_date_after_internship_ends(): void
    {
        $this->travelTo(Carbon::parse('2027-01-05 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreateForDate', '2026-12-30')
            ->assertSet('showModal', false);
    }

    public function test_student_cannot_save_a_new_logbook_after_internship_ends(): void
    {
        $this->travelTo(Carbon::parse('2027-01-05 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->set('date', '2026-12-30')
            ->set('activity', 'Aktivitas baru')
            ->call('save');

        $this->assertDatabaseCount('logbooks', 0);
    }

    public function test_student_can_upload_a_logbook_photo(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->set('activity', 'Analisis data curah hujan')
            ->set('photo', UploadedFile::fake()->image('kegiatan.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $logbook = Logbook::query()->where('user_id', $student->id)->firstOrFail();

        $this->assertNotNull($logbook->attachment_path);
        Storage::disk('local')->assertExists($logbook->attachment_path);
    }

    public function test_replacing_a_logbook_photo_deletes_the_old_file(): void
    {
        $student = $this->makeStudent();
        $oldPath = 'logbook-photos/'.$student->id.'/old.jpg';
        Storage::disk('local')->put($oldPath, 'old-image');

        $logbook = Logbook::factory()->for($student)->create([
            'date' => '2026-11-08',
            'attachment_path' => $oldPath,
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openEdit', $logbook->id)
            ->set('photo', UploadedFile::fake()->image('baru.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $logbook->refresh();

        $this->assertNotSame($oldPath, $logbook->attachment_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($logbook->attachment_path);
    }

    public function test_student_keeps_existing_photo_when_editing_without_upload(): void
    {
        $student = $this->makeStudent();
        $path = 'logbook-photos/'.$student->id.'/old.jpg';
        Storage::disk('local')->put($path, 'old-image');

        $logbook = Logbook::factory()->for($student)->create([
            'date' => '2026-11-08',
            'attachment_path' => $path,
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openEdit', $logbook->id)
            ->set('activity', 'Aktivitas diperbarui')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Aktivitas diperbarui', $logbook->fresh()->activity);
        $this->assertSame($path, $logbook->fresh()->attachment_path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_student_cannot_save_a_logbook_without_a_photo(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->set('activity', 'Analisis data curah hujan')
            ->call('save')
            ->assertHasErrors(['photo']);

        $this->assertDatabaseCount('logbooks', 0);
    }

    public function test_logbook_photo_must_be_an_image(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->set('activity', 'Analisis data curah hujan')
            ->set('photo', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['photo']);

        $this->assertDatabaseMissing('logbooks', ['user_id' => $student->id]);
    }

    public function test_deleting_a_logbook_deletes_its_photo(): void
    {
        $student = $this->makeStudent();
        $path = 'logbook-photos/'.$student->id.'/old.jpg';
        Storage::disk('local')->put($path, 'old-image');

        $logbook = Logbook::factory()->for($student)->create([
            'date' => '2026-11-09',
            'attachment_path' => $path,
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('delete', $logbook->id);

        $this->assertDatabaseMissing('logbooks', ['id' => $logbook->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_student_logbook_history_is_paginated_ten_per_page(): void
    {
        $student = $this->makeStudent();

        foreach (range(1, 11) as $day) {
            Logbook::factory()->for($student)->create([
                'date' => sprintf('2026-11-%02d', $day),
            ]);
        }

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->assertViewHas('logbooks', fn ($logbooks) => $logbooks->perPage() === 10 && $logbooks->count() === 10);
    }

    public function test_deleting_last_logbook_on_the_last_page_moves_to_the_previous_page(): void
    {
        $this->travelTo(Carbon::parse('2026-12-30 10:00:00', 'Asia/Jakarta'));

        $student = $this->makeStudent();

        foreach (range(1, 11) as $day) {
            Logbook::factory()->for($student)->create([
                'date' => sprintf('2026-11-%02d', $day),
            ]);
        }

        $oldestLogbook = Logbook::query()
            ->where('user_id', $student->id)
            ->orderBy('date')
            ->firstOrFail();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('setPage', 2)
            ->call('delete', $oldestLogbook->id)
            ->assertViewHas('logbooks', fn ($logbooks) => $logbooks->currentPage() === 1);
    }

    public function test_admin_can_view_logbook_details_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->makeStudent();

        $logbook = Logbook::factory()->for($student)->create([
            'date' => '2026-11-09',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminLogbookIndex::class)
            ->call('openDetail', $logbook->id)
            ->assertSet('viewingId', $logbook->id)
            ->assertSee('Logbook hanya dapat dilihat.')
            ->assertDontSee('Setujui Logbook')
            ->assertDontSee('Tolak & Minta Revisi');
    }

    public function test_admin_logbook_search_changes_reset_the_date_filters(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminLogbookIndex::class)
            ->set('dateFrom', '2026-11-01')
            ->set('dateTo', '2026-11-30')
            ->set('search', 'Budi')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->set('dateFrom', '2026-11-01')
            ->set('dateTo', '2026-11-30')
            ->set('search', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '');
    }

    private function makeStudent(): User
    {
        $student = User::factory()->mahasiswa()->create();
        $student->internProfile()->create([
            'institution' => 'Universitas Jember',
            'major' => 'Teknik Informatika',
            'semester' => '6',
            'start_date' => Carbon::parse('2026-11-01'),
            'end_date' => Carbon::parse('2026-12-31'),
        ]);

        return $student;
    }
}
