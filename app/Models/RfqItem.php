<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqItem extends Model
{
    protected $fillable = [
        'rfq_id', 'category', 'product_name', 'qty', 'unit', 'detail_item', 'description',
        'hpp', 'ongkir_pedia', 'ongkir_pelanggan', 'margin', 'ceiling', 'validity_days', 'price_after_margin',
        'biaya_kirim', 'fee_eu', 'margin_type', 'margin_value', 'custom_ceiling', 'vendor_id'
    ];

    public const CATEGORY_HARDWARE = 'Hardware';
    public const CATEGORY_JASA = 'Jasa Pemasangan';
    public const CATEGORY_MATERIAL = 'Material Support';

    public const CATEGORY_ORDER = [
        'I'   => 'Hardware',
        'II'  => 'Jasa Pemasangan',
        'III' => 'Material Support',
    ];

    /**
     * Normalisasi varian teks kategori lama agar konsisten dengan 3 Blok HPP & Quotation:
     * 1. Hardware (Active Equipment, dll)
     * 2. Jasa Pemasangan (Jasa, Professional Service, dll)
     * 3. Material Support (Material, Passive Equipment, Consumables, dll)
     */
    public static function normalizeCategory(?string $cat): ?string
    {
        if (empty($cat)) {
            return null;
        }

        $variants = [
            'Hardware'             => 'Hardware',
            'Active Equipment'     => 'Hardware',
            'Jasa'                 => 'Jasa Pemasangan',
            'Jasa Pemasangan'      => 'Jasa Pemasangan',
            'Professional Service' => 'Jasa Pemasangan',
            'Professional Services'=> 'Jasa Pemasangan',
            'Training Support'     => 'Jasa Pemasangan',
            'Training & Support'   => 'Jasa Pemasangan',
            'Material'             => 'Material Support',
            'Material Support'     => 'Material Support',
            'Passive Equipment'    => 'Material Support',
            'Consumables'          => 'Material Support',
        ];

        return $variants[$cat] ?? $cat;
    }

    /**
     * Label tampilan untuk sebuah kategori: awalan romawi statis untuk kategori
     * yang dikenal, nama hasil normalisasi untuk kategori lain, dan
     * 'Tanpa Kategori' untuk nilai null/kosong.
     */
    public static function categoryLabel(?string $cat): string
    {
        $normalized = self::normalizeCategory($cat);

        if ($normalized === null) {
            return 'Tanpa Kategori';
        }

        foreach (self::CATEGORY_ORDER as $numeral => $label) {
            if ($label === $normalized) {
                return $numeral . '. ' . $label;
            }
        }

        return $normalized;
    }

    /**
     * Kelompokkan item projek mengikuti urutan statis CATEGORY_ORDER.
     * Item berkategori tak dikenal / tanpa kategori dilempar ke kelompok
     * fallback 'Tanpa Kategori' di bagian paling akhir.
     *
     * @return array<int, array{numeral: string|null, label: string, items: \Illuminate\Support\Collection}>
     */
    public static function groupProjects($items): array
    {
        $buckets = array_fill_keys(array_values(self::CATEGORY_ORDER), []);
        $fallback = [];

        foreach ($items as $item) {
            $normalized = self::normalizeCategory($item->category ?? null);

            if ($normalized !== null && isset($buckets[$normalized])) {
                $buckets[$normalized][] = $item;
            } else {
                $fallback[] = $item;
            }
        }

        $groups = [];
        foreach (self::CATEGORY_ORDER as $numeral => $label) {
            if (!empty($buckets[$label])) {
                $groups[] = [
                    'numeral' => $numeral,
                    'label'   => $numeral . '. ' . $label,
                    'items'   => collect($buckets[$label]),
                ];
            }
        }

        if (!empty($fallback)) {
            $groups[] = [
                'numeral' => null,
                'label'   => 'Tanpa Kategori',
                'items'   => collect($fallback),
            ];
        }

        return $groups;
    }

    protected $casts = [
        'qty' => 'decimal:2',
        'hpp' => 'decimal:2',
        'ongkir_pedia' => 'decimal:2',
        'ongkir_pelanggan' => 'decimal:2',
        'margin' => 'decimal:2',
        'price_after_margin' => 'decimal:2',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Calculate price after margin and ceiling.
     * Margin disimpan dalam format PERSEN (cth: 25 = 25%).
     * Formula: CEIL(((HPP + Ongkir Pedia + Ongkir Pelanggan) * (1 + Margin/100)) / Ceiling) * Ceiling
     */
    public function calculatePriceAfterMargin(): float
    {
        $baseCost = $this->hpp + $this->ongkir_pedia + $this->biaya_kirim + $this->fee_eu;
        
        $withMargin = $baseCost;
        if ($this->margin_type === 'nominal') {
            $withMargin = $baseCost + (float) $this->margin_value;
        } else {
            $marginVal = (float) $this->margin_value;
            $profit = $baseCost * ($marginVal / 100);
            // Aturan minimum estimasi untung: Rp 50.000 jika terdapat modal dasar
            if ($baseCost > 0 && $profit < 50000) {
                $profit = 50000;
            }
            $withMargin = $baseCost + $profit;
        }

        $ceiling = $this->custom_ceiling > 0 ? $this->custom_ceiling : ($this->ceiling > 0 ? $this->ceiling : 1);
        $calculated = ceil($withMargin / $ceiling) * $ceiling;

        return $calculated;
    }
}
