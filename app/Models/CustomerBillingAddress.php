<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBillingAddress extends Model
{
    protected $fillable = [
        'customer_id', 'label', 'address', 'city', 'province', 'postal_code', 'country',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
