<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Notifications\StudentLogbookSubmitted;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;

class LogbookController extends Controller
{
    // KOMPRESI FOTO MAKSIMAL 500 KB
    private function compressPhoto($foto)
    {
        $manager = new ImageManager(new Driver());

        $image = $manager->read($foto->getPathname());

        $image->scaleDown(
            width: 1600,
            height: 1600
        );

        $quality = 75;
        $maxSize = 500 * 1024;

        while (true) {
            $encoded = $image->encode(
                new JpegEncoder(quality: $quality)
            );

            if (strlen((string) $encoded) <= $maxSize) {
                break;
            }

            if ($quality > 35) {
                $quality -= 10;
            } elseif (max($image->width(), $image->height()) > 320) {
                $newWidth = max(320, (int) ($image->width() * 0.8));
                $newHeight = max(320, (int) ($image->height() * 0.8));

                $image->scaleDown(
                    width: $newWidth,
                    height: $newHeight
                );

                $quality = 65;
            } else {
                throw new \RuntimeException(
                    'Foto tidak dapat dikompres hingga maksimal 500 KB.'
                );
            }
        }

        $filename = Str::uuid() . '.jpg';
        $path = 'logbooks/' . $filename;

        $saved = Storage::disk('local')->put(
            $path,
            (string) $encoded
        );

        if (!$saved) {
            throw new \RuntimeException(
                'Gagal menyimpan foto logbook.'
            );
        }

        return $path;
    }

    // MAHASISWA MELIHAT SELURUH LOGBOOK MILIKNYA
    public function index(Request $request)
    {
        $pkls = $request->user()
            ->pklsAsMahasiswa()
            ->with('logbooks')
            ->get();

        $logbooks = $pkls->flatMap(function ($pkl) {
            return $pkl->logbooks;
        })->sortByDesc('tanggal_kegiatan')->values();

        return response()->json([
            'message' => 'Data logbook berhasil diambil.',
            'data' => $logbooks,
        ]);
    }

    // MAHASISWA MELIHAT STATISTIK LOGBOOK MILIKNYA
    public function statistics(Request $request)
    {
        $logbooks = Logbook::whereHas('pkl', function ($query) use ($request) {
            $query->where('mahasiswa_id', $request->user()->id);
        });

        return response()->json([
            'message' => 'Statistik logbook berhasil diambil.',
            'data' => [
                'total_logbook' => (clone $logbooks)->count(),
                'pending' => (clone $logbooks)->where('status', 'pending')->count(),
                'approved' => (clone $logbooks)->where('status', 'approved')->count(),
                'revision_requested' => (clone $logbooks)->where('status', 'revision_requested')->count(),
                'rejected' => (clone $logbooks)->where('status', 'rejected')->count(),
            ],
        ]);
    }

    // MAHASISWA MEMBUAT LOGBOOK
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pkl_id' => 'required|exists:pkls,id',
            'idempotency_key' => 'nullable|uuid',
            'judul_kegiatan' => 'required|string|max:255',
            'deskripsi_kegiatan' => 'required|string|max:5000',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // MEMASTIKAN PKL MILIK MAHASISWA
        $pkl = $request->user()
            ->pklsAsMahasiswa()
            ->where('id', $validated['pkl_id'])
            ->first();

        if (!$pkl) {
            return response()->json([
                'message' => 'Kamu tidak memiliki akses ke penempatan PKL ini.',
            ], 403);
        }

        // Idempotency prevents duplicate rows when a mobile retry follows
        // a successful server write whose response was lost in transit.
        if (!empty($validated['idempotency_key'])) {
            $existing = Logbook::where('idempotency_key', $validated['idempotency_key'])
                ->first();

            if ($existing) {
                if ((int) $existing->pkl_id !== (int) $pkl->id) {
                    return response()->json([
                        'message' => 'Kunci pengiriman sudah digunakan oleh data lain.',
                    ], 409);
                }

                return response()->json([
                    'message' => 'Logbook ini sudah pernah diterima.',
                    'data' => $existing,
                    'duplicate' => true,
                ]);
            }
        }

