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
        'need_date', 'customer_contact_id', 'type', 'priority', 'revision_notes'
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'need_date' => 'date',
    ];

    public const STATUS_PENDING_ADMIN = 'Pending Admin';
    public const STATUS_PENDING_LEADER = 'Pending Leader';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_QUOTATION_CREATED = 'Quotation Created';
    public const STATUS_CANCELLED = 'Cancelled';
    
    // Alur PO & GOAL
    public const STATUS_PO_PENDING_ADMIN = 'PO Received (Pending Admin)';
    public const STATUS_PO_PENDING_LEADER = 'PO Received (Pending Leader)';
    public const STATUS_GOAL = 'GOAL';

    // Tingkat Urgensi / Priority
    public const PRIORITY_NORMAL = 'Normal';
    public const PRIORITY_URGENT = 'Urgent';
    public const PRIORITY_HIGH = 'High Priority';

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
            ],
            self::STATUS_APPROVED => [
                self::STATUS_QUOTATION_CREATED,
                self::STATUS_GOAL,
                self::STATUS_PENDING_ADMIN,
            ],
            self::STATUS_QUOTATION_CREATED => [
                self::STATUS_GOAL,
                self::STATUS_PENDING_ADMIN,
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
        return $this->status === self::STATUS_PENDING_ADMIN;
    }

    public function getNextActionLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_ADMIN => 'Menunggu Admin Purchase mengisi harga',
            self::STATUS_PENDING_LEADER => 'Menunggu approval Leader',
            self::STATUS_APPROVED => 'Siap membuat quotation',
            self::STATUS_QUOTATION_CREATED => 'Quotation sudah dibuat',
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
        return $this->items->sum(function($item) {
            return $item->qty * $item->price_after_margin;
        });
    }
}
