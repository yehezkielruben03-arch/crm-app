<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqItem extends Model
{
    protected $fillable = [
        'rfq_id', 'parent_id', 'is_bundle', 'category', 'product_name', 'qty', 'unit', 'detail_item', 'description',
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
            if (!empty($item->parent_id)) {
                continue;
            }

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
        'is_bundle' => 'boolean',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(RfqItem::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class, 'parent_id');
    }

    public function isBundle(): bool
    {
        if ((bool) $this->is_bundle) {
            return true;
        }
        if (!empty($this->parent_id)) {
            return false;
        }
        if ($this->relationLoaded('components')) {
            return $this->components->isNotEmpty();
        }
        if (!$this->exists) {
            return false;
        }
        return $this->components()->exists();
    }

    public function isComponent(): bool
    {
        return !empty($this->parent_id);
    }

    public function getBundleSellingPriceAttribute(): float
    {
        $comps = $this->relationLoaded('components') ? $this->components : $this->components()->get();
        if ($comps->isEmpty()) {
            return (float) $this->price_after_margin;
        }

        return (float) $comps->sum(function ($c) {
            $unitP = (float) $c->price_after_margin;
            $qty = (float) ($c->qty ?: 1);
            return $unitP * $qty;
        });
    }

    public function getBundleTotalCostAttribute(): float
    {
        $comps = $this->relationLoaded('components') ? $this->components : $this->components()->get();
        if ($comps->isEmpty()) {
            return (float) $this->hpp + (float) $this->ongkir_pedia + (float) $this->biaya_kirim + (float) $this->fee_eu;
        }

        return (float) $comps->sum(function ($c) {
            $unitCost = (float) $c->hpp + (float) $c->ongkir_pedia + (float) $c->biaya_kirim + (float) $c->fee_eu;
            $qty = (float) ($c->qty ?: 1);
            return $unitCost * $qty;
        });
    }

    // Menentukan apakah item merupakan tenaga kerja teknisi, akomodasi, atau mobdemob internal Pedia
    // yang tidak ditampilkan langsung di Quotation PDF klien
    public function isInternalLaborOrAccommodation(): bool
    {
        $name = strtolower(trim($this->product_name ?? ''));
        $vendor = strtolower(trim($this->vendor?->nama_vendor ?? ($this->attributes['vendor_name'] ?? '')));

        if (str_contains($name, 'mp team pedia') 
            || str_contains($name, 'mainpower pedia') 
            || str_contains($name, 'mp pedia') 
            || str_contains($name, 'tarif mp')) {
            return true;
        }

        if (str_contains($name, 'akomodasi') 
            || str_contains($name, 'transport pedia') 
            || str_contains($name, 'operasional pedia')) {
            return true;
        }

        if (str_contains($name, 'mobdemob') 
            || str_contains($name, 'mob demob') 
            || str_contains($name, 'mob-demob') 
            || str_contains($name, 'mobilisasi') 
            || str_contains($name, 'demobilisasi')) {
            return true;
        }

        if (str_contains($vendor, 'mainpower pedia') 
            || str_contains($vendor, 'operasional pedia') 
            || str_contains($vendor, 'mobdemob')) {
            return true;
        }

        return false;
    }

    // Menentukan apakah item merupakan material bantu atau miscellaneous
    public function isMiscellaneousMaterial(): bool
    {
        $name = strtolower(trim($this->product_name ?? ''));
        return str_contains($name, 'miscelanious') || str_contains($name, 'miscellaneous');
    }

    // Menentukan apakah item disembunyikan dari tabel Quotation PDF klien
    public function isHiddenFromQuotation(): bool
    {
        return $this->isInternalLaborOrAccommodation() || $this->isMiscellaneousMaterial();
    }

    /**
     * Calculate price after margin and ceiling.
     * Margin disimpan dalam format PERSEN (cth: 25 = 25%).
     * Formula: CEIL(((HPP + Ongkir Pedia + Ongkir Pelanggan) * (1 + Margin/100)) / Ceiling) * Ceiling
     */
    public function calculatePriceAfterMargin(): float
    {
        if ($this->isBundle()) {
            $comps = $this->relationLoaded('components') ? $this->components : $this->components()->get();
            if ($comps->isNotEmpty()) {
                return $this->bundle_selling_price;
            }
        }

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
