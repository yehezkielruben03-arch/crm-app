<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mainpower;

class MainpowerController extends Controller
{
    public function index()
    {
        $mainpower = Mainpower::first();
        return view('mainpowers.index', compact('mainpower'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mp'               => 'required|numeric|min:0',
            'thr'              => 'nullable|numeric|min:0',
            'uang_makan'       => 'nullable|numeric|min:0',
            'bpjs_kes'         => 'nullable|numeric|min:0',
            'bpjs_kes_percent' => 'nullable|numeric|min:0|max:100',
            'bpjs_tk'          => 'nullable|numeric|min:0',
            'bpjs_tk_percent'  => 'nullable|numeric|min:0|max:100',
            'lembur_per_jam'   => 'nullable|numeric|min:0',
        ]);
        
        $mpGaji         = (float) $validated['mp'];
        $thr            = (float) ($validated['thr'] ?? 0);
        $uangMakan      = (float) ($validated['uang_makan'] ?? 0);
        $bpjsKesPercent = isset($validated['bpjs_kes_percent']) && $validated['bpjs_kes_percent'] !== '' ? (float)$validated['bpjs_kes_percent'] : 4.0;
        $bpjsTkPercent  = isset($validated['bpjs_tk_percent']) && $validated['bpjs_tk_percent'] !== '' ? (float)$validated['bpjs_tk_percent'] : 5.7;

        // Hitung nominal BPJS jika tidak diinput manual atau otomatis berdasarkan formula %
        $bpjsKes = !empty($validated['bpjs_kes']) ? (float)$validated['bpjs_kes'] : round(($mpGaji * $bpjsKesPercent) / 100);
        $bpjsTk  = !empty($validated['bpjs_tk']) ? (float)$validated['bpjs_tk'] : round(($mpGaji * $bpjsTkPercent) / 100);

        // Hitung tarif lembur / jam jika tidak diisi manual: formula standar 1.5x upah per jam (8 jam kerja)
        $lemburPerJam = !empty($validated['lembur_per_jam']) ? (float)$validated['lembur_per_jam'] : round(($mpGaji / 8) * 1.5);

        // Total rate harian Pedia
        $total = $mpGaji + $thr + $uangMakan + $bpjsKes + $bpjsTk;

        $payload = [
            'mp'               => $mpGaji,
            'thr'              => $thr,
            'uang_makan'       => $uangMakan,
            'bpjs_kes'         => $bpjsKes,
            'bpjs_kes_percent' => $bpjsKesPercent,
            'bpjs_tk'          => $bpjsTk,
            'bpjs_tk_percent'  => $bpjsTkPercent,
            'lembur_per_jam'   => $lemburPerJam,
            'total'            => $total,
        ];

        $mp = Mainpower::first();
        if ($mp) {
            $mp->update($payload);
        } else {
            Mainpower::create($payload);
        }

        return back()->with('success', 'Data Portal Tarif Mainpower berhasil diperbarui.');
    }
}
