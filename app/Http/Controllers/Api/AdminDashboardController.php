<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pkl;
use App\Models\Logbook;

class AdminDashboardController extends Controller
{
    public function statistics()
    {
        return response()->json([
            'message' => 'Statistik dashboard Admin berhasil diambil.',
            'data' => [
                // Pengguna
                'total_pengguna' => User::count(),
                'total_mahasiswa' => User::where('role', 'mahasiswa')->count(),
                'total_dosen' => User::where('role', 'dosen')->count(),
                'total_korlap' => User::where('role', 'korlap')->count(),
                'total_admin' => User::where('role', 'admin')->count(),
                'user_aktif' => User::where('is_active', true)->count(),
                'user_nonaktif' => User::where('is_active', false)->count(),

                // PKL
                'total_pkl' => Pkl::count(),
                'pkl_berlangsung' => Pkl::where('status', 'berlangsung')->count(),
                'pkl_selesai' => Pkl::where('status', 'selesai')->count(),

                // Logbook
                'total_logbook' => Logbook::count(),
                'pending' => Logbook::where('status', 'pending')->count(),
                'approved' => Logbook::where('status', 'approved')->count(),
                'revision_requested' => Logbook::where('status', 'revision_requested')->count(),
                'rejected' => Logbook::where('status', 'rejected')->count(),

                // Aktivitas terbaru (5 logbook terakhir)
                'aktivitas_terbaru' => Logbook::with([
                    'pkl.mahasiswa:id,name',
                ])
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'pkl_id', 'judul_kegiatan', 'status', 'created_at']),
            ],
        ]);
    }
}