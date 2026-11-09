<?php

namespace Tests\Unit;

use App\Models\Logbook;
use Tests\TestCase;

class LogbookMassAssignmentTest extends TestCase
{
    public function test_logbook_accepts_activity_title_during_mass_assignment(): void
    {
        $logbook = new Logbook([
            'judul_kegiatan' => 'Pemeriksaan perangkat jaringan',
            'deskripsi_kegiatan' => 'Melakukan pemeriksaan perangkat jaringan di lokasi PKL.',
        ]);

        $this->assertSame('Pemeriksaan perangkat jaringan', $logbook->judul_kegiatan);
    }
}
