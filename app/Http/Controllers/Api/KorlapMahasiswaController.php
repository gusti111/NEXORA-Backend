<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pkl;
use Illuminate\Http\Request;

class KorlapMahasiswaController extends Controller
{
    // KORLAP MELIHAT DAFTAR MAHASISWA DALAM KOORDINASINYA
    public function index(Request $request)
    {
        $pkls = Pkl::where('korlap_id', $request->user()->id)
            ->with([
                'mahasiswa:id,name,nim,email',
                'dosen:id,name,email',
                'verifikator:id,name',
            ])
            ->withCount([
                'logbooks',
                'logbooks as logbooks_approved_count' => function ($q) {
                    $q->where('status', 'approved');
                },
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data mahasiswa koordinasi berhasil diambil.',
            'data' => $pkls,
        ]);
    }

    // KORLAP MENYELESAIKAN PKL (UBAH STATUS JADI SELESAI)
    public function complete(Request $request, string $id)
    {
        $pkl = Pkl::where('korlap_id', $request->user()->id)->findOrFail($id);

        if ($pkl->status === 'selesai') {
            return response()->json([
                'message' => 'PKL ini sudah dinyatakan selesai.',
            ], 422);
        }

        // MINIMAL 1 LOGBOOK DISETUJUI SEBELUM BISA DISELESAIKAN
        $approvedCount = $pkl->logbooks()->where('status', 'approved')->count();

        if ($approvedCount < 1) {
            return response()->json([
                'message' => 'Belum ada logbook yang disetujui dosen. Pastikan minimal ada 1 logbook disetujui sebelum menyelesaikan PKL.',
            ], 422);
        }

        $pkl->update([
            'status' => 'selesai',
            'diverifikasi_oleh' => $request->user()->id,
            'tanggal_selesai_verifikasi' => now(),
        ]);

        return response()->json([
            'message' => 'PKL berhasil diselesaikan.',
            'data' => $pkl->fresh()->load(['mahasiswa', 'dosen', 'verifikator']),
        ]);
    }
}