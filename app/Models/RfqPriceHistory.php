<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfqPriceHistory extends Model
{
    protected $fillable = ['rfq_id', 'version', 'history_data', 'created_by'];

    protected $casts = [
        'history_data' => 'array',
    ];

    public function rfq()
    {
        return $this->belongsTo(Rfq::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
