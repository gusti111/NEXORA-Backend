<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Pkl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LogbookPhotoSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makeLogbook(
        User $student,
        User $lecturer,
        User $coordinator,
        string $photoPath = 'logbooks/private-test.jpg',
    ): Logbook {
        $pkl = Pkl::create([
            'mahasiswa_id' => $student->id,
            'dosen_id' => $lecturer->id,
            'korlap_id' => $coordinator->id,
            'nama_instansi' => 'Unit Pengujian NEXORA',
            'alamat_instansi' => 'Pontianak',
            'tanggal_mulai' => now()->subDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(20)->toDateString(),
            'status' => 'berlangsung',
        ]);

        return Logbook::create([
            'pkl_id' => $pkl->id,
            'tanggal_kegiatan' => now()->toDateString(),
            'judul_kegiatan' => 'Dokumentasi kegiatan',
            'deskripsi_kegiatan' => 'Foto privat untuk pengujian otorisasi.',
            'foto' => $photoPath,
            'status' => 'pending',
        ]);
    }

    public function test_logbook_photo_requires_authentication(): void
    {
        $this->getJson('/api/logbooks/1/photo')->assertUnauthorized();
    }

    public function test_student_can_fetch_a_photo_from_their_own_logbook(): void
    {
        Storage::fake('local');

        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook($student, $lecturer, $coordinator);

        Storage::disk('local')->put($logbook->foto, 'private-jpeg-fixture');

        $this->actingAs($student, 'sanctum')
            ->get('/api/logbooks/'.$logbook->id.'/photo')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertStreamedContent('private-jpeg-fixture');
    }

    public function test_assigned_lecturer_can_fetch_the_students_logbook_photo(): void
    {
        Storage::fake('local');

        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook($student, $lecturer, $coordinator);

        Storage::disk('local')->put($logbook->foto, 'lecturer-photo-fixture');

        $this->actingAs($lecturer, 'sanctum')
            ->get('/api/logbooks/'.$logbook->id.'/photo')
            ->assertOk()
            ->assertStreamedContent('lecturer-photo-fixture');
    }

    public function test_assigned_coordinator_can_fetch_the_students_logbook_photo(): void
    {
        Storage::fake('local');

        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook($student, $lecturer, $coordinator);

        Storage::disk('local')->put($logbook->foto, 'coordinator-photo-fixture');

        $this->actingAs($coordinator, 'sanctum')
            ->get('/api/logbooks/'.$logbook->id.'/photo')
            ->assertOk()
            ->assertStreamedContent('coordinator-photo-fixture');
    }

    public function test_admin_can_fetch_a_logbook_photo_for_audit(): void
    {
        Storage::fake('local');

        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $admin = $this->makeUser('admin');
        $logbook = $this->makeLogbook($student, $lecturer, $coordinator);

        Storage::disk('local')->put($logbook->foto, 'admin-photo-fixture');

        $this->actingAs($admin, 'sanctum')
            ->get('/api/logbooks/'.$logbook->id.'/photo')
            ->assertOk()
            ->assertStreamedContent('admin-photo-fixture');
    }

    public function test_unassigned_student_cannot_fetch_another_students_photo(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser('mahasiswa');
        $otherStudent = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook($owner, $lecturer, $coordinator);

        Storage::disk('local')->put($logbook->foto, 'owner-only-photo');

        $this->actingAs($otherStudent, 'sanctum')
            ->get('/api/logbooks/'.$logbook->id.'/photo')
            ->assertNotFound();
    }

    public function test_unassigned_lecturer_cannot_fetch_the_photo(): void
    {
        Storage::fake('local');

        $student = $this->makeUser('mahasiswa');
        $assignedLecturer = $this->makeUser('dosen');
        $otherLecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook($student, $assignedLecturer, $coordinator);

        Storage::disk('local')->put($logbook->foto, 'assigned-lecturer-photo');

        $this->actingAs($otherLecturer, 'sanctum')
            ->get('/api/logbooks/'.$logbook->id.'/photo')
            ->assertNotFound();
    }

    public function test_photo_endpoint_rejects_paths_outside_the_logbooks_directory(): void
    {
        Storage::fake('local');

        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook(
            $student,
            $lecturer,
            $coordinator,
            'logbooks/../../.env',
        );

        $this->actingAs($student, 'sanctum')
            ->get('/api/logbooks/'.$logbook->id.'/photo')
            ->assertNotFound();
    }
    public function test_photo_endpoint_returns_not_found_when_private_file_is_missing(): void
    {
        Storage::fake('local');

        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook($student, $lecturer, $coordinator);

        $this->actingAs($student, 'sanctum')
            ->get('/api/logbooks/'.$logbook->id.'/photo')
            ->assertNotFound();
    }

    public function test_legacy_public_photo_can_be_migrated_to_private_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook($student, $lecturer, $coordinator);
        $contents = 'legacy-public-photo-fixture';

        Storage::disk('public')->put($logbook->foto, $contents);

        $this->artisan('nexora:migrate-logbook-photos-to-private')
            ->assertExitCode(0);

        Storage::disk('local')->assertExists($logbook->foto);
        Storage::disk('public')->assertMissing($logbook->foto);
        $this->assertSame($contents, Storage::disk('local')->get($logbook->foto));
    }

    public function test_photo_migration_dry_run_does_not_change_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $student = $this->makeUser('mahasiswa');
        $lecturer = $this->makeUser('dosen');
        $coordinator = $this->makeUser('korlap');
        $logbook = $this->makeLogbook($student, $lecturer, $coordinator);

        Storage::disk('public')->put($logbook->foto, 'dry-run-photo-fixture');

        $this->artisan('nexora:migrate-logbook-photos-to-private', ['--dry-run' => true])
            ->assertExitCode(0);

        Storage::disk('public')->assertExists($logbook->foto);
        Storage::disk('local')->assertMissing($logbook->foto);
    }
}
