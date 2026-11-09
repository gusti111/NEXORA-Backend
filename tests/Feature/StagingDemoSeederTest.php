<?php

namespace Tests\Feature;

use App\Models\Pkl;
use App\Models\User;
use Database\Seeders\StagingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StagingDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_staging_demo_seeder_creates_dummy_roles_and_one_active_pkl(): void
    {
        $this->seed(StagingDemoSeeder::class);

        $student = User::where('email', 'student@nexora.local')->firstOrFail();
        $lecturer = User::where('email', 'lecturer@nexora.local')->firstOrFail();
        $coordinator = User::where('email', 'coordinator@nexora.local')->firstOrFail();

        $this->assertSame('mahasiswa', $student->role);
        $this->assertSame('dosen', $lecturer->role);
        $this->assertSame('korlap', $coordinator->role);
        $this->assertTrue($student->is_active);
        $this->assertTrue(Hash::check('NexoraLocal!2026', $student->password));

        $pkl = Pkl::where('mahasiswa_id', $student->id)->firstOrFail();

        $this->assertSame($lecturer->id, $pkl->dosen_id);
        $this->assertSame($coordinator->id, $pkl->korlap_id);
        $this->assertSame('berlangsung', $pkl->status);
        $this->assertSame(3, User::count());
        $this->assertSame(1, Pkl::count());
    }

    public function test_staging_demo_seeder_is_idempotent(): void
    {
        $this->seed(StagingDemoSeeder::class);
        $firstPklId = Pkl::query()->value('id');

        $this->seed(StagingDemoSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(1, Pkl::count());
        $this->assertSame($firstPklId, Pkl::query()->value('id'));
    }
}
