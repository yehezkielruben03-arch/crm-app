<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerShippingAddress extends Model
{
    protected $fillable = [
        'customer_id', 'label', 'address', 'city', 'province', 'postal_code', 'country',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getProvinceAttribute($value): ?string
    {
        return \App\Helpers\IndonesiaRegion::formatTitleCase($value);
    }

    public function setProvinceAttribute($value): void
    {
        $this->attributes['province'] = \App\Helpers\IndonesiaRegion::formatTitleCase($value);
    }

    public function getCityAttribute($value): ?string
    {
        return \App\Helpers\IndonesiaRegion::formatTitleCase($value);
    }

    public function setCityAttribute($value): void
    {
        $this->attributes['city'] = \App\Helpers\IndonesiaRegion::formatTitleCase($value);
    }
}
