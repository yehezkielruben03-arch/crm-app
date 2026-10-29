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

    /**
     * Format teks wilayah menjadi Title Case (huruf besar di awal kata),
     * mempertahankan singkatan resmi seperti DKI, DI, dan angka Romawi.
     */
    public static function formatTitleCase(?string $name): ?string
    {
        $trimmed = trim($name ?? '');
        if ($trimmed === '') return null;

        $normalized = preg_replace('/\s+/', ' ', $trimmed);
        $title = mb_convert_case(mb_strtolower($normalized, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        // Pertahankan singkatan resmi wilayah Indonesia
        $replacements = [
            '/\bDki\b/u' => 'DKI',
            '/\bDi\b/u'  => 'DI',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $title = preg_replace($pattern, $replacement, $title);
        }

        // Pertahankan angka Romawi (contoh: Delta Silicon II, Blok IV)
        $title = preg_replace_callback('/\b(ii|iii|iv|vi|vii|viii|ix|x)\b/i', fn($m) => strtoupper($m[0]), $title);

        return $title;
    }

    /** Kembalikan 34 nama Provinsi dalam format Title Case, diurutkan A-Z */
    public static function provinces(): array
    {
        if (isset(self::$cache['provinces'])) return self::$cache['provinces'];
        try {
            $data  = self::fetch('/provinces.json');
            $names = array_map([self::class, 'formatTitleCase'], array_column($data, 'name'));
            sort($names);
            return self::$cache['provinces'] = $names;
        } catch (\Throwable) { return []; }
    }

    /** Kota/Kabupaten berdasarkan nama Provinsi (Title Case) */
    public static function citiesByProvince(string $prov): array
    {
        try {
            $id = self::provinceId($prov);
            if (!$id) return [];
            $raw = array_column(self::fetch('/regencies/' . $id . '.json'), 'name');
            return array_values(array_map([self::class, 'formatTitleCase'], $raw));
        } catch (\Throwable) { return []; }
    }

    /** Kecamatan berdasarkan Provinsi + Kota (Title Case) */
    public static function districtsByCity(string $prov, string $city): array
    {
        try {
            $id = self::cityId($prov, $city);
            if (!$id) return [];
            $raw = array_column(self::fetch('/districts/' . $id . '.json'), 'name');
            return array_values(array_map([self::class, 'formatTitleCase'], $raw));
        } catch (\Throwable) { return []; }
    }

    /**
     * Kelurahan/Desa + Kode Pos berdasarkan Provinsi + Kota + Kecamatan (Title Case).
     * Return: [['name' => 'Kranji', 'postal_code' => '17134'], ...]
     */
    public static function villagesByDistrict(string $prov, string $city, string $district): array
    {
        try {
            $cid = self::cityId($prov, $city);
            if (!$cid) return [];

            $distList = self::fetch('/districts/' . $cid . '.json');
            $did = null;
            $cleanDistrict = mb_strtolower(trim($district));
            foreach ($distList as $d) {
                if (mb_strtolower(trim($d['name'])) === $cleanDistrict) {
                    $did = $d['id'];
                    break;
                }
            }
            if (!$did) return [];

            return array_map(
                fn($v) => [
                    'name' => self::formatTitleCase($v['name']),
                    'postal_code' => $v['postal_code'] ?? ''
                ],
                self::fetch('/villages/' . $did . '.json')
            );
        } catch (\Throwable) { return []; }
    }

    // ── Helpers Internal ────────────────────────────────────

    private static function provinceId(string $name): ?string
    {
        $cleanName = mb_strtolower(trim($name));
        $k = 'pid_' . md5($cleanName);
        if (isset(self::$cache[$k])) return self::$cache[$k];
        foreach (self::fetch('/provinces.json') as $p) {
            if (mb_strtolower(trim($p['name'])) === $cleanName) {
                return self::$cache[$k] = (string) $p['id'];
            }
        }
        return null;
    }

    private static function cityId(string $prov, string $city): ?string
    {
        $cleanProv = mb_strtolower(trim($prov));
        $cleanCity = mb_strtolower(trim($city));
        $k = 'cid_' . md5($cleanProv . $cleanCity);
        if (isset(self::$cache[$k])) return self::$cache[$k];
        $pid = self::provinceId($prov);
        if (!$pid) return null;
        foreach (self::fetch('/regencies/' . $pid . '.json') as $r) {
            if (mb_strtolower(trim($r['name'])) === $cleanCity) {
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
