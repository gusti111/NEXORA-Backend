<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Pkl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    private function makePkl(User $mahasiswa, User $dosen, User $korlap): Pkl
    {
        return Pkl::create([
            'mahasiswa_id' => $mahasiswa->id,
            'dosen_id' => $dosen->id,
            'korlap_id' => $korlap->id,
            'nama_instansi' => 'Unit Pengujian NEXORA',
            'alamat_instansi' => 'Pontianak',
            'tanggal_mulai' => now()->subDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(20)->toDateString(),
            'status' => 'berlangsung',
        ]);
    }

    public function test_each_supported_role_can_authenticate(): void
    {
        foreach (['mahasiswa', 'dosen', 'korlap', 'admin'] as $role) {
            $user = $this->makeUser($role);

            $this->postJson('/api/login', [
                'login' => $user->email,
                'password' => 'password',
            ])
                ->assertOk()
                ->assertJsonPath('user.id', $user->id)
                ->assertJsonPath('user.role', $role)
                ->assertJsonStructure(['token', 'user']);
        }
    }

    public function test_authenticated_user_endpoint_returns_a_consistent_data_envelope(): void
    {
        $user = $this->makeUser('mahasiswa');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'mahasiswa');
    }

    public function test_role_middleware_rejects_users_outside_the_required_role(): void
    {
        $cases = [
            ['mahasiswa', '/api/admin/statistics'],
            ['dosen', '/api/korlap/statistics'],
            ['korlap', '/api/dosen/logbooks'],
            ['admin', '/api/logbooks'],
        ];

        foreach ($cases as [$role, $endpoint]) {
            $user = $this->makeUser($role);

            $this->actingAs($user, 'sanctum')
                ->getJson($endpoint)
                ->assertForbidden();
        }
    }

    public function test_inactive_account_cannot_access_the_current_user_endpoint(): void
    {
        $user = $this->makeUser('mahasiswa', ['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun kamu sedang dinonaktifkan. Hubungi Admin.');
    }

    public function test_logout_revokes_the_current_sanctum_token(): void
    {
        $user = $this->makeUser('mahasiswa');
        $issuedToken = $user->createToken('nexora-test');
        $plainTextToken = $issuedToken->plainTextToken;

        $this->withToken($plainTextToken)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $issuedToken->accessToken->getKey(),
        ]);

        // Reset the guard between requests so the second request resolves
        // authentication from the now-revoked token, not a cached test guard.
        Auth::forgetGuards();

        $this->withToken($plainTextToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_admin_password_reset_revokes_all_tokens_for_the_target_user(): void
    {
        $admin = $this->makeUser('admin');
        $target = $this->makeUser('mahasiswa');
        $target->createToken('mobile-device');
        $target->createToken('second-device');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/users/'.$target->id.'/reset-password', [
                'password' => 'new-password-123',
            ])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(Hash::check(
            'new-password-123',
            $target->fresh()->password,
        ));
    }

    public function test_account_with_pkl_history_cannot_be_deleted(): void
    {
        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $admin = $this->makeUser('admin');
        $this->makePkl($student, $lecturer, $coordinator);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/users/'.$student->id)
            ->assertUnprocessable();

        $this->assertDatabaseHas('users', ['id' => $student->id]);
        $this->assertDatabaseHas('pkls', ['mahasiswa_id' => $student->id]);
    }

    public function test_student_logbook_list_is_scoped_to_the_authenticated_student(): void
    {
        $studentA = $this->makeUser('mahasiswa');
        $studentB = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');

        $pklA = $this->makePkl($studentA, $lecturer, $coordinator);
        $pklB = $this->makePkl($studentB, $lecturer, $coordinator);

        $logbookA = Logbook::create([
            'pkl_id' => $pklA->id,
            'tanggal_kegiatan' => now()->toDateString(),
            'judul_kegiatan' => 'Logbook milik A',
            'deskripsi_kegiatan' => 'Fixture logbook untuk mahasiswa A.',
            'status' => 'pending',
        ]);

        Logbook::create([
            'pkl_id' => $pklB->id,
            'tanggal_kegiatan' => now()->toDateString(),
            'judul_kegiatan' => 'Logbook milik B',
            'deskripsi_kegiatan' => 'Fixture logbook untuk mahasiswa B.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($studentA, 'sanctum')
            ->getJson('/api/logbooks')
            ->assertOk();

        $this->assertSame(
            [$logbookA->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_lecturer_cannot_review_a_logbook_outside_their_assignments(): void
    {
        $student = $this->makeUser('mahasiswa');
        $assignedLecturer = $this->makeUser('dosen');
        $otherLecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $pkl = $this->makePkl($student, $assignedLecturer, $coordinator);

        $logbook = Logbook::create([
            'pkl_id' => $pkl->id,
            'tanggal_kegiatan' => now()->toDateString(),
            'judul_kegiatan' => 'Review terbatas',
            'deskripsi_kegiatan' => 'Hanya dosen pembimbing yang boleh memeriksa.',
            'status' => 'pending',
        ]);

        $this->actingAs($otherLecturer, 'sanctum')
            ->patchJson('/api/dosen/logbooks/'.$logbook->id.'/review', [
                'status' => 'approved',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'status' => 'pending',
        ]);
    }
}
