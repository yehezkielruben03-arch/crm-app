<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ManualBookController extends Controller
{
    /**
     * Menampilkan Buku Panduan interaktif di browser.
     */
    public function index()
    {
        return view('manual.index');
    }

    /**
     * Mengunduh Buku Panduan resmi dalam format PDF berstandar cetak.
     */
    public function download()
    {
        $pdfPath = public_path('downloads/Manual_Book_CRM_Analyst.pdf');

        // Jika file PDF sudah ada di folder downloads, sajikan langsung
        if (file_exists($pdfPath)) {
            return response()->download($pdfPath, 'Manual_Book_CRM_Analyst_Pedia.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Fallback jika belum ter-generate, compile via DomPDF
        $pdf = Pdf::loadView('manual.pdf')
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true);

        return $pdf->download('Manual_Book_CRM_Analyst_Pedia.pdf');
    }
}
