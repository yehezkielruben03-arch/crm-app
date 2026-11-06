<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'message',
        'message_type',
        'rfq_id',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    public function isRfqCard(): bool
    {
        return $this->message_type === 'rfq_card' && !empty($this->rfq_id);
    }

    /**
     * Format message payload for frontend client polling and rendering.
     */
    public function toPayloadFor(User $viewer): array
    {
        $sender = $this->user;
        $isLeader = $sender?->isLeader() ?? false;
        $isAdmin = $sender?->isAdminOrAbove() ?? false;
        $isSales = $sender?->isSales() ?? false;

        $roleBadge = 'Sales';
        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';

        if ($isLeader) {
            $roleBadge = 'Leader';
            $badgeClass = 'bg-indigo-50 text-indigo-700 border-indigo-200';
        } elseif ($isAdmin) {
            $roleBadge = 'Admin';
            $badgeClass = 'bg-amber-50 text-amber-700 border-amber-300';
        }

        $rfqData = null;
        if ($this->isRfqCard() && $this->rfq) {
            $rfq = $this->rfq;
            $grandTotalFormatted = null;
            try {
                if ($rfq->grand_total) {
                    $grandTotalFormatted = 'Rp ' . number_format($rfq->grand_total, 0, ',', '.');
                }
            } catch (\Throwable $e) {
                $grandTotalFormatted = null;
            }

            $rfqData = [
                'id'            => $rfq->id,
                'rfq_number'    => $rfq->rfq_number,
                'customer_name' => $rfq->customer_name,
                'status'        => $rfq->status,
                'grand_total'   => $grandTotalFormatted,
                'detail_url'    => route('rfq.show', $rfq),
                'pdf_url'       => route('rfq.download_quotation', $rfq),
            ];
        }

        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'user_name'           => $sender?->name ?? 'User',
            'user_role'           => $roleBadge,
            'badge_class'         => $badgeClass,
            'user_initial'        => strtoupper(substr($sender?->name ?? 'U', 0, 1)),
            'message'             => $this->message,
            'message_type'        => $this->message_type,
            'rfq'                 => $rfqData,
            'created_at_time'     => $this->created_at ? $this->created_at->format('H:i') : '',
            'created_at_human'    => $this->created_at ? $this->created_at->diffForHumans() : '',
            'is_mine'             => ($this->user_id === $viewer->id),
        ];
    }
}
