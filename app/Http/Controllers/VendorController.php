<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();

        // Filter: Pencarian nama, PIC, kontak, NPWP, Bank, Rekening
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_vendor', 'like', "%{$search}%")
                  ->orWhere('pic', 'like', "%{$search}%")
                  ->orWhere('kontak', 'like', "%{$search}%")
                  ->orWhere('npwp', 'like', "%{$search}%")
                  ->orWhere('bank', 'like', "%{$search}%")
                  ->orWhere('rekening', 'like', "%{$search}%");
            });
        }

        // Filter: Kategori
        if ($kategori = $request->input('kategori')) {
            $query->where('kategori', $kategori);
        }

        // Filter: Status (Active / Inactive)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $vendors = $query->orderBy('nama_vendor', 'asc')->get();

        // Kategori terdaftar untuk filter dropdown
        $definedCategories = Vendor::categories();
        $dbCategories = Vendor::whereNotNull('kategori')->pluck('kategori')->unique()->toArray();
        $categories = array_values(array_unique(array_merge($definedCategories, $dbCategories)));

        // Summary counters
        $totalCount = Vendor::count();
        $activeCount = Vendor::where('status', Vendor::STATUS_ACTIVE)->count();
        $inactiveCount = Vendor::where('status', Vendor::STATUS_INACTIVE)->count();

        return view('vendors.index', compact(
            'vendors',
            'categories',
            'totalCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'pic'         => 'nullable|string|max:100',
            'kontak'      => 'nullable|string|max:100',
            'npwp'        => 'nullable|string|max:50',
            'kategori'    => 'nullable|string|max:100',
            'bank'        => 'nullable|string|max:100',
            'rekening'    => 'nullable|string|max:100',
            'alamat'      => 'nullable|string',
            'status'      => 'nullable|in:Active,Inactive',
        ]);

        if (empty($validated['status'])) {
            $validated['status'] = Vendor::STATUS_ACTIVE;
        }

        $vendor = Vendor::create($validated);

        return redirect()->route('vendors.index')->with('success', "Vendor \"{$vendor->nama_vendor}\" berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'nama_vendor' => 'required|string|max:255',
            'pic'         => 'nullable|string|max:100',
            'kontak'      => 'nullable|string|max:100',
            'npwp'        => 'nullable|string|max:50',
            'kategori'    => 'nullable|string|max:100',
            'bank'        => 'nullable|string|max:100',
            'rekening'    => 'nullable|string|max:100',
            'alamat'      => 'nullable|string',
            'status'      => 'required|in:Active,Inactive',
        ]);

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('success', "Data Vendor \"{$vendor->nama_vendor}\" berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $name = $vendor->nama_vendor;
        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', "Vendor \"{$name}\" berhasil dihapus.");
    }
}
