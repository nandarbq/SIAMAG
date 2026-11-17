<?php

namespace Tests\Feature;

use App\Livewire\Admin\Logbooks\Index as AdminLogbookIndex;
use App\Livewire\Mahasiswa\Logbooks\Index as StudentLogbookIndex;
use App\Models\Logbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ->set('date', '2026-11-09')
            ->set('activity', 'Analisis data curah hujan')
            ->set('description', 'Mengolah data observasi harian.')
            ->set('startTime', '08:00')
            ->set('endTime', '10:00')
            ->call('save')
            ->assertHasNoErrors();

        $logbook = Logbook::query()->where('user_id', $student->id)->firstOrFail();

        $this->assertSame('2026-11-09', $logbook->date->toDateString());
        $this->assertSame('Analisis data curah hujan', $logbook->activity);
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

    public function test_student_can_upload_a_logbook_photo(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->set('date', '2026-11-09')
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

    public function test_student_can_remove_a_logbook_photo(): void
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
            ->call('removePhoto')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($logbook->fresh()->attachment_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_logbook_photo_must_be_an_image(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->set('date', '2026-11-09')
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
