<?php

namespace App\Helpers;

/**
 * IndonesiaRegion - Helper Dropdown Berantai Wilayah Indonesia
 * Data sumber: emsifa.github.io (berdasarkan data resmi Kemendagri)
 */
class IndonesiaRegion
{
    private const BASE = 'https://emsifa.github.io/api-wilayah-indonesia/api';
    private static array $cache = [];

    /** Kembalikan 34 nama Provinsi, diurutkan A-Z */
    public static function provinces(): array
    {
        if (isset(self::$cache['provinces'])) return self::$cache['provinces'];
        try {
            $data  = self::fetch('/provinces.json');
            $names = array_column($data, 'name');
            sort($names);
            return self::$cache['provinces'] = $names;
        } catch (\Throwable) { return []; }
    }

    /** Kota/Kabupaten berdasarkan nama Provinsi */
    public static function citiesByProvince(string $prov): array
    {
        try {
            $id = self::provinceId($prov);
            if (!$id) return [];
            return array_values(array_column(self::fetch('/regencies/' . $id . '.json'), 'name'));
        } catch (\Throwable) { return []; }
    }

    /** Kecamatan berdasarkan Provinsi + Kota */
    public static function districtsByCity(string $prov, string $city): array
    {
        try {
            $id = self::cityId($prov, $city);
            if (!$id) return [];
            return array_values(array_column(self::fetch('/districts/' . $id . '.json'), 'name'));
        } catch (\Throwable) { return []; }
    }

    /**
     * Kelurahan/Desa + Kode Pos berdasarkan Provinsi + Kota + Kecamatan.
     * Return: [['name' => 'Kranji', 'postal_code' => '17134'], ...]
     */
    public static function villagesByDistrict(string $prov, string $city, string $district): array
    {
        try {
            $cid = self::cityId($prov, $city);
            if (!$cid) return [];

            $distList = self::fetch('/districts/' . $cid . '.json');
            $did = null;
            foreach ($distList as $d) {
                if (mb_strtolower($d['name']) === mb_strtolower($district)) {
                    $did = $d['id'];
                    break;
                }
            }
            if (!$did) return [];

            return array_map(
                fn($v) => ['name' => $v['name'], 'postal_code' => $v['postal_code'] ?? ''],
                self::fetch('/villages/' . $did . '.json')
            );
        } catch (\Throwable) { return []; }
    }

    // ── Helpers Internal ────────────────────────────────────

    private static function provinceId(string $name): ?string
    {
        $k = 'pid_' . md5($name);
        if (isset(self::$cache[$k])) return self::$cache[$k];
        foreach (self::fetch('/provinces.json') as $p) {
            if (mb_strtolower($p['name']) === mb_strtolower($name)) {
                return self::$cache[$k] = (string) $p['id'];
            }
        }
        return null;
    }

    private static function cityId(string $prov, string $city): ?string
    {
        $k = 'cid_' . md5($prov . $city);
        if (isset(self::$cache[$k])) return self::$cache[$k];
        $pid = self::provinceId($prov);
        if (!$pid) return null;
        foreach (self::fetch('/regencies/' . $pid . '.json') as $r) {
            if (mb_strtolower($r['name']) === mb_strtolower($city)) {
                return self::$cache[$k] = (string) $r['id'];
            }
        }
        return null;
    }

    private static function fetch(string $path): array
    {
        $url = self::BASE . $path;
        if (isset(self::$cache[$url])) return self::$cache[$url];
        
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->withoutVerifying()->get($url);
            if (!$response->successful()) {
                throw new \RuntimeException('Gagal fetch HTTP status: ' . $response->status());
            }
            return self::$cache[$url] = $response->json() ?? [];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Region Fetch Error: ' . $e->getMessage() . ' URL: ' . $url);
            throw new \RuntimeException('Gagal fetch: ' . $url);
        }
    }
}
