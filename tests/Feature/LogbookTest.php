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

    public function test_student_can_save_a_logbook_as_draft(): void
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
            ->call('saveDraft')
            ->assertHasNoErrors();

        $logbook = Logbook::query()->where('user_id', $student->id)->firstOrFail();

        $this->assertSame('2026-11-09', $logbook->date->toDateString());
        $this->assertSame('Analisis data curah hujan', $logbook->activity);
        $this->assertSame(LogbookStatus::Draft, $logbook->status);
    }

    public function test_student_can_submit_a_logbook_for_review(): void
    {
        $student = $this->makeStudent();

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openCreate')
            ->set('date', '2026-11-09')
            ->set('activity', 'Penyusunan laporan mingguan')
            ->call('submit')
            ->assertHasNoErrors();

        $logbook = Logbook::query()->where('user_id', $student->id)->firstOrFail();

        $this->assertSame('2026-11-09', $logbook->date->toDateString());
        $this->assertSame(LogbookStatus::Submitted, $logbook->status);
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
            ->call('submit')
            ->assertHasErrors(['date']);
    }

    public function test_student_cannot_edit_a_submitted_logbook(): void
    {
        $student = $this->makeStudent();

        $logbook = Logbook::factory()->submitted()->for($student)->create([
            'date' => '2026-11-09',
        ]);

        Livewire::actingAs($student)
            ->test(StudentLogbookIndex::class)
            ->call('openEdit', $logbook->id)
            ->assertSet('logbookId', null)
            ->assertSet('showModal', false);
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