        // LOGBOOK BARU HANYA BOLEH DIKIRIM SAAT PKL BERLANGSUNG
        if ($pkl->status !== 'berlangsung') {
            return response()->json([
                'message' => 'Logbook hanya dapat dikirim selama PKL berlangsung.',
            ], 422);
        }

        // BATAS TANGGAL KEGIATAN BERDASARKAN PERIODE PKL
        $tanggalMulai = $pkl->tanggal_mulai->toDateString();

        $tanggalSelesai = $pkl->tanggal_selesai
            ? $pkl->tanggal_selesai->toDateString()
            : now()->toDateString();

        $batasAkhir = min(
            $tanggalSelesai,
            now()->toDateString()
        );

        $tanggalValidated = $request->validate([
            'tanggal_kegiatan' => [
                'required',
                'date',
                'after_or_equal:' . $tanggalMulai,
                'before_or_equal:' . $batasAkhir,
            ],
        ]);

        $validated['tanggal_kegiatan'] = $tanggalValidated['tanggal_kegiatan'];

        $fotoPath = null;

        if ($request->hasFile('foto')) {
            $fotoPath = $this->compressPhoto($request->file('foto'));
        }

        try {
            // The logbook row and its database notification must commit or
            // roll back together. This prevents an accepted logbook from
            // being left without a lecturer notification after a DB error.
            $logbook = DB::transaction(function () use ($pkl, $validated, $fotoPath) {
                $created = Logbook::create([
                    'pkl_id' => $pkl->id,
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                    'tanggal_kegiatan' => $validated['tanggal_kegiatan'],
                    'judul_kegiatan' => $validated['judul_kegiatan'],
                    'deskripsi_kegiatan' => $validated['deskripsi_kegiatan'],
                    'foto' => $fotoPath,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'status' => 'pending',
                ]);

                $pkl->loadMissing('dosen', 'mahasiswa');
                $pkl->dosen?->notify(new StudentLogbookSubmitted(
                    $created->load('pkl.mahasiswa')
                ));

                return $created;
            });
        } catch (QueryException $e) {
            // A concurrent retry can pass the initial lookup before the
            // first request commits. The unique index arbitrates the race;
            // reconcile the winner instead of surfacing a duplicate error.
            if ($fotoPath !== null) {
                Storage::disk('local')->delete($fotoPath);
            }

            $sqlState = $e->errorInfo[0] ?? (string) $e->getCode();
            $driverCode = $e->errorInfo[1] ?? null;
            $isUniqueViolation = in_array($sqlState, ['23000', '23505'], true)
                || in_array((int) $driverCode, [19, 1062], true);

            if ($isUniqueViolation && !empty($validated['idempotency_key'])) {
                $existing = Logbook::where(
                    'idempotency_key',
                    $validated['idempotency_key']
                )->first();

                if ($existing) {
                    if ((int) $existing->pkl_id !== (int) $pkl->id) {
                        return response()->json([
                            'message' => 'Kunci pengiriman sudah digunakan oleh data lain.',
                        ], 409);
                    }

                    return response()->json([
                        'message' => 'Logbook ini sudah pernah diterima.',
                        'data' => $existing,
                        'duplicate' => true,
                    ]);
                }
            }

            throw $e;
        } catch (\Throwable $e) {
            // Hapus foto jika transaksi logbook/notifikasi gagal.
            if ($fotoPath !== null) {
                Storage::disk('local')->delete($fotoPath);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Logbook berhasil dikirim.',
            'data' => $logbook,
        ], 201);
    }

    // MAHASISWA MENGIRIM ULANG LOGBOOK SETELAH REVISI
    public function resubmit(Request $request, string $id)
    {
        $user = $request->user();

        // MEMASTIKAN LOGBOOK MILIK MAHASISWA DAN MEMUAT PENEMPATANNYA
        $logbook = Logbook::whereHas('pkl', function ($query) use ($user) {
            $query->where('mahasiswa_id', $user->id);
        })
            ->with('pkl.dosen', 'pkl.mahasiswa')
            ->findOrFail($id);

        if ($logbook->status !== 'revision_requested') {
            return response()->json([
                'message' => 'Hanya logbook yang diminta revisi yang dapat dikirim ulang.',
            ], 422);
        }

        $pkl = $logbook->pkl;

        // BATAS TANGGAL KEGIATAN BERDASARKAN PERIODE PKL
        $tanggalMulai = $pkl->tanggal_mulai->toDateString();

        $tanggalSelesai = $pkl->tanggal_selesai
            ? $pkl->tanggal_selesai->toDateString()
            : now()->toDateString();

        $batasAkhir = min(
            $tanggalSelesai,
            now()->toDateString()
        );

        // VALIDASI DATA YANG DIKIRIM
        $validated = $request->validate([
            'tanggal_kegiatan' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:' . $tanggalMulai,
                'before_or_equal:' . $batasAkhir,
            ],
            'judul_kegiatan' => 'sometimes|required|string|max:255',
            'deskripsi_kegiatan' => 'sometimes|required|string|max:5000',
            'foto' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        // MEMERIKSA TANGGAL YANG AKAN DIGUNAKAN
        $tanggalUntukDiperiksa = $validated['tanggal_kegiatan']
            ?? $logbook->tanggal_kegiatan->toDateString();

        if (
            $tanggalUntukDiperiksa < $tanggalMulai ||
            $tanggalUntukDiperiksa > $batasAkhir
        ) {
            return response()->json([
                'message' => 'Tanggal kegiatan logbook berada di luar periode PKL. Silakan perbaiki tanggal sebelum mengirim ulang.',
            ], 422);
        }

        // CEK APAKAH ADA PERUBAHAN YANG DIKIRIM
        $hasChanges = false;

        foreach ($validated as $field => $value) {
            if ($field === 'foto') {
                continue;
            }

            $currentValue = $logbook->{$field};

            if ($field === 'tanggal_kegiatan') {
                $currentValue = $currentValue
                    ? $currentValue->toDateString()
                    : null;

                $value = $value
                    ? date('Y-m-d', strtotime($value))
                    : null;
            }

            if (in_array($field, ['latitude', 'longitude'], true)) {
                $currentValue = $currentValue !== null
                    ? (float) $currentValue
                    : null;

                $value = $value !== null
                    ? (float) $value
                    : null;
            }

            if ($currentValue != $value) {
                $hasChanges = true;
                break;
            }
        }

        // FOTO BARU JUGA DIANGGAP SEBAGAI PERUBAHAN
        $hasChanges = $hasChanges || $request->hasFile('foto');

        if (!$hasChanges) {
            return response()->json([
                'message' => 'Tidak ada perubahan. Silakan perbaiki minimal satu bagian logbook sebelum dikirim ulang.',
            ], 422);
        }

        $data = $validated;

        // FOTO LAMA DIPERTAHANKAN JIKA TIDAK ADA FOTO BARU
        if (!$request->hasFile('foto')) {
            unset($data['foto']);
        }

        $oldFotoPath = $logbook->foto;
        $newFotoPath = null;

        // KOMPRESI DAN SIMPAN FOTO BARU SEBELUM TRANSAKSI DATABASE
        if ($request->hasFile('foto')) {
            $newFotoPath = $this->compressPhoto($request->file('foto'));
            $data['foto'] = $newFotoPath;
        }

        // STATUS DAN CATATAN DOSEN DIATUR OLEH SISTEM
        $data['status'] = 'pending';
        $data['catatan_dosen'] = null;

        try {
            // Kunci baris logbook agar dua permintaan resubmit tidak memproses
            // revisi yang sama secara bersamaan. Update dan notifikasi harus
            // berhasil bersama-sama atau seluruh transaksi dibatalkan.
            $updatedLogbook = DB::transaction(function () use ($id, $user, $data) {
                $lockedLogbook = Logbook::whereHas('pkl', function ($query) use ($user) {
                    $query->where('mahasiswa_id', $user->id);
                })
                    ->with('pkl.dosen', 'pkl.mahasiswa')
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($lockedLogbook->status !== 'revision_requested') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => 'Logbook tidak lagi berada pada status revisi dan tidak dapat dikirim ulang.',
                    ]);
                }

                $lockedLogbook->update($data);

                // Notifikasi dikirim kepada dosen pembimbing dari penempatan
                // PKL yang sama, bukan kepada dosen dari input pengguna.
                $lockedLogbook->pkl?->dosen?->notify(
                    new StudentLogbookSubmitted($lockedLogbook, true)
                );

                return $lockedLogbook->fresh();
            });
        } catch (\Throwable $e) {
            // Jika update atau penyimpanan notifikasi gagal, hapus foto baru
            // dan pertahankan foto lama yang masih direferensikan database.
            if ($newFotoPath !== null) {
                Storage::disk('local')->delete($newFotoPath);
            }

            throw $e;
        }

        // Hapus foto lama hanya setelah transaksi database berhasil commit.
        if ($newFotoPath !== null && $oldFotoPath) {
            Storage::disk('local')->delete($oldFotoPath);
        }

        return response()->json([
            'message' => 'Logbook berhasil diperbaiki dan dikirim ulang. Dosen pembimbing telah diberi notifikasi.',
            'data' => $updatedLogbook,
        ]);
    }

    // FOTO LOGBOOK DIKIRIM HANYA KEPADA PENGGUNA YANG BERHAK
    public function photo(Request $request, string $id)
    {
        $user = $request->user();
        $query = Logbook::query()->whereKey($id);

        // Role access is not enough: enforce ownership / assigned placement.
        switch ($user->role) {
            case 'mahasiswa':
                $query->whereHas('pkl', function ($q) use ($user) {
                    $q->where('mahasiswa_id', $user->id);
                });
                break;

            case 'dosen':
                $query->whereHas('pkl', function ($q) use ($user) {
                    $q->where('dosen_id', $user->id);
                });
                break;

            case 'korlap':
                $query->whereHas('pkl', function ($q) use ($user) {
                    $q->where('korlap_id', $user->id);
                });
                break;

            case 'admin':
                // Administrators may inspect logbook evidence across placements.
                break;

            default:
                abort(403);
        }

        // Return 404 for both an unknown logbook and a record outside the
        // caller's scope so record existence is not disclosed.
        $logbook = $query->firstOrFail();
        $path = $logbook->foto;

        // Accept only a simple image filename beneath the logbooks directory.
        // Never allow database path values to traverse into other private files.
        if (
            !$path ||
            !preg_match('#\\Alogbooks/[A-Za-z0-9._-]+\\.(?:jpe?g|png|webp)\\z#i', $path)
        ) {
            abort(404);
        }

        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            abort(404);
        }

        $contentType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return response()->stream(function () use ($disk, $path) {
            echo $disk->get($path);
        }, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // RIWAYAT PEMERIKSAAN LOGBOOK — BISA DIAKSES MAHASISWA & DOSEN
    public function history(Request $request, string $id)
    {
        $user = $request->user();

        $query = Logbook::query();

        if ($user->role === 'mahasiswa') {
            // Mahasiswa hanya bisa akses logbook miliknya sendiri
            $query->whereHas('pkl', function ($q) use ($user) {
                $q->where('mahasiswa_id', $user->id);
            });
        } elseif ($user->role === 'dosen') {
            // Dosen hanya bisa akses logbook mahasiswa bimbingannya
            $query->whereHas('pkl', function ($q) use ($user) {
                $q->where('dosen_id', $user->id);
            });
        }

        $logbook = $query
            ->with([
                'reviews' => function ($q) {
                    $q->with('dosen:id,name')->oldest();
                },
            ])
            ->findOrFail($id);

        return response()->json([
            'message' => 'Riwayat pemeriksaan logbook berhasil diambil.',
            'data' => [
                'logbook_id' => $logbook->id,
                'status_sekarang' => $logbook->status,
                'riwayat_pemeriksaan' => $logbook->reviews,
            ],
        ]);
    }
}