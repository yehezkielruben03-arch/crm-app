<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_id',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_room_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'chat_room_id')->latestOfMany();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ChatRoomRead::class, 'chat_room_id');
    }

    /**
     * Scope rooms visible to a specific user based on RBAC.
     * Sales can only see their own room; Admin and Leader see all rooms.
     */
    public function scopeAccessibleFor($query, User $user)
    {
        if ($user->isSales() && !$user->isAdminOrAbove() && !$user->isLeader()) {
            return $query->where('sales_id', $user->id);
        }

        return $query->where('is_active', true);
    }

    /**
     * Calculate unread message count for a specific user in this room.
     */
    public function unreadCountFor(User $user): int
    {
        $lastRead = $this->reads()->where('user_id', $user->id)->first();
        $lastReadId = $lastRead?->last_read_message_id ?? 0;

        return $this->messages()
            ->where('id', '>', $lastReadId)
            ->where('user_id', '!=', $user->id)
            ->count();
    }

    /**
     * Mark all messages in this room as read for a specific user.
     */
    public function markAsReadFor(User $user): void
    {
        $latestId = $this->messages()->max('id') ?? 0;

        ChatRoomRead::updateOrCreate(
            ['chat_room_id' => $this->id, 'user_id' => $user->id],
            ['last_read_message_id' => $latestId]
        );
    }

    /**
     * Get or automatically create the dedicated chat room for a Sales user.
     */
    public static function getOrCreateForSales(User $sales): self
    {
        return self::firstOrCreate(
            ['sales_id' => $sales->id],
            [
                'name'      => $sales->name,
                'slug'      => 'sales-' . $sales->id . '-' . Str::slug($sales->name),
                'is_active' => true,
            ]
        );
    }
}
