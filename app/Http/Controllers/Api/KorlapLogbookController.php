<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\Pkl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KorlapLogbookController extends Controller
{
    // KORLAP MELIHAT SELURUH LOGBOOK MAHASISWA KOORDINASINYA
    public function index(Request $request)
    {
        $logbooks = Logbook::whereHas('pkl', function ($query) use ($request) {
            $query->where('korlap_id', $request->user()->id);
        })
            ->with([
                'pkl.mahasiswa:id,name,nim,email',
                'pkl.dosen:id,name,email',
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data logbook mahasiswa koordinasi berhasil diambil.',
            'data' => $logbooks,
        ]);
    }

    // KORLAP MELIHAT STATISTIK LOGBOOK
    public function statistics(Request $request)
    {
        $korlapId = $request->user()->id;

        $totalMahasiswa = Pkl::where('korlap_id', $korlapId)
            ->distinct('mahasiswa_id')
            ->count('mahasiswa_id');

        $statistik = Logbook::whereHas('pkl', function ($query) use ($korlapId) {
            $query->where('korlap_id', $korlapId);
        })
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'message' => 'Statistik logbook berhasil diambil.',
            'data' => [
                'total_mahasiswa' => $totalMahasiswa,
                'total_logbook' => $statistik->sum(),
                'pending' => $statistik->get('pending', 0),
                'approved' => $statistik->get('approved', 0),
                'revision_requested' => $statistik->get('revision_requested', 0),
                'rejected' => $statistik->get('rejected', 0),
            ],
        ]);
    }
}
