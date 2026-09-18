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

    public const CATEGORY_ACTIVE = 'Active Equipment';
    public const CATEGORY_PASSIVE = 'Passive Equipment';
    public const CATEGORY_CONSUMABLES = 'Consumables';
    public const CATEGORY_SERVICE = 'Professional Service';
    public const CATEGORY_TRAINING = 'Training Support';

    /**
     * Urutan sub-kategori statis mengikuti format Excel (romawi III & IV di-skip).
     * Kunci = nomor romawi, nilai = label kanonik yang disimpan di database.
     */
    public const CATEGORY_ORDER = [
        'I'   => 'Active Equipment',
        'II'  => 'Passive Equipment',
        'V'   => 'Consumables',
        'VI'  => 'Professional Services',
        'VII' => 'Training & Support',
    ];

    /**
     * Normalisasi varian teks kategori lama agar selalu tampil dengan label
     * kanonik Excel (mis. 'Professional Service' -> 'Professional Services').
     */
    private static function normalizeCategory(?string $cat): ?string
    {
        if (empty($cat)) {
            return null;
        }

        $variants = [
            'Active Equipment'     => 'Active Equipment',
            'Passive Equipment'    => 'Passive Equipment',
            'Consumables'          => 'Consumables',
            'Professional Service' => 'Professional Services',
            'Professional Services'=> 'Professional Services',
            'Training Support'     => 'Training & Support',
            'Training & Support'   => 'Training & Support',
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
            $withMargin = $baseCost + $this->margin_value;
        } else {
            $multiplier = 1 + ($this->margin_value / 100);
            $withMargin = $baseCost * $multiplier;
        }

        $ceiling = $this->custom_ceiling > 0 ? $this->custom_ceiling : 1;
        $calculated = ceil($withMargin / $ceiling) * $ceiling;

        return $calculated;
    }
}
