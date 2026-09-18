<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mainpower extends Model
{
    protected $fillable = [
        'mp',
        'thr',
        'uang_makan',
        'bpjs_kes',
        'bpjs_kes_percent',
        'bpjs_tk',
        'bpjs_tk_percent',
        'total',
        'lembur_per_jam',
    ];

    // Accessors to match the Phase 4 logic where I used $mp->bpjskes
    public function getBpjskesAttribute(): float
    {
        return (float) ($this->attributes['bpjs_kes'] ?? 0);
    }

    public function getBpjstkAttribute(): float
    {
        return (float) ($this->attributes['bpjs_tk'] ?? 0);
    }
}
