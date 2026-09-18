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
            'mp' => 'required|numeric|min:0',
            'thr' => 'required|numeric|min:0',
            'bpjs_kes' => 'required|numeric|min:0',
            'bpjs_tk' => 'required|numeric|min:0',
        ]);
        
        $total = $validated['mp'] + $validated['thr'] + $validated['bpjs_kes'] + $validated['bpjs_tk'];
        $validated['total'] = $total;

        $mp = Mainpower::first();
        if ($mp) {
            $mp->update($validated);
        } else {
            Mainpower::create($validated);
        }

        return back()->with('success', 'Data Portal Mainpower berhasil diperbarui.');
    }
}
