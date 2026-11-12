<?php

namespace Tests\Feature;

use App\Enums\LogbookStatus;
use App\Livewire\Admin\Logbooks\Index as AdminLogbookIndex;
use App\Livewire\Mahasiswa\Logbooks\Index as StudentLogbookIndex;
use App\Models\Logbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogbookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));
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

        $logbook = Logbook::factory()->submitted()->for($student)->create([
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
            'status' => LogbookStatus::Draft,
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

    public function test_admin_can_approve_a_submitted_logbook_and_notify_the_student(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->makeStudent();

        $logbook = Logbook::factory()->submitted()->for($student)->create([
            'date' => '2026-11-09',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminLogbookIndex::class)
            ->call('openReview', $logbook->id)
            ->set('reviewNote', 'Logbook sudah lengkap.')
            ->call('approve', $logbook->id)
            ->assertHasNoErrors();

        $logbook->refresh();

        $this->assertSame(LogbookStatus::Approved, $logbook->status);
        $this->assertSame($admin->id, $logbook->reviewed_by);
        $this->assertNotNull($logbook->reviewed_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_admin_must_add_a_note_when_rejecting_a_logbook(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->makeStudent();

        $logbook = Logbook::factory()->submitted()->for($student)->create([
            'date' => '2026-11-09',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminLogbookIndex::class)
            ->call('openReview', $logbook->id)
            ->set('reviewNote', '')
            ->call('reject', $logbook->id)
            ->assertHasErrors(['reviewNote']);

        $this->assertSame(LogbookStatus::Submitted, $logbook->fresh()->status);
    }

    public function test_admin_can_reject_a_logbook_with_a_revision_note(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->makeStudent();

        $logbook = Logbook::factory()->submitted()->for($student)->create([
            'date' => '2026-11-09',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminLogbookIndex::class)
            ->call('openReview', $logbook->id)
            ->set('reviewNote', 'Tambahkan detail kegiatan.')
            ->call('reject', $logbook->id)
            ->assertHasNoErrors();

        $logbook->refresh();

        $this->assertSame(LogbookStatus::Rejected, $logbook->status);
        $this->assertSame('Tambahkan detail kegiatan.', $logbook->review_note);
        $this->assertSame($admin->id, $logbook->reviewed_by);
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
