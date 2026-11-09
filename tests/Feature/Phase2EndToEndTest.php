<?php

namespace Tests\Feature;

use App\Models\Pkl;
use App\Models\User;
use App\Notifications\LogbookReviewed;
use App\Notifications\StudentLogbookSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class Phase2EndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_submission_idempotent_replay_lecturer_review_and_student_notification_flow(): void
    {
        $student = User::factory()->create([
            'name' => 'E2E Student',
            'email' => 'e2e.student@example.test',
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $lecturer = User::factory()->create([
            'name' => 'E2E Lecturer',
            'email' => 'e2e.lecturer@example.test',
            'role' => 'dosen',
            'is_active' => true,
        ]);

        $coordinator = User::factory()->create([
            'name' => 'E2E Coordinator',
            'email' => 'e2e.coordinator@example.test',
            'role' => 'korlap',
            'is_active' => true,
        ]);

        $pkl = Pkl::create([
            'mahasiswa_id' => $student->id,
            'dosen_id' => $lecturer->id,
            'korlap_id' => $coordinator->id,
            'nama_instansi' => 'NEXORA E2E Test Lab',
            'alamat_instansi' => 'Local automated test environment',
            'tanggal_mulai' => now()->subDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDays(20)->toDateString(),
            'status' => 'berlangsung',
        ]);

        // Student obtains a real Sanctum token through the login endpoint.
        $studentLogin = $this->postJson('/api/login', [
            'login' => $student->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.role', 'mahasiswa');

        $studentToken = $studentLogin->json('token');
        $this->assertNotEmpty($studentToken);

        $payload = [
            'pkl_id' => $pkl->id,
            'idempotency_key' => 'b1caa2e0-9c7d-4d6e-9a0d-c3afbded1001',
            'tanggal_kegiatan' => now()->toDateString(),
            'judul_kegiatan' => 'E2E pemeriksaan jaringan',
            'deskripsi_kegiatan' => 'Menguji alur kirim, replay, notifikasi, dan review.',
        ];

        $created = $this->flushHeaders()->withToken($studentToken)
            ->postJson('/api/logbooks', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $logbookId = $created->json('data.id');
        $this->assertNotEmpty($logbookId);

        // A lost response followed by a retry must reconcile to the same row.
        $this->postJson('/api/logbooks', $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('data.id', $logbookId);

        $this->assertDatabaseCount('logbooks', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $lecturer->id,
            'type' => StudentLogbookSubmitted::class,
        ]);

        // Lecturer logs in through the same public auth endpoint.
        $lecturerLogin = $this->postJson('/api/login', [
            'login' => $lecturer->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.role', 'dosen');

        $lecturerToken = $lecturerLogin->json('token');
        $this->assertNotEmpty($lecturerToken);

        // The test application instance is reused across requests. Flush the
        // cached guard so this request authenticates from the lecturer token,
        // as a separate real HTTP request would.
        Auth::forgetGuards();

        // Verify that the second bearer token resolves to the lecturer before
        // checking the notification inbox.
        $this->flushHeaders()->withToken($lecturerToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $lecturer->id);

        $lecturerNotification = $lecturer->notifications()->firstOrFail();
        $this->assertNull($lecturerNotification->read_at);
        $this->assertSame(1, $lecturer->unreadNotifications()->count());

        $lecturerInbox = $this->flushHeaders()->withToken($lecturerToken)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame(
            1,
            $lecturerInbox->json('unread_count'),
            json_encode($lecturerInbox->json())
        );

        $this->assertSame(
            'logbook_submitted',
            $lecturerInbox->json('data.0.data.event')
        );
        $this->assertSame(
            $logbookId,
            $lecturerInbox->json('data.0.data.logbook_id')
        );

        // The assigned lecturer reviews the logbook.
        $this->flushHeaders()->withToken($lecturerToken)
            ->patchJson('/api/dosen/logbooks/'.$logbookId.'/review', [
                'status' => 'approved',
                'komentar' => 'E2E review approved.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        // A second review must not create a second student notification.
        $this->patchJson('/api/dosen/logbooks/'.$logbookId.'/review', [
            'status' => 'approved',
            'komentar' => 'Duplicate review must be rejected.',
        ])->assertStatus(422);

        $this->assertDatabaseCount('logbook_reviews', 1);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $student->id,
            'type' => LogbookReviewed::class,
        ]);

        // Switch the reused test application back to the student's bearer.
        Auth::forgetGuards();

        // Student receives and acknowledges the review notification.
        $studentInbox = $this->flushHeaders()->withToken($studentToken)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data');

        $studentNotificationId = $studentInbox->json('data.0.id');

        $this->patchJson('/api/notifications/'.$studentNotificationId.'/read')
            ->assertOk();

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbookId,
            'status' => 'approved',
        ]);
        $this->assertDatabaseCount('logbooks', 1);
        $this->assertDatabaseCount('notifications', 2);
    }
}
