<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number', 'customer_id', 'sales_id', 'rfq_id', 'rfq_reference',
        'po_date', 'received_date', 'due_date',
        'subtotal', 'tax_amount', 'grand_total', 'estimated_cost',
        'file_attachment', 'file_path',
        'status', 'approved_by', 'approved_at', 'notes', 'rejection_reason',
        'change_request_reason',
    ];

    protected $casts = [
        'po_date'        => 'date',
        'received_date'  => 'date',
        'due_date'       => 'date',
        'approved_at'    => 'datetime',
        'subtotal'       => 'decimal:2',
        'tax_amount'     => 'decimal:2',
        'grand_total'    => 'decimal:2',
        'estimated_cost' => 'decimal:2',
    ];

    // Status Constants (sesuai request user)
    public const STATUS_PENDING = 'Pending';
    public const STATUS_GOAL = 'Goal';
    public const STATUS_TIDAK_GOAL = 'Tidak Goal';
    public const STATUS_REVISI = 'Revisi';

    // ─── Helpers ─────────────────────────────────────────────────────────────
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isGoal(): bool
    {
        return $this->status === self::STATUS_GOAL;
    }

    public function isRevisi(): bool
    {
        return $this->status === self::STATUS_REVISI;
    }

    public function canBeEdited(): bool
    {
        return $this->isPending() || $this->isRevisi();
    }

    // ─── Relationships ────────────────────────────────────────────────────────
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id')->withTrashed();
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function approvalHistories(): MorphMany
    {
        return $this->morphMany(ApprovalHistory::class, 'approvable');
    }

    // Auto-generate PO number: PO-2026-0001
    public static function generatePoNumber(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)->orderByDesc('id')->first();
        $next = $last ? (int) substr($last->po_number, -4) + 1 : 1;
        return 'PO-' . $year . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
