<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'phone',
        'status',
        'monthly_target',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Role Helpers ────────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool { return $this->role === 'Super Admin'; }
    public function isAdmin(): bool      { return in_array($this->role, ['Admin', 'Admin Purchase']); }
    public function isSales(): bool      { return in_array($this->role, ['Sales', 'Sales Marketing']); }
    public function isLeader(): bool     { return $this->role === 'Leader'; }

    public function getRoleDisplayNameAttribute(): string
    {
        return match($this->role) {
            'Sales' => 'Sales Marketing',
            'Admin' => 'Admin Purchase',
            default => $this->role,
        };
    }

    public function isAdminOrAbove(): bool
    {
        return in_array($this->role, ['Super Admin', 'Admin', 'Admin Purchase', 'Leader']);
    }

    public function hasPermission(string $permission): bool
    {
        // Pengecualian: Super Admin punya akses ke semuanya
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Guard: jika role null, tidak punya permission apapun
        if (empty($this->role)) {
            return false;
        }

        // Role name di tabel role_permissions menggunakan display name,
        // sedangkan users.role menyimpan short name. Normalisasikan.
        $roleName = match($this->role) {
            'Admin' => 'Admin Purchase',
            'Sales' => 'Sales Marketing',
            default => $this->role,
        };

        return RolePermission::hasPermission($roleName, $permission);
    }

    // ─── Relationships ────────────────────────────────────────────────────────────

    // Customer yang di-handle sales ini
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'sales_id');
    }

    // RFQ yang dibuat sales ini
    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class, 'sales_id');
    }

    // Purchase Orders yang dibuat sales ini
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'sales_id');
    }

    // Target bulanan sales ini
    public function salesTargets(): HasMany
    {
        return $this->hasMany(SalesTarget::class, 'sales_id');
    }

    // Target bulan ini
    public function currentMonthTarget()
    {
        return $this->salesTargets()
            ->where('target_month', now()->month)
            ->where('target_year', now()->year)
            ->first();
    }

    // Notifikasi yang diterima user ini
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // Jumlah notif belum dibaca (untuk badge lonceng)
    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }
}
