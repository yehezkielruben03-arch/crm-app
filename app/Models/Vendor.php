<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    const STATUS_ACTIVE = 'Active';
    const STATUS_INACTIVE = 'Inactive';

    protected $fillable = [
        'nama_vendor',
        'alamat',
        'kontak',
        'pic',
        'npwp',
        'kategori',
        'bank',
        'rekening',
        'status',
    ];

    public static function categories(): array
    {
        return [
            'Hardware & IT',
            'CCTV & Access Control',
            'Networking & Fiber Optic',
            'Mechanical & Electrical',
            'Jasa & Subcon',
            'General Trading & Office',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
