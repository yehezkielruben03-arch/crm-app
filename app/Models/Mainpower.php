<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mainpower extends Model
{
    protected $fillable = ['mp', 'thr', 'bpjs_kes', 'bpjs_tk', 'total'];

    // Accessors to match the Phase 4 logic where I used $mp->bpjskes
    public function getBpjskesAttribute()
    {
        return $this->attributes['bpjs_kes'] ?? 0;
    }

    public function getBpjstkAttribute()
    {
        return $this->attributes['bpjs_tk'] ?? 0;
    }
}
