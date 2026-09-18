<?php

namespace App\Http\Controllers;

use App\Helpers\IndonesiaRegion;
use Illuminate\Http\Request;

/**
 * RegionController
 * ─────────────────────────────────────────────────────────────
 * Menyediakan endpoint AJAX untuk Dependent Select wilayah.
 *
 * ALGORITMA BERANTAI:
 * 1. Client kirim nama Provinsi → server kembalikan daftar Kota
 * 2. Client kirim nama Kota → server kembalikan daftar Kecamatan
 * 3. Client kirim nama Kecamatan → server kembalikan Kelurahan
 *    beserta kode posnya (untuk auto-fill)
 *
 * KEAMANAN: Semua endpoint dilindungi oleh auth middleware
 *   (hanya user yang sudah login yang bisa akses).
 * ─────────────────────────────────────────────────────────────
 */
class RegionController extends Controller
{
    /**
     * GET /region/cities?province=Jawa+Barat
     * Kembalikan daftar Kota/Kabupaten dalam Provinsi terpilih.
     */
    public function cities(Request $request)
    {
        $province = trim($request->query('province', ''));
        if (empty($province)) {
            return response()->json([]);
        }

        $cities = IndonesiaRegion::citiesByProvince($province);
        return response()->json($cities);
    }

    /**
     * GET /region/districts?province=Jawa+Barat&city=Kota+Bekasi
     * Kembalikan daftar Kecamatan dalam Kota terpilih.
     */
    public function districts(Request $request)
    {
        $province = trim($request->query('province', ''));
        $city     = trim($request->query('city', ''));
        if (empty($province) || empty($city)) {
            return response()->json([]);
        }

        $districts = IndonesiaRegion::districtsByCity($province, $city);
        return response()->json($districts);
    }

    /**
     * GET /region/villages?province=...&city=...&district=Bekasi+Barat
     * Kembalikan Kelurahan + Kode Pos untuk auto-fill.
     * Format: [{"name":"Kranji","postal_code":"17134"}, ...]
     */
    public function villages(Request $request)
    {
        $province = trim($request->query('province', ''));
        $city     = trim($request->query('city', ''));
        $district = trim($request->query('district', ''));
        if (empty($province) || empty($city) || empty($district)) {
            return response()->json([]);
        }

        $villages = IndonesiaRegion::villagesByDistrict($province, $city, $district);
        return response()->json(array_values($villages));
    }
}
