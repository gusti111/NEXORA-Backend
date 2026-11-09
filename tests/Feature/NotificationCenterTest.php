<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Pkl;
use App\Models\User;
use App\Notifications\LogbookReviewed;
use App\Notifications\StudentLogbookSubmitted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_idempotency_key_cannot_be_reused_for_a_different_pkl(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $firstPkl = $this->makePkl($student, $lecturer, $coordinator);
        $secondPkl = $this->makePkl($student, $lecturer, $coordinator);

        $payload = [
            'idempotency_key' => '4a8dd865-24df-4d22-a9d1-6a8f75d4c110',
            'tanggal_kegiatan' => now()->toDateString(),
            'judul_kegiatan' => 'Pemeriksaan jaringan',
            'deskripsi_kegiatan' => 'Memeriksa konfigurasi switch.',
        ];

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/logbooks', $payload + ['pkl_id' => $firstPkl->id])
            ->assertCreated();

        $this->postJson('/api/logbooks', $payload + ['pkl_id' => $secondPkl->id])
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Kunci pengiriman sudah digunakan oleh data lain.'
            );

        $this->assertDatabaseCount('logbooks', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_submission_rolls_back_when_lecturer_notification_persistence_fails(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);

        DB::statement(<<<'SQL'
            CREATE TRIGGER block_notification_insert
            BEFORE INSERT ON notifications
            BEGIN
                SELECT RAISE(ABORT, 'notification persistence unavailable');
            END;
        SQL);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($student, 'sanctum')
                ->postJson('/api/logbooks', [
                    'pkl_id' => $pkl->id,
                    'idempotency_key' => '2a31bd8a-8e5b-43c0-9bdb-3b9ec0c11001',
                    'tanggal_kegiatan' => now()->toDateString(),
                    'judul_kegiatan' => 'Pemeriksaan jaringan',
                    'deskripsi_kegiatan' => 'Menguji rollback transaksi.',
                ]);

            $this->fail('The notification insert should fail.');
        } catch (QueryException) {
            // Expected: the transaction must roll back both writes.
        }

        $this->assertDatabaseCount('logbooks', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_review_rolls_back_status_and_history_when_student_notification_fails(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        DB::statement(<<<'SQL'
            CREATE TRIGGER block_notification_insert
            BEFORE INSERT ON notifications
            BEGIN
                SELECT RAISE(ABORT, 'notification persistence unavailable');
            END;
        SQL);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($lecturer, 'sanctum')
                ->patchJson('/api/dosen/logbooks/'.$logbook->id.'/review', [
                    'status' => 'approved',
                    'komentar' => 'Sudah sesuai.',
                ]);

            $this->fail('The notification insert should fail.');
        } catch (QueryException) {
            // Expected: review status, history, and notification roll back.
        }

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('logbook_reviews', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_logbook_resubmission_notifies_the_assigned_lecturer_and_resets_review_state(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        $logbook->update([
            'status' => 'revision_requested',
            'catatan_dosen' => 'Mohon lengkapi hasil kegiatan.',
        ]);

        $this->actingAs($student, 'sanctum')
            ->putJson('/api/logbooks/'.$logbook->id.'/resubmit', [
                'judul_kegiatan' => 'Pemeriksaan jaringan - revisi',
                'deskripsi_kegiatan' => 'Deskripsi telah dilengkapi sesuai masukan dosen.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.catatan_dosen', null)
            ->assertJsonPath('data.judul_kegiatan', 'Pemeriksaan jaringan - revisi');

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'status' => 'pending',
            'catatan_dosen' => null,
            'judul_kegiatan' => 'Pemeriksaan jaringan - revisi',
        ]);

        $this->assertDatabaseCount('notifications', 1);
        $notification = $lecturer->notifications()->firstOrFail();

        $this->assertSame(StudentLogbookSubmitted::class, $notification->type);
        $this->assertSame('logbook_submitted', $notification->data['event']);
        $this->assertSame('resubmission', $notification->data['submission_type']);
        $this->assertSame(
            'Logbook revisi menunggu pemeriksaan',
            $notification->data['title']
        );
    }

    public function test_resubmission_rolls_back_logbook_changes_when_lecturer_notification_fails(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        $logbook->update([
            'status' => 'revision_requested',
            'catatan_dosen' => 'Mohon lengkapi hasil kegiatan.',
        ]);

        DB::statement(<<<'SQL'
            CREATE TRIGGER block_notification_insert
            BEFORE INSERT ON notifications
            BEGIN
                SELECT RAISE(ABORT, 'notification persistence unavailable');
            END;
        SQL);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($student, 'sanctum')
                ->putJson('/api/logbooks/'.$logbook->id.'/resubmit', [
                    'judul_kegiatan' => 'Judul hasil revisi',
                    'deskripsi_kegiatan' => 'Deskripsi yang sudah diperbaiki.',
                ]);

            $this->fail('The lecturer notification insert should fail.');
        } catch (QueryException) {
            // Expected: the logbook update must roll back with the notification.
        }

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'status' => 'revision_requested',
            'catatan_dosen' => 'Mohon lengkapi hasil kegiatan.',
            'judul_kegiatan' => 'Pemeriksaan jaringan',
            'deskripsi_kegiatan' => 'Pengujian endpoint notifikasi.',
        ]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_student_cannot_resubmit_another_students_logbook(): void
    {
        $owner = $this->makeUser('mahasiswa');
        $otherStudent = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($owner, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        $logbook->update(['status' => 'revision_requested']);

        $this->actingAs($otherStudent, 'sanctum')
            ->putJson('/api/logbooks/'.$logbook->id.'/resubmit', [
                'judul_kegiatan' => 'Percobaan akses tidak sah',
                'deskripsi_kegiatan' => 'Mahasiswa lain tidak boleh mengubah logbook ini.',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'status' => 'revision_requested',
            'judul_kegiatan' => 'Pemeriksaan jaringan',
        ]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_logbook_resubmission_requires_revision_requested_status(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        $this->actingAs($student, 'sanctum')
            ->putJson('/api/logbooks/'.$logbook->id.'/resubmit', [
                'judul_kegiatan' => 'Judul yang tidak boleh diterapkan',
                'deskripsi_kegiatan' => 'Status logbook masih pending.',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'status' => 'pending',
            'judul_kegiatan' => 'Pemeriksaan jaringan',
        ]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_logbook_resubmission_rejects_requests_without_actual_changes(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $lecturer, $coordinator);
        $logbook = $this->makeLogbook($pkl);

        $logbook->update([
            'status' => 'revision_requested',
            'catatan_dosen' => 'Mohon periksa kembali.',
        ]);

        $this->actingAs($student, 'sanctum')
            ->putJson('/api/logbooks/'.$logbook->id.'/resubmit', [
                'judul_kegiatan' => 'Pemeriksaan jaringan',
                'deskripsi_kegiatan' => 'Pengujian endpoint notifikasi.',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Tidak ada perubahan. Silakan perbaiki minimal satu bagian logbook sebelum dikirim ulang.'
            );

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'status' => 'revision_requested',
            'catatan_dosen' => 'Mohon periksa kembali.',
        ]);
        $this->assertDatabaseCount('notifications', 0);
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
