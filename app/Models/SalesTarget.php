<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTarget extends Model
{
    protected $fillable = [
        'sales_id', 'target_month', 'target_year', 'target_amount', 'actual_amount',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
    ];

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id')->withTrashed();
    }

    // Hitung persentase pencapaian
    public function getAchievementPercentAttribute(): float
    {
        if ($this->target_amount <= 0) return 0;
        return round(($this->actual_amount / $this->target_amount) * 100, 2);
    }

    // Sisa target yang belum tercapai
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->target_amount - $this->actual_amount);
    }
}
