<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // MELIHAT SELURUH PENGGUNA
    public function index()
    {
        $users = User::select(
            'id',
            'name',
            'nim',
            'username',
            'email',
            'role',
            'is_active',
            'created_at'
        )->latest()->get();

        return response()->json([
            'message' => 'Data pengguna berhasil diambil.',
            'data' => $users,
        ]);
    }

    // LIHAT DETAIL PENGGUNA
    public function show(string $id)
    {
        $user = User::withCount([
            'pklsAsMahasiswa',
            'pklsAsDosen',
            'pklsAsKorlap',
            'logbookReviews',
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Detail pengguna berhasil diambil.',
            'data' => $user,
        ]);
    }

    // MEMBUAT AKUN (mahasiswa, dosen, korlap, admin)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nim' => 'nullable|string|max:30|unique:users,nim',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:mahasiswa,dosen,korlap,admin',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'nim' => $validated['nim'] ?? null,
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Akun berhasil dibuat.',
            'user' => $user,
        ], 201);
    }

    // MENGEDIT DATA PENGGUNA
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin' && $request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Akun admin tidak dapat diedit oleh admin lain.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'nim' => 'sometimes|nullable|string|max:30|unique:users,nim,' . $id,
            'username' => 'sometimes|nullable|string|max:255|unique:users,username,' . $id,
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'role' => 'sometimes|required|in:mahasiswa,dosen,korlap,admin',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'user' => $user,
        ]);
    }

    // RESET PASSWORD
    public function resetPassword(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password berhasil direset.',
        ]);
    }

    // MENGAKTIFKAN / MENONAKTIFKAN AKUN
    public function toggleStatus(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Status akun admin tidak dapat diubah.',
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $user->is_active
                ? 'Akun berhasil diaktifkan.'
                : 'Akun berhasil dinonaktifkan.',
            'user' => $user,
        ]);
    }

    // HAPUS PENGGUNA
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Akun admin tidak dapat dihapus.',
            ], 403);
        }

        // Cek apakah user masih terikat PKL aktif
        if ($user->role === 'mahasiswa') {
            $pklAktif = $user->pklsAsMahasiswa()
                ->where('status', 'berlangsung')
                ->exists();
            if ($pklAktif) {
                return response()->json([
                    'message' => 'Mahasiswa masih memiliki PKL berlangsung. Hapus PKL terlebih dahulu.',
                ], 422);
            }
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Akun berhasil dihapus.',
        ]);
    }
}