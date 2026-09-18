<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_code', 'company_name', 'brand_name', 'customer_type',
        'industry', 'company_scale',
        'area_category', 'address', 'city', 'district', 'village',
        'province', 'postal_code', 'country', 'region',
        'website', 'phone', 'email', 'npwp', 'nib',
        'status', 'sales_id', 'created_by', 'approved_by', 'approved_at',
        'notes', 'cp_name', 'cp_position', 'cp_email', 'cp_phone',
        'division', 'office_phone', 'whatsapp', 'preferred_contact',
        'ongkir_pedia',
    ];

    protected static function booted()
    {
        static::created(function ($customer) {
            $cpName = $customer->cp_name ?? '';
            if (!empty(trim($cpName))) {
                $customer->contacts()->create([
                    'name' => trim($cpName),
                    'position' => $customer->cp_position ?? null,
                    'email' => $customer->cp_email ?? null,
                    'phone' => $customer->cp_phone ?? null,
                    'office_phone' => $customer->office_phone ?? null,
                    'whatsapp' => $customer->whatsapp ?? null,
                    'division' => $customer->division ?? null,
                    'preferred_contact' => $customer->preferred_contact ?? null,
                    'is_primary' => true
                ]);
            }
        });
    }


    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Status Constants
    public const STATUS_PROSPECT = 'Prospect';
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_BLACKLIST = 'Blacklist';
    // Legacy statuses kept for backward compatibility
    public const STATUS_PENDING = 'Pending';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_LEAD = 'Lead';

    public static function statusMapping(): array
    {
        return [
            'Prospect' => 'Prospect',
            'Pending'  => 'Prospect',
            'Lead'     => 'Prospect',
            'Active'   => 'Active',
            'Inactive' => 'Inactive',
            'Rejected' => 'Blacklist',
            'Blacklist'=> 'Blacklist',
        ];
    }

    public static function normalizeStatus(?string $status): string
    {
        $map = self::statusMapping();
        return $map[$status] ?? 'Prospect';
    }

    public static function pendingApprovalStatuses(): array
    {
        return [self::STATUS_PROSPECT, self::STATUS_PENDING, self::STATUS_LEAD];
    }

    // ─── Scopes (Untuk mempermudah filter query) ─────────────────────────────
    
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeProspect(Builder $query): Builder
    {
        return $query->whereIn('status', ['Prospect', 'Pending', 'Lead']);
    }

    // ─── Relationships ────────────────────────────────────────────────────────
    
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function billingAddresses(): HasMany
    {
        return $this->hasMany(CustomerBillingAddress::class);
    }

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(CustomerShippingAddress::class);
    }

    public function primaryContact()
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function approvalHistories(): MorphMany
    {
        return $this->morphMany(ApprovalHistory::class, 'approvable');
    }

    // ─── Computed Attributes ──────────────────────────────────────────────────

    public function getTotalRevenueAttribute(): float
    {
        return $this->purchaseOrders()->where('status', PurchaseOrder::STATUS_GOAL)->sum('grand_total');
    }

    // Generate company code format: PTIyyyymmddxxxxxxx
    // Dibungkus DB::transaction + lockForUpdate untuk mencegah race condition
    // saat 2 Admin melakukan approve bersamaan di milidetik yang sama.
    public static function generateCompanyCode(): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $prefix = 'PTI' . now()->format('Ymd');
            // lockForUpdate() mengunci baris yang sedang dibaca agar query lain
            // menunggu sampai transaksi ini selesai — mencegah kode kembar.
            $last = self::where('company_code', 'LIKE', $prefix . '%')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            $next = $last ? (int) substr($last->company_code, -7) + 1 : 1;
            return $prefix . str_pad($next, 7, '0', STR_PAD_LEFT);
        });
    }

    public static function resolveSalesOwner(?int $salesId = null): int
    {
        if (!empty($salesId)) {
            return $salesId;
        }

        $existingOwner = User::whereIn('role', ['Sales', 'Sales Marketing'])
            ->where('status', 'Active')
            ->orderBy('id')
            ->first();

        if ($existingOwner) {
            return (int) $existingOwner->id;
        }

        $fallbackOwner = User::create([
            'name' => 'Sales Marketing Default',
            'username' => 'sales-marketing-default',
            'email' => 'sales.marketing.default@crm.local',
            'password' => Hash::make('password'),
            'role' => 'Sales',
            'status' => 'Active',
        ]);

        return (int) $fallbackOwner->id;
    }
}
