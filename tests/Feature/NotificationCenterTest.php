<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Pkl;
use App\Models\User;
use App\Notifications\LogbookReviewed;
use App\Notifications\StudentLogbookSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makePkl(User $student, User $lecturer, User $coordinator): Pkl
    {
        return Pkl::create([
            'mahasiswa_id' => $student->id,
            'dosen_id' => $lecturer->id,
            'korlap_id' => $coordinator->id,
            'nama_instansi' => 'Unit Notifikasi NEXORA',
            'alamat_instansi' => 'Pontianak',
            'tanggal_mulai' => now()->subDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDays(15)->toDateString(),
            'status' => 'berlangsung',
        ]);
    }

    private function makeLogbook(Pkl $pkl): Logbook
    {
        return Logbook::create([
            'pkl_id' => $pkl->id,
            'tanggal_kegiatan' => now()->toDateString(),
            'judul_kegiatan' => 'Pemeriksaan jaringan',
            'deskripsi_kegiatan' => 'Pengujian endpoint notifikasi.',
            'status' => 'pending',
        ]);
    }

    public function test_logbook_submission_notifies_the_assigned_lecturer(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/logbooks', [
                'pkl_id' => $pkl->id,
                'idempotency_key' => 'd22a3f0a-8d9a-4a94-a20c-0a25a60d1001',
                'tanggal_kegiatan' => now()->toDateString(),
                'judul_kegiatan' => 'Pemeriksaan jaringan',
                'deskripsi_kegiatan' => 'Memeriksa konfigurasi switch.',
            ])
            ->assertCreated();

        $logbookId = $response->json('data.id');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $lecturer->id,
            'type' => StudentLogbookSubmitted::class,
        ]);

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbookId,
            'idempotency_key' => 'd22a3f0a-8d9a-4a94-a20c-0a25a60d1001',
        ]);
    }

    public function test_replayed_submission_idempotency_key_does_not_create_duplicate_logbook_or_notification(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);

        $payload = [
            'pkl_id' => $pkl->id,
            'idempotency_key' => '9bb1f8f4-4b58-4c98-a2e1-8aa6d28b1002',
            'tanggal_kegiatan' => now()->toDateString(),
            'judul_kegiatan' => 'Pemeriksaan jaringan',
            'deskripsi_kegiatan' => 'Memeriksa konfigurasi switch.',
        ];

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/logbooks', $payload)
            ->assertCreated();

        // The replay must return the original record even if the placement
        // has since been completed after the first request reached the server.
        $pkl->update(['status' => 'selesai']);

        $this->postJson('/api/logbooks', $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('logbooks', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_logbook_review_notifies_the_student(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        $this->actingAs($lecturer, 'sanctum')
            ->patchJson('/api/dosen/logbooks/'.$logbook->id.'/review', [
                'status' => 'approved',
                'komentar' => 'Sudah sesuai.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->id,
            'type' => LogbookReviewed::class,
        ]);
    }

    public function test_user_can_list_and_mark_only_their_own_notifications_as_read(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $otherStudent = $this->makeUser('mahasiswa');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        $student->notify(new LogbookReviewed($logbook));
        $otherStudent->notify(new LogbookReviewed($logbook));

        $notificationId = $student->notifications()->firstOrFail()->id;

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data');

        $this->patchJson('/api/notifications/'.$notificationId.'/read')
            ->assertOk();

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->assertDatabaseHas('notifications', [
            'id' => $otherStudent->notifications()->firstOrFail()->id,
            'read_at' => null,
        ]);
    }

    public function test_user_can_mark_all_their_unread_notifications_as_read(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        $student->notify(new LogbookReviewed($logbook));
        $student->notify(new LogbookReviewed($logbook));

        $this->actingAs($student, 'sanctum')
            ->patchJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.updated', 2);

        $this->assertSame(0, $student->unreadNotifications()->count());
    }
}
