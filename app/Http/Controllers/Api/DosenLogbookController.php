<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\LogbookReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DosenLogbookController extends Controller
{
    // DOSEN MELIHAT LOGBOOK MAHASISWA BIMBINGANNYA
    public function index(Request $request)
    {
        $logbooks = Logbook::whereHas('pkl', function ($query) use ($request) {
            $query->where('dosen_id', $request->user()->id);
        })
            ->with([
                'pkl.mahasiswa:id,name,nim,email',
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data logbook mahasiswa bimbingan berhasil diambil.',
            'data' => $logbooks,
        ]);
    }

    // DOSEN MEMERIKSA LOGBOOK
    public function review(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,revision_requested,rejected',
            'komentar' => 'nullable|string|max:5000',
        ]);

        // KOMENTAR WAJIB JIKA MEMINTA REVISI
        if (
            $validated['status'] === 'revision_requested' &&
            empty(trim($validated['komentar'] ?? ''))
        ) {
            return response()->json([
                'message' => 'Komentar wajib diisi ketika meminta revisi.',
            ], 422);
        }

        $result = DB::transaction(function () use ($id, $validated, $request) {

            // MENGUNCI LOGBOOK SELAMA PROSES PEMERIKSAAN
            $logbook = Logbook::whereHas('pkl', function ($query) use ($request) {
                $query->where('dosen_id', $request->user()->id);
            })
                ->lockForUpdate()
                ->findOrFail($id);

            // MENCEGAH PEMERIKSAAN ULANG
            if ($logbook->status !== 'pending') {
                return false;
            }

            $logbook->update([
                'status' => $validated['status'],
                'catatan_dosen' => $validated['komentar'] ?? null,
            ]);

            // MENYIMPAN RIWAYAT PEMERIKSAAN
            LogbookReview::create([
                'logbook_id' => $logbook->id,
                'dosen_id' => $request->user()->id,
                'status' => $validated['status'],
                'komentar' => $validated['komentar'] ?? null,
            ]);

            return $logbook->fresh();
        });

        if ($result === false) {
            return response()->json([
                'message' => 'Logbook hanya dapat diperiksa jika berstatus pending.',
            ], 422);
        }

        return response()->json([
            'message' => 'Pemeriksaan logbook berhasil disimpan.',
            'data' => $result,
        ]);
    }
}
