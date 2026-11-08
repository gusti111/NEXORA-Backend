<?php

namespace App\Exports;

use App\Models\Logbook;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class KorlapLogbookExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $korlapId;

    public function __construct($korlapId)
    {
        $this->korlapId = $korlapId;
    }

    // MENGAMBIL DATA LOGBOOK BERDASARKAN KORLAP
    public function collection(): Collection
    {
        return Logbook::whereHas('pkl', function ($query) {
            $query->where('korlap_id', $this->korlapId);
        })
        ->with([
            'pkl.mahasiswa:id,name,nim',
            'pkl.dosen:id,name',
        ])
        ->latest()
        ->get()
        ->map(function ($logbook) {
            return [
                'ID Logbook' => $logbook->id,
                'NIM' => $logbook->pkl->mahasiswa->nim ?? '-',
                'Nama Mahasiswa' => $logbook->pkl->mahasiswa->name ?? '-',
                'Nama Instansi' => $logbook->pkl->nama_instansi,
                'Dosen Pembimbing' => $logbook->pkl->dosen->name ?? '-',
                'Tanggal Kegiatan' => $logbook->tanggal_kegiatan?->format('Y-m-d') ?? '-',
                'Deskripsi Kegiatan' => $logbook->deskripsi_kegiatan,
                'Status' => $logbook->status,
                'Catatan Dosen' => $logbook->catatan_dosen ?? '-',
            ];
        });
    }

    // JUDUL KOLOM EXCEL
    public function headings(): array
    {
        return [
            'ID Logbook',
            'NIM',
            'Nama Mahasiswa',
            'Nama Instansi',
            'Dosen Pembimbing',
            'Tanggal Kegiatan',
            'Deskripsi Kegiatan',
            'Status',
            'Catatan Dosen',
        ];
    }
}
