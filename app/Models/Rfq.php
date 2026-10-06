<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rfq_number', 'customer_id', 'customer_name', 'customer_code',
        'sales_id', 'sales_name', 'rfq_date', 'status', 'notes',
        'need_date', 'customer_contact_id', 'type', 'priority', 'revision_notes',
        'po_file_path', 'tax_type'
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'need_date' => 'date',
    ];

    public const STATUS_PENDING_ADMIN = 'Pending Admin';
    public const STATUS_PENDING_LEADER = 'Pending Leader';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_QUOTATION_CREATED = 'Quotation Created';
    public const STATUS_QUOTATION_SENT = 'Quotation Sent';
    public const STATUS_CANCELLED = 'Cancelled';
    
    // Alur PO & GOAL
    public const STATUS_PO_PENDING_ADMIN = 'PO Received (Pending Admin)';
    public const STATUS_PO_PENDING_LEADER = 'PO Received (Pending Leader)';
    public const STATUS_GOAL = 'GOAL';

    // Tingkat Urgensi / Priority
    public const PRIORITY_NORMAL = 'Normal';
    public const PRIORITY_URGENT = 'Urgent';
    public const PRIORITY_HIGH = 'High Priority';

    // Format Pajak Penawaran (Tax Type)
    public const TAX_AUTO = 'auto';
    public const TAX_INCLUDE = 'include';
    public const TAX_EXCLUDE = 'exclude';

    public function isIncludeTax(): bool
    {
        if ($this->tax_type === self::TAX_INCLUDE) {
            return true;
        }

        if ($this->tax_type === self::TAX_EXCLUDE) {
            return false;
        }

        // Auto-detect berdasarkan tipe customer
        $type = null;
        if ($this->relationLoaded('customer')) {
            $type = $this->customer?->customer_type;
        } elseif (!empty($this->customer_id)) {
            $type = $this->customer?->customer_type;
        }

        if (!empty($type)) {
            return strcasecmp($type, 'Perorangan') === 0 || strcasecmp($type, 'Pribadi') === 0;
        }

        // Fallback: deteksi dari nama customer
        $raw = trim($this->customer_name ?? '');
        if (preg_match('/\s*,\s*(Perorangan|Pribadi)$/i', $raw) || preg_match('/\b(Perorangan|Pribadi)\b/i', $raw)) {
            return true;
        }

        return false;
    }

    public function isPendingAdmin(): bool
    {
        return $this->status === self::STATUS_PENDING_ADMIN;
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowed = match ($this->status) {
            self::STATUS_PENDING_ADMIN => [
                self::STATUS_PENDING_LEADER,
            ],
            self::STATUS_PENDING_LEADER => [
                self::STATUS_APPROVED,
                self::STATUS_PENDING_ADMIN,
                self::STATUS_PENDING_LEADER,
            ],
            self::STATUS_APPROVED => [
                self::STATUS_PENDING_LEADER,
                self::STATUS_APPROVED,
                self::STATUS_QUOTATION_CREATED,
                self::STATUS_QUOTATION_SENT,
                self::STATUS_GOAL,
                self::STATUS_PENDING_ADMIN,
                self::STATUS_PO_PENDING_ADMIN,
            ],
            self::STATUS_QUOTATION_CREATED => [
                self::STATUS_PENDING_LEADER,
                self::STATUS_APPROVED,
                self::STATUS_QUOTATION_CREATED,
                self::STATUS_QUOTATION_SENT,
                self::STATUS_GOAL,
                self::STATUS_PENDING_ADMIN,
                self::STATUS_PO_PENDING_ADMIN,
            ],
            self::STATUS_QUOTATION_SENT => [
                self::STATUS_PENDING_LEADER,
                self::STATUS_APPROVED,
                self::STATUS_QUOTATION_SENT,
                self::STATUS_GOAL,
                self::STATUS_PENDING_ADMIN,
                self::STATUS_PO_PENDING_ADMIN,
            ],
            self::STATUS_PO_PENDING_ADMIN => [
                self::STATUS_PO_PENDING_LEADER,
            ],
            self::STATUS_PO_PENDING_LEADER => [
                self::STATUS_GOAL,
            ],
            default => [],
        };

        return in_array($targetStatus, $allowed, true);
    }

    public function canBePriceSubmitted(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_ADMIN,
            self::STATUS_PENDING_LEADER,
            self::STATUS_APPROVED,
            self::STATUS_QUOTATION_CREATED,
            self::STATUS_QUOTATION_SENT,
        ], true);
    }

    public function getNextActionLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_ADMIN => 'Menunggu Admin Purchase mengisi harga',
            self::STATUS_PENDING_LEADER => 'Menunggu approval Leader',
            self::STATUS_APPROVED => 'Siap membuat quotation',
            self::STATUS_QUOTATION_CREATED => 'Quotation sudah dibuat',
            self::STATUS_QUOTATION_SENT => 'Quotation terkirim ke klien',
            self::STATUS_PO_PENDING_ADMIN => 'Menunggu verifikasi PO Admin',
            self::STATUS_PO_PENDING_LEADER => 'Menunggu approval GOAL Leader',
            self::STATUS_GOAL => 'Sudah menjadi GOAL',
            default => 'Tahap sedang diproses',
        };
    }

    public function getWorkflowProgressPercent(): int
    {
        return match ($this->status) {
            self::STATUS_PENDING_ADMIN => 20,
            self::STATUS_PENDING_LEADER => 40,
            self::STATUS_APPROVED => 60,
            self::STATUS_QUOTATION_CREATED => 70,
            self::STATUS_QUOTATION_SENT => 75,
            self::STATUS_PO_PENDING_ADMIN => 80,
            self::STATUS_PO_PENDING_LEADER => 90,
            self::STATUS_GOAL => 100,
            default => 0,
        };
    }

    public function getWorkflowStageLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_ADMIN => 'Tahap 1 - Persiapan harga',
            self::STATUS_PENDING_LEADER => 'Tahap 2 - Menunggu leader',
            self::STATUS_APPROVED => 'Tahap 3 - RFQ disetujui',
            self::STATUS_QUOTATION_CREATED => 'Tahap 4 - Quotation dibuat',
            self::STATUS_QUOTATION_SENT => 'Tahap 4 - Penawaran terkirim',
            self::STATUS_PO_PENDING_ADMIN => 'Tahap 5 - Menunggu verifikasi PO',
            self::STATUS_PO_PENDING_LEADER => 'Tahap 6 - Menunggu GOAL',
            self::STATUS_GOAL => 'Tahap 7 - GOAL selesai',
            default => 'Tahap belum jelas',
        };
    }

    public function canBeEdited(): bool
    {
        return $this->isPendingAdmin();
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function customerContact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id')->withTrashed();
    }

    public static function generateRfqNumber(): string
    {
        $ymd = date('ymd'); // YYMMDD
        $year = date('Y');
        
        // Cari urutan penawaran terakhir di tahun ini
        $last = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();
            
        // Reset urutan per tahun
        $next = $last ? (int) substr($last->rfq_number, -4) + 1 : 1;
        
        return $ymd . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function getGrandTotalAttribute()
    {
        return $this->items->filter(fn($item) => empty($item->parent_id))->sum(function($item) {
            return $item->qty * $item->price_after_margin;
        });
    }

    public function getQuotationNumberAttribute(): string
    {
        $date = $this->rfq_date ? \Carbon\Carbon::parse($this->rfq_date) : ($this->created_at ?? now());
        $seq = '0001';
        if ($this->rfq_number && preg_match('/(\d+)$/', $this->rfq_number, $matches)) {
            $seq = str_pad($matches[1], 4, '0', STR_PAD_LEFT);
        } else {
            $seq = str_pad((string) ($this->id ?? 1), 4, '0', STR_PAD_LEFT);
        }

        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romanMonths[(int) $date->format('n')] ?? $date->format('m');
        $year = $date->format('Y');

        return 'Q' . $seq . '/PTI/' . $romanMonth . '/' . $year;
    }

    public function getResolvedCustomerAddressAttribute(): string
    {
        if ($this->customer) {
            return $this->customer->full_address;
        }

        return '';
    }

    public function setCustomerNameAttribute($value): void
    {
        $this->attributes['customer_name'] = Customer::formatTitleCase($value);
    }

    public function getResolvedPicNameAttribute(): string
    {
        $name = $this->customerContact?->name;
        if (empty($name) && $this->customer) {
            $name = $this->customer->resolved_pic_name;
        }

        $clean = preg_replace('/^(up[\.\:\s]+)/i', '', trim($name ?? ''));
        return Customer::formatTitleCase($clean) ?: 'Bpk/Ibu';
    }

    public function getResolvedCustomerPhoneAttribute(): ?string
    {
        $raw = $this->customer?->phone 
            ?: ($this->customer?->office_phone 
            ?: ($this->customerContact?->office_phone 
            ?: ($this->customerContact?->phone 
            ?: ($this->customer?->primaryContact()?->phone 
            ?: ($this->customer?->primaryContact()?->office_phone 
            ?: ($this->customer?->cp_phone ?? null))))));

        if (!$raw) {
            return null;
        }

        $clean = preg_replace('/^(telp?[\.\:\s]+)/i', '', trim($raw));
        return !empty($clean) ? $clean : null;
    }

    public function getResolvedCompanyNameAttribute(): string
    {
        if ($this->customer) {
            return $this->customer->formal_company_name;
        }

        $raw = trim($this->customer_name ?? '');
        if ($raw === '') {
            return 'Pelanggan';
        }

        $detectedType = null;
        if (preg_match('/^PT\.?\s+/i', $raw) || preg_match('/\s*,\s*PT\.?$/i', $raw)) {
            $detectedType = 'PT';
        } elseif (preg_match('/^CV\.?\s+/i', $raw) || preg_match('/\s*,\s*CV\.?$/i', $raw)) {
            $detectedType = 'CV';
        } elseif (preg_match('/\s*,\s*Perorangan$/i', $raw)) {
            $detectedType = 'Perorangan';
        } elseif (preg_match('/\s*,\s*Pemerintah$/i', $raw)) {
            $detectedType = 'Pemerintah';
        }

        $clean = preg_replace('/^(PT\.?|CV\.?)\s+/i', '', $raw);
        $clean = preg_replace('/\s*,\s*(PT\.?|CV\.?|Perorangan|Pemerintah)$/i', '', $clean);
        $clean = Customer::formatTitleCase($clean) ?? 'Pelanggan';

        if (!empty($detectedType)) {
            return $clean . ', ' . $detectedType;
        }

        return $clean;
    }

    public function getValidUntilDateAttribute(): \Carbon\Carbon
    {
        $raw = $this->rfq_date ?: ($this->created_at ?: now());
        $baseDate = \Carbon\Carbon::parse($raw);
        $days = (int) ($this->items->max('validity_days') ?: 3);
        if ($days <= 0 || $days === 7) {
            $days = 3;
        }

        return $baseDate->copy()->addWeekdays($days);
    }

    public function getFormattedValidUntilAttribute(): string
    {
        return $this->valid_until_date->format('d M Y');
    }

    // Koleksi item untuk dokumen resmi Quotation PDF dan Preview
    // Baris internal (MP dan Akomodasi Pedia) disembunyikan dari tabel dan nilainya
    // dilebur ke baris Jasa Pemasangan utama agar total penawaran tetap utuh dan akurat
    public function getCustomerQuotationItemsAttribute(): \Illuminate\Support\Collection
    {
        $items = $this->items;
        if ($items->isEmpty()) {
            return collect();
        }

        $visibleItems = [];
        $internalLaborTotal = 0.0;
        $internalMiscTotal = 0.0;

        foreach ($items as $item) {
            // Komponen anak di dalam paket rakitan tidak ditampilkan sebagai baris mandiri
            if ($item->isComponent()) {
                continue;
            }

            if ($item->isInternalLaborOrAccommodation()) {
                $unitPrice = (float) $item->price_after_margin;
                $rowTotal = $unitPrice * (float) $item->qty;
                $internalLaborTotal += $rowTotal;
            } elseif ($item->isMiscellaneousMaterial()) {
                $unitPrice = (float) $item->price_after_margin;
                $rowTotal = $unitPrice * (float) $item->qty;
                $internalMiscTotal += $rowTotal;
            } else {
                $visibleItems[] = $item;
            }
        }

        $mapItem = function ($it, $extraTotal = 0) {
            $isBundle = $it->isBundle();
            $comps = $isBundle ? ($it->relationLoaded('components') ? $it->components : $it->components()->get()) : collect();

            if ($isBundle && $comps->isNotEmpty()) {
                $compSellingTotal = (float) $comps->sum(function ($c) {
                    $cPrice = (float) $c->price_after_margin;
                    $cQty = (float) ($c->qty ?: 1);
                    return $cPrice * $cQty;
                });
                $unitPrice = $compSellingTotal;
                $compList = $comps->map(function ($c) {
                    $cQty = (int) $c->qty;
                    $prefix = $cQty > 1 ? "({$cQty}x) " : "";
                    return "- " . $prefix . $c->product_name;
                })->implode("\n");

                $desc = !empty($it->description) ? ($it->description . "\n" . $compList) : $compList;
            } else {
                $unitPrice = (float) $it->price_after_margin;
                $desc = $it->description ?: $it->detail_item;
            }

            $qty = (float) $it->qty;
            $rowTotal = ($unitPrice * $qty) + $extraTotal;
            if ($extraTotal > 0 && $qty > 0) {
                $unitPrice = $rowTotal / $qty;
            }

            return (object) [
                'id' => $it->id,
                'qty' => $qty,
                'unit' => $it->unit ?: 'Unit',
                'product_name' => $it->product_name,
                'description' => $desc,
                'detail_item' => $it->detail_item,
                'unit_price' => $unitPrice,
                'row_total' => $rowTotal,
                'category' => $it->category,
                'is_absorbed' => $extraTotal > 0,
                'is_bundle' => $isBundle,
            ];
        };

        if ($internalLaborTotal <= 0 && $internalMiscTotal <= 0) {
            return collect($visibleItems)->map(fn ($it) => $mapItem($it, 0));
        }

        // Cari item penampung untuk material miscellaneous
        $absorbedMaterialIdx = null;
        if ($internalMiscTotal > 0) {
            foreach ($visibleItems as $idx => $it) {
                $cat = RfqItem::normalizeCategory($it->category);
                $name = strtolower($it->product_name ?? '');
                if ($cat === RfqItem::CATEGORY_MATERIAL || str_contains($name, 'material') || str_contains($name, 'kabel') || str_contains($name, 'cable') || str_contains($name, 'pipa')) {
                    $absorbedMaterialIdx = $idx;
                    break;
                }
            }
            // Jika tidak ada item material support khusus, leburkan ke penampung jasa
            if ($absorbedMaterialIdx === null) {
                $internalLaborTotal += $internalMiscTotal;
                $internalMiscTotal = 0.0;
            }
        }

        // Cari item penampung untuk labor, akomodasi, dan mobdemob
        $absorbedLaborIdx = null;
        if ($internalLaborTotal > 0) {
            foreach ($visibleItems as $idx => $it) {
                $cat = RfqItem::normalizeCategory($it->category);
                $name = strtolower($it->product_name ?? '');
                if ($cat === RfqItem::CATEGORY_JASA || str_contains($name, 'jasa') || str_contains($name, 'instalasi') || str_contains($name, 'installation') || str_contains($name, 'setting')) {
                    $absorbedLaborIdx = $idx;
                    break;
                }
            }
        }

        $extraMap = array_fill(0, count($visibleItems), 0.0);
        if ($absorbedMaterialIdx !== null && $internalMiscTotal > 0) {
            $extraMap[$absorbedMaterialIdx] += $internalMiscTotal;
        }

        $hasSyntheticService = false;
        if ($internalLaborTotal > 0) {
            if ($absorbedLaborIdx !== null) {
                $extraMap[$absorbedLaborIdx] += $internalLaborTotal;
            } elseif (!empty($visibleItems) && $this->type === 'Non Projek') {
                // Pada Non Projek tanpa baris jasa terpisah, leburkan ke item pertama
                $extraMap[0] += $internalLaborTotal;
            } else {
                $hasSyntheticService = true;
            }
        }

        $result = [];
        foreach ($visibleItems as $idx => $it) {
            $extra = $extraMap[$idx] ?? 0.0;
            $result[] = $mapItem($it, $extra);
        }

        if ($hasSyntheticService) {
            $result[] = (object) [
                'id' => 'absorbed-service',
                'qty' => 1,
                'unit' => 'Lot',
                'product_name' => 'Jasa Instalasi & Setting Operasional',
                'description' => 'Jasa instalasi perangkat, penarikan kabel, konfigurasi sistem, dan pengetesan fungsi operasional.',
                'detail_item' => null,
                'unit_price' => $internalLaborTotal,
                'row_total' => $internalLaborTotal,
                'category' => RfqItem::CATEGORY_JASA,
                'is_absorbed' => true,
                'is_bundle' => false,
            ];
        }

        return collect($result);
    }
}
