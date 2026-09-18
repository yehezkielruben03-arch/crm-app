<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    // Tabel ini tidak punya kolom id auto-increment
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'role_name', 'permission_name',
    ];

    /**
     * Check if a role has a specific permission
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        return self::where('role_name', $role)
            ->where('permission_name', $permission)
            ->exists();
    }
}
