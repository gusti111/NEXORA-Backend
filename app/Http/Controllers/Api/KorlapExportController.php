<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\KorlapLogbookExport;
use App\Models\Logbook;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class KorlapExportController extends Controller
{
    // KORLAP MENGUNDUH LAPORAN LOGBOOK EXCEL
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new KorlapLogbookExport($request->user()->id),
            'laporan-logbook-korlap.xlsx'
        );
    }

    // KORLAP MENGUNDUH LAPORAN LOGBOOK PDF
    public function exportPdf(Request $request)
    {
        $logbooks = Logbook::whereHas('pkl', function ($query) use ($request) {
            $query->where('korlap_id', $request->user()->id);
        })
        ->with([
            'pkl.mahasiswa:id,name,nim',
            'pkl.dosen:id,name',
        ])
        ->latest()
        ->get();

        $pdf = Pdf::loadView('pdf.korlap-logbooks', [
            'logbooks' => $logbooks,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-logbook-korlap.pdf');
    }
}
