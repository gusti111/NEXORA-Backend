<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pkl;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PklController extends Controller
{
    // MAHASISWA MELIHAT DATA PKL MILIKNYA
    public function index(Request $request)
    {
        $pkls = $request->user()
            ->pklsAsMahasiswa()
            ->with(['dosen:id,name,email', 'korlap:id,name,email'])
            ->get();

        return response()->json([
            'message' => 'Data PKL berhasil diambil.',
            'data' => $pkls,
        ]);
    }

    // ADMIN MEMBUAT PENEMPATAN PKL
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mahasiswa_id' => 'required|exists:users,id',
            'dosen_id' => 'required|exists:users,id',
            'korlap_id' => 'required|exists:users,id',
            'nama_instansi' => 'required|string|max:255',
            'alamat_instansi' => 'nullable|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        return DB::transaction(function () use ($validated) {

            // MENGUNCI DATA MAHASISWA
            $mahasiswa = User::whereKey($validated['mahasiswa_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $dosen = User::findOrFail($validated['dosen_id']);
            $korlap = User::findOrFail($validated['korlap_id']);

            // MEMASTIKAN ROLE PENGGUNA SESUAI
            if (
                $mahasiswa->role !== 'mahasiswa' ||
                $dosen->role !== 'dosen' ||
                $korlap->role !== 'korlap'
            ) {
                return response()->json([
                    'message' => 'Peran pengguna tidak sesuai dengan penempatan PKL.',
                ], 422);
            }

            // MEMASTIKAN SEMUA AKUN AKTIF
            if (
                !$mahasiswa->is_active ||
                !$dosen->is_active ||
                !$korlap->is_active
            ) {
                return response()->json([
                    'message' => 'Semua pengguna harus memiliki akun aktif.',
                ], 422);
            }

            // MENCEGAH MAHASISWA MEMILIKI DUA PKL AKTIF
            $pklAktif = Pkl::where('mahasiswa_id', $mahasiswa->id)
                ->where('status', 'berlangsung')
                ->exists();

            if ($pklAktif) {
                return response()->json([
                    'message' => 'Mahasiswa sudah memiliki PKL yang sedang berlangsung.',
                ], 422);
            }

            // MEMBUAT PENEMPATAN PKL
            $pkl = Pkl::create([
                'mahasiswa_id' => $mahasiswa->id,
                'dosen_id' => $dosen->id,
                'korlap_id' => $korlap->id,
                'nama_instansi' => $validated['nama_instansi'],
                'alamat_instansi' => $validated['alamat_instansi'] ?? null,
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                'status' => 'berlangsung',
            ]);

            return response()->json([
                'message' => 'Penempatan PKL berhasil dibuat.',
                'data' => $pkl->load([
                    'mahasiswa',
                    'dosen',
                    'korlap',
                ]),
            ], 201);
        });
    }

    // ADMIN MELIHAT SELURUH DATA PKL
    public function adminIndex()
    {
        $pkls = Pkl::with([
            'mahasiswa',
            'dosen',
            'korlap',
        ])->latest()->get();

        return response()->json([
            'message' => 'Seluruh data PKL berhasil diambil.',
            'data' => $pkls,
        ]);
    }

    // ADMIN MENGEDIT PENEMPATAN PKL
    public function update(Request $request, string $id)
    {
        // MAHASISWA PEMILIK PKL TIDAK BOLEH DIGANTI
        if ($request->exists('mahasiswa_id')) {
            return response()->json([
                'message' => 'Mahasiswa pemilik PKL tidak dapat diganti.',
            ], 422);
        }

        $validated = $request->validate([
            'dosen_id' => 'required|exists:users,id',
            'korlap_id' => 'required|exists:users,id',
            'nama_instansi' => 'required|string|max:255',
            'alamat_instansi' => 'nullable|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'sometimes|required|in:berlangsung,selesai',
        ]);

        // MENGAMBIL ID MAHASISWA PEMILIK PKL
        $pklAwal = Pkl::findOrFail($id);
        $mahasiswaId = $pklAwal->mahasiswa_id;

        return DB::transaction(function () use ($validated, $id, $mahasiswaId) {

            // MENGUNCI DATA MAHASISWA TERLEBIH DAHULU
            $mahasiswa = User::whereKey($mahasiswaId)
                ->lockForUpdate()
                ->firstOrFail();

            // MENGUNCI DATA PKL
            $pkl = Pkl::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $dosen = User::findOrFail($validated['dosen_id']);
            $korlap = User::findOrFail($validated['korlap_id']);

            // MEMASTIKAN ROLE PENGGUNA SESUAI
            if (
                $mahasiswa->role !== 'mahasiswa' ||
                $dosen->role !== 'dosen' ||
                $korlap->role !== 'korlap'
            ) {
                return response()->json([
                    'message' => 'Peran pengguna tidak sesuai dengan penempatan PKL.',
                ], 422);
            }

            // MEMASTIKAN SEMUA AKUN AKTIF
            if (
                !$mahasiswa->is_active ||
                !$dosen->is_active ||
                !$korlap->is_active
            ) {
                return response()->json([
                    'message' => 'Semua pengguna harus memiliki akun aktif.',
                ], 422);
            }

            // MENENTUKAN PERIODE PKL BARU
            $tanggalMulaiBaru = $validated['tanggal_mulai'];

            $tanggalSelesaiBaru = array_key_exists('tanggal_selesai', $validated)
                ? $validated['tanggal_selesai']
                : $pkl->getRawOriginal('tanggal_selesai');

            // MEMERIKSA LOGBOOK DI LUAR PERIODE BARU
            $logbookDiLuarPeriode = $pkl->logbooks()
                ->where(function ($query) use ($tanggalMulaiBaru, $tanggalSelesaiBaru) {
                    $query->whereDate('tanggal_kegiatan', '<', $tanggalMulaiBaru);

                    if ($tanggalSelesaiBaru !== null) {
                        $query->orWhereDate('tanggal_kegiatan', '>', $tanggalSelesaiBaru);
                    }
                })
                ->exists();

            if ($logbookDiLuarPeriode) {
                return response()->json([
                    'message' => 'Periode PKL tidak dapat diubah karena terdapat logbook dengan tanggal di luar periode baru.',
                ], 422);
            }

            // MEMERIKSA STATUS PKL YANG AKAN DITERAPKAN
            $statusBaru = $validated['status'] ?? $pkl->status;

            // MENCEGAH DUPLIKASI PKL AKTIF
            if ($statusBaru === 'berlangsung') {
                $pklAktifLain = Pkl::where('mahasiswa_id', $pkl->mahasiswa_id)
                    ->where('status', 'berlangsung')
                    ->where('id', '!=', $pkl->id)
                    ->exists();

                if ($pklAktifLain) {
                    return response()->json([
                        'message' => 'Mahasiswa sudah memiliki PKL yang sedang berlangsung.',
                    ], 422);
                }
            }

            // MEMPERBARUI PENEMPATAN PKL
            $pkl->update($validated);

            return response()->json([
                'message' => 'Penempatan PKL berhasil diperbarui.',
                'data' => $pkl->fresh()->load([
                    'mahasiswa',
                    'dosen',
                    'korlap',
                ]),
            ]);
        });
    }

    // ADMIN MENGHAPUS PENEMPATAN PKL
    public function destroy(string $id)
    {
        $pkl = Pkl::findOrFail($id);

        // Cek apakah masih ada logbook
        if ($pkl->logbooks()->count() > 0) {
            return response()->json([
                'message' => 'PKL tidak dapat dihapus karena masih memiliki logbook. Hapus logbook terlebih dahulu.',
            ], 422);
        }

        $pkl->delete();

        return response()->json([
            'message' => 'Penempatan PKL berhasil dihapus.',
        ]);
    }
}