<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pkl;
use Illuminate\Http\Request;

class DosenMahasiswaController extends Controller
{
    public function index(Request $request)
    {
        $pkls = Pkl::where('dosen_id', $request->user()->id)
            ->with(['mahasiswa:id,name,nim,email'])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data mahasiswa bimbingan berhasil diambil.',
            'data' => $pkls,
        ]);
    }
}