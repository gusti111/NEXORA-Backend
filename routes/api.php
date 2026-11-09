<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PklController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LogbookController;
use App\Http\Controllers\Api\DosenLogbookController;
use App\Http\Controllers\Api\DosenMahasiswaController;
use App\Http\Controllers\Api\KorlapLogbookController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\KorlapExportController;
use App\Http\Middleware\EnsureActive;
use App\Http\Controllers\Api\KorlapMahasiswaController;
use App\Http\Controllers\Api\AdminLogbookController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response()->json([
            'message' => 'Profil pengguna berhasil divalidasi.',
            'data' => $request->user(),
        ]);
    })->middleware(EnsureActive::class);

    // KELOLA AKUN DOSEN DAN KORLAP (ADMIN)
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('role:admin');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('role:admin');

    // EDIT DATA PENGGUNA (ADMIN)
    Route::put('/users/{id}', [UserController::class, 'update'])
        ->middleware('role:admin');

    // AKTIFKAN / NONAKTIFKAN AKUN (ADMIN)
    Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])
        ->middleware('role:admin');

    // DATA PKL MAHASISWA
    Route::get('/pkls', [PklController::class, 'index'])
        ->middleware('role:mahasiswa');

    // KELOLA DATA PKL (ADMIN)
    Route::post('/pkls', [PklController::class, 'store'])
        ->middleware('role:admin');

    // ADMIN MELIHAT SELURUH DATA PKL
    Route::get('/admin/pkls', [PklController::class, 'adminIndex'])
        ->middleware('role:admin');

    // ADMIN MENGEDIT PENEMPATAN PKL
    Route::put('/admin/pkls/{id}', [PklController::class, 'update'])
        ->middleware('role:admin');

    // DATA LOGBOOK MAHASISWA
    Route::get('/logbooks', [LogbookController::class, 'index'])
        ->middleware('role:mahasiswa');

    Route::post('/logbooks', [LogbookController::class, 'store'])
        ->middleware('role:mahasiswa');

    Route::put('/logbooks/{id}/resubmit', [LogbookController::class, 'resubmit'])
        ->middleware('role:mahasiswa');

    // RIWAYAT PEMERIKSAAN LOGBOOK — BISA DIAKSES MAHASISWA & DOSEN
    Route::get('/logbooks/{id}/history', [LogbookController::class, 'history'])
        ->middleware('role:mahasiswa,dosen');

    // FOTO DOKUMENTASI DIKIRIM MELALUI ENDPOINT PRIVAT DENGAN PEMERIKSAAN KEPEMILIKAN
    Route::get('/logbooks/{id}/photo', [LogbookController::class, 'photo'])
        ->middleware('role:mahasiswa,dosen,korlap,admin');

    Route::get('/logbooks/statistics', [LogbookController::class, 'statistics'])
        ->middleware('role:mahasiswa');

    // DOSEN MELIHAT LOGBOOK MAHASISWA BIMBINGANNYA
    Route::get('/dosen/logbooks', [DosenLogbookController::class, 'index'])
        ->middleware('role:dosen');

    // DOSEN MELIHAT DAFTAR MAHASISWA BIMBINGANNYA
    Route::get('/dosen/mahasiswa', [DosenMahasiswaController::class, 'index'])
        ->middleware('role:dosen');

    // DOSEN MEMERIKSA LOGBOOK
    Route::patch('/dosen/logbooks/{id}/review', [DosenLogbookController::class, 'review'])
        ->middleware('role:dosen');

    // KORLAP MEMANTAU LOGBOOK MAHASISWA
    Route::get('/korlap/logbooks', [KorlapLogbookController::class, 'index'])
        ->middleware('role:korlap');

    Route::get('/korlap/statistics', [KorlapLogbookController::class, 'statistics'])
        ->middleware('role:korlap');

    // KORLAP EXPORT LAPORAN EXCEL
    Route::get('/korlap/logbooks/export/excel', [KorlapExportController::class, 'exportExcel'])
        ->middleware('role:korlap');

    // KORLAP EXPORT LAPORAN PDF
    Route::get('/korlap/logbooks/export/pdf', [KorlapExportController::class, 'exportPdf'])
        ->middleware('role:korlap');

    // KORLAP MELIHAT DAFTAR MAHASISWA KOORDINASINYA
    Route::get('/korlap/mahasiswa', [KorlapMahasiswaController::class, 'index'])
        ->middleware('role:korlap');

    // KORLAP MENYELESAIKAN PKL
    Route::put('/korlap/pkls/{id}/complete', [KorlapMahasiswaController::class, 'complete'])
        ->middleware('role:korlap');

    // DASHBOARD ADMIN
    Route::get('/admin/statistics', [AdminDashboardController::class, 'statistics'])
        ->middleware('role:admin');

        // ADMIN: DETAIL USER
    Route::get('/admin/users/{id}', [UserController::class, 'show'])
        ->middleware('role:admin');

    // ADMIN: RESET PASSWORD
    Route::put('/admin/users/{id}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('role:admin');

    // ADMIN: HAPUS USER
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy'])
        ->middleware('role:admin');

    // ADMIN: HAPUS PKL
    Route::delete('/admin/pkls/{id}', [PklController::class, 'destroy'])
        ->middleware('role:admin');

    // ADMIN: LIHAT SEMUA LOGBOOK
    Route::get('/admin/logbooks', [AdminLogbookController::class, 'index'])
        ->middleware('role:admin');
});