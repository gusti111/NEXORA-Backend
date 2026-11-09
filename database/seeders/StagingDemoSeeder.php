<?php

namespace Database\Seeders;

use App\Models\Pkl;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class StagingDemoSeeder extends Seeder
{
    /**
     * Seed deterministic local integration accounts only.
     *
     * These credentials are intentionally public dummy credentials and must
     * never be created in a shared staging or production environment.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'StagingDemoSeeder hanya boleh dijalankan pada APP_ENV=local/testing.'
            );
        }

        $password = Hash::make('NexoraLocal!2026');

        $student = User::updateOrCreate(
            ['email' => 'student@nexora.local'],
            [
                'name' => 'NEXORA Demo Student',
                'nim' => 'LOCAL-NEXORA-001',
                'username' => 'nexora.student',
                'password' => $password,
                'role' => 'mahasiswa',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $lecturer = User::updateOrCreate(
            ['email' => 'lecturer@nexora.local'],
            [
                'name' => 'NEXORA Demo Lecturer',
                'nim' => 'LOCAL-NEXORA-002',
                'username' => 'nexora.lecturer',
                'password' => $password,
                'role' => 'dosen',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $coordinator = User::updateOrCreate(
            ['email' => 'coordinator@nexora.local'],
            [
                'name' => 'NEXORA Demo Coordinator',
                'nim' => 'LOCAL-NEXORA-003',
                'username' => 'nexora.korlap',
                'password' => $password,
                'role' => 'korlap',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $pkl = Pkl::updateOrCreate(
            [
                'mahasiswa_id' => $student->id,
                'nama_instansi' => 'NEXORA Local Staging Lab',
            ],
            [
                'dosen_id' => $lecturer->id,
                'korlap_id' => $coordinator->id,
                'alamat_instansi' => 'Local integration test environment',
                'tanggal_mulai' => today()->subDays(7)->toDateString(),
                'tanggal_selesai' => today()->addDays(30)->toDateString(),
                'status' => 'berlangsung',
            ]
        );

        $this->command?->info('NEXORA local integration accounts are ready.');
        $this->command?->line('Student: student@nexora.local / NexoraLocal!2026');
        $this->command?->line('Lecturer: lecturer@nexora.local / NexoraLocal!2026');
        $this->command?->line('Coordinator: coordinator@nexora.local / NexoraLocal!2026');
        $this->command?->line('Active PKL ID: '.$pkl->id);
        $this->command?->warn('Dummy credentials are for local development only. Never expose this database publicly.');
    }
}
