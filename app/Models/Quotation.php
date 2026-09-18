<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'rfq_id', 'quo_number', 'revision_number',
        'customer_id', 'sales_id', 'created_by',
        'status', 'file_path', 'notes', 'valid_until',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'valid_until'     => 'date',
    ];

    public const STATUS_DRAFT    = 'Draft';
    public const STATUS_SENT     = 'Sent';
    public const STATUS_APPROVED = 'Approved'; // Disetujui Leader, siap diproses GOAL oleh Sales

    public function isDraft(): bool    { return $this->status === self::STATUS_DRAFT; }
    public function isSent(): bool     { return $this->status === self::STATUS_SENT; }
    public function isApproved(): bool { return $this->status === self::STATUS_APPROVED; }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowed = match ($this->status) {
            self::STATUS_DRAFT => [self::STATUS_SENT],
            self::STATUS_SENT => [self::STATUS_APPROVED],
            default => [],
        };

        return in_array($targetStatus, $allowed, true);
    }

    public static function generateQuoNumber(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)->orderByDesc('id')->first();
        $next = $last ? (int) substr($last->quo_number, 1, 4) + 1 : 1;

        $bulanRomawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        $romanMonth = $bulanRomawi[(int) date('n')];

        return 'Q' . str_pad($next, 4, '0', STR_PAD_LEFT) . '/PTI/' . $romanMonth . '/' . $year;
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }
}
