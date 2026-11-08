<?php

namespace Tests\Feature;

use App\Livewire\Admin\Announcements\Index as AnnouncementIndex;
use App\Models\Announcement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));
    }

    public function test_admin_can_create_an_announcement(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AnnouncementIndex::class)
            ->call('openCreate')
            ->set('title', 'Jadwal Apel Senin')
            ->set('body', 'Apel dilaksanakan pukul 07.30 di halaman kantor.')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $announcement = Announcement::query()->firstOrFail();

        $this->assertSame('Jadwal Apel Senin', $announcement->title);
        $this->assertTrue($announcement->is_active);
        $this->assertSame($admin->id, $announcement->created_by);
        $this->assertNotNull($announcement->published_at);
    }

    public function test_admin_can_update_an_announcement(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create([
            'title' => 'Judul Lama',
            'body' => 'Isi lama.',
        ]);

        Livewire::actingAs($admin)
            ->test(AnnouncementIndex::class)
            ->call('openEdit', $announcement->id)
            ->assertSet('title', 'Judul Lama')
            ->set('title', 'Judul Baru')
            ->set('body', 'Isi baru.')
            ->call('save')
            ->assertHasNoErrors();

        $announcement->refresh();

        $this->assertSame('Judul Baru', $announcement->title);
        $this->assertSame('Isi baru.', $announcement->body);
    }

    public function test_admin_can_toggle_and_delete_an_announcement(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create(['is_active' => true]);

        Livewire::actingAs($admin)
            ->test(AnnouncementIndex::class)
            ->call('toggleActive', $announcement->id);

        $this->assertFalse($announcement->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test(AnnouncementIndex::class)
            ->call('delete', $announcement->id);

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_announcement_requires_title_and_body(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AnnouncementIndex::class)
            ->set('title', '')
            ->set('body', '')
            ->call('save')
            ->assertHasErrors(['title', 'body']);
    }
}
