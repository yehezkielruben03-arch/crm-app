<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::orderBy('created_at', 'desc')->get();
        return view('vendors.index', compact('vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'kontak' => 'nullable|string',
            'pic' => 'nullable|string',
            'npwp' => 'nullable|string',
        ]);

        Vendor::create($validated);
        return back()->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'kontak' => 'nullable|string',
            'pic' => 'nullable|string',
            'npwp' => 'nullable|string',
        ]);

        Vendor::findOrFail($id)->update($validated);
        return back()->with('success', 'Vendor berhasil diupdate.');
    }

    public function destroy($id)
    {
        Vendor::findOrFail($id)->delete();
        return back()->with('success', 'Vendor berhasil dihapus.');
    }
}
