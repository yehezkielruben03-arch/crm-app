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
    // Mengikuti format resmi penawaran:
    // Urutan 1: Seluruh item Hardware terurut di baris-baris teratas
    // Urutan 2: Seluruh item Material Support dirangkum menjadi 1 baris di tengah bertajuk Material Support
    // Urutan 3: Seluruh item Jasa Pemasangan, Mobdemob, MP, dan Akomodasi dirangkum 1 baris di paling bawah bertajuk Installation & Accommodation
    // Baris Note otomatis berada di baris terakhir tepat di bawah rincian pekerjaan jasa
    public function getCustomerQuotationItemsAttribute(): \Illuminate\Support\Collection
    {
        $items = $this->items;
        if ($items->isEmpty()) {
            return collect();
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

        // Filter komponen anak dalam paket rakitan
        $nonComponents = $items->filter(fn($item) => !$item->isComponent());

        // Alur untuk RFQ tipe Projek:
        if ($this->type === 'Projek') {
            $hardwareItems = [];
            $materialItems = [];
            $jasaItems = [];

            foreach ($nonComponents as $item) {
                $normCat = RfqItem::normalizeCategory($item->category);
                $name = strtolower(trim($item->product_name ?? ''));

                if ($item->isInternalLaborOrAccommodation()
                    || $normCat === RfqItem::CATEGORY_JASA
                    || str_contains($name, 'installation')
                    || str_contains($name, 'instalasi')
                    || str_contains($name, 'jasa')
                    || str_contains($name, 'akomodasi')
                    || str_contains($name, 'mobdemob')
                    || str_contains($name, 'setting operasional')
                    || str_contains($name, 'testing & commissioning')) {
                    $jasaItems[] = $item;
                } elseif ($item->isMiscellaneousMaterial()
                    || $normCat === RfqItem::CATEGORY_MATERIAL
                    || str_contains($name, 'material')
                    || str_contains($name, 'cable')
                    || str_contains($name, 'kabel')
                    || str_contains($name, 'consumable')
                    || str_contains($name, 'roset')
                    || str_contains($name, 'panel')
                    || str_contains($name, 'pipa')
                    || str_contains($name, 'patch cord')
                    || str_contains($name, 'connector')) {
                    $materialItems[] = $item;
                } else {
                    $hardwareItems[] = $item;
                }
            }

            $output = [];

            // 1. HARDWARE ROWS: Tampilkan setiap item hardware terurut di atas
            foreach ($hardwareItems as $hw) {
                $output[] = $mapItem($hw, 0);
            }

            // 2. MATERIAL SUPPORT ROW: Rangkum seluruh item material menjadi 1 baris
            if (!empty($materialItems)) {
                $materialTotal = 0.0;
                $partNames = [];
                $customMaterialDesc = null;

                $hasMisc = false;
                foreach ($materialItems as $mat) {
                    $matRowTotal = (float) $mat->price_after_margin * (float) ($mat->qty ?: 1);
                    $materialTotal += $matRowTotal;
                    if ($mat->isMiscellaneousMaterial()) {
                        $hasMisc = true;
                        continue;
                    }
                    $pName = trim($mat->product_name ?? '');
                    if (!empty($pName) && strcasecmp($pName, 'Material Support') !== 0) {
                        $partNames[] = $pName;
                    }
                    $sDesc = trim($mat->description ?: $mat->detail_item ?: '');
                    if (!empty($sDesc) && empty($customMaterialDesc) && !str_contains(strtolower($sDesc), 'material pendukung & habis')) {
                        $customMaterialDesc = $sDesc;
                    }
                }

                if ($hasMisc) {
                    $consumableIdx = null;
                    foreach ($partNames as $idx => $p) {
                        if (stripos($p, 'consumable') !== false) {
                            $consumableIdx = $idx;
                            break;
                        }
                    }
                    if ($consumableIdx !== null) {
                        $partNames[$consumableIdx] = 'Consumable and Miscelanious Material';
                    } elseif (!in_array('Consumable and Miscelanious Material', $partNames)) {
                        $partNames[] = 'Consumable and Miscelanious Material';
                    }
                }

                if (count($partNames) > 1) {
                    $materialDesc = implode(', ', $partNames);
                } elseif (count($partNames) === 1) {
                    $materialDesc = $customMaterialDesc ?: $partNames[0];
                } else {
                    $materialDesc = $customMaterialDesc ?: 'Material pendukung instalasi dan aksesoris perkabelan.';
                }

                $output[] = (object) [
                    'id' => 'bundled-material',
                    'qty' => 1,
                    'unit' => 'Unit',
                    'product_name' => 'Material Support',
                    'description' => $materialDesc,
                    'detail_item' => null,
                    'unit_price' => $materialTotal,
                    'row_total' => $materialTotal,
                    'category' => RfqItem::CATEGORY_MATERIAL,
                    'is_absorbed' => count($materialItems) > 1,
                    'is_bundle' => false,
                ];
            }

            // 3. JASA PEMASANGAN & AKOMODASI ROW: Rangkum seluruh jasa & mobdemob menjadi 1 baris di paling bawah
            if (!empty($jasaItems)) {
                $jasaTotal = 0.0;
                $jasaTitle = 'Installation & Accommodation';
                $collectedDescs = [];

                foreach ($jasaItems as $js) {
                    $jsRowTotal = (float) $js->price_after_margin * (float) ($js->qty ?: 1);
                    $jasaTotal += $jsRowTotal;

                    $pName = trim($js->product_name ?? '');
                    if (!$js->isInternalLaborOrAccommodation() && !empty($pName)) {
                        $jasaTitle = $pName;
                    }

                    if (!$js->isInternalLaborOrAccommodation()) {
                        $sDesc = trim($js->description ?: $js->detail_item ?: '');
                        if (!empty($sDesc) && !in_array($sDesc, $collectedDescs)) {
                            $collectedDescs[] = $sDesc;
                        }
                    }
                }

                $desc = !empty($collectedDescs) 
                    ? implode("\n", $collectedDescs) 
                    : 'Jasa instalasi perangkat, penarikan kabel, konfigurasi sistem, dan pengetesan fungsi operasional.';

                $output[] = (object) [
                    'id' => 'bundled-jasa',
                    'qty' => 1,
                    'unit' => 'Unit',
                    'product_name' => $jasaTitle,
                    'description' => $desc,
                    'detail_item' => null,
                    'unit_price' => $jasaTotal,
                    'row_total' => $jasaTotal,
                    'category' => RfqItem::CATEGORY_JASA,
                    'is_absorbed' => count($jasaItems) > 1,
                    'is_bundle' => false,
                ];
            }

            return collect($output);
        }

        // Alur untuk RFQ tipe Non Projek:
        // Urutkan kategori secara konsisten: Hardware -> Material Support -> Jasa Pemasangan.
        // Item internal diserap ke item penampung agar penawaran rapi.
        $visibleHardware = [];
        $visibleMaterial = [];
        $visibleJasa = [];
        $internalLaborTotal = 0.0;
        $internalMiscTotal = 0.0;

        foreach ($nonComponents as $item) {
            if ($item->isInternalLaborOrAccommodation()) {
                $unitPrice = (float) $item->price_after_margin;
                $rowTotal = $unitPrice * (float) $item->qty;
                $internalLaborTotal += $rowTotal;
            } elseif ($item->isMiscellaneousMaterial()) {
                $unitPrice = (float) $item->price_after_margin;
                $rowTotal = $unitPrice * (float) $item->qty;
                $internalMiscTotal += $rowTotal;
            } else {
                $normCat = RfqItem::normalizeCategory($item->category);
                $name = strtolower(trim($item->product_name ?? ''));

                if ($normCat === RfqItem::CATEGORY_JASA || str_contains($name, 'jasa') || str_contains($name, 'instalasi')) {
                    $visibleJasa[] = $item;
                } elseif ($normCat === RfqItem::CATEGORY_MATERIAL || str_contains($name, 'material') || str_contains($name, 'kabel')) {
                    $visibleMaterial[] = $item;
                } else {
                    $visibleHardware[] = $item;
                }
            }
        }

        $orderedVisible = array_merge($visibleHardware, $visibleMaterial, $visibleJasa);

        if (empty($orderedVisible)) {
            return collect();
        }

        $extraMap = array_fill(0, count($orderedVisible), 0.0);

        if ($internalMiscTotal > 0) {
            $matIdx = null;
            foreach ($orderedVisible as $idx => $it) {
                $normCat = RfqItem::normalizeCategory($it->category);
                if ($normCat === RfqItem::CATEGORY_MATERIAL) {
                    $matIdx = $idx;
                    break;
                }
            }
            if ($matIdx !== null) {
                $extraMap[$matIdx] += $internalMiscTotal;
            } else {
                $extraMap[0] += $internalMiscTotal;
            }
        }

        if ($internalLaborTotal > 0) {
            $jasaIdx = null;
            foreach ($orderedVisible as $idx => $it) {
                $normCat = RfqItem::normalizeCategory($it->category);
                if ($normCat === RfqItem::CATEGORY_JASA) {
                    $jasaIdx = $idx;
                    break;
                }
            }
            if ($jasaIdx !== null) {
                $extraMap[$jasaIdx] += $internalLaborTotal;
            } else {
                $extraMap[0] += $internalLaborTotal;
            }
        }

        $result = [];
        foreach ($orderedVisible as $idx => $it) {
            $extra = $extraMap[$idx] ?? 0.0;
            $result[] = $mapItem($it, $extra);
        }

        return collect($result);
    }
}
