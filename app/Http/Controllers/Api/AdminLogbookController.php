<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use Illuminate\Http\Request;

class AdminLogbookController extends Controller
{
    // ADMIN MELIHAT SELURUH LOGBOOK
    public function index(Request $request)
    {
        $query = Logbook::with([
            'pkl.mahasiswa:id,name,nim',
            'pkl.dosen:id,name',
        ]);

        if ($request->has('status') && $request->status !== 'semua') {
            $query->where('status', $request->status);
        }

        $logbooks = $query->latest()->get();

        return response()->json([
            'message' => 'Data logbook berhasil diambil.',
            'data' => $logbooks,
        ]);
    }
}