<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatRoomParticipant extends Model
{
    protected $fillable = [
        'chat_room_id',
        'user_id',
        'role',
        'last_read_at',
        'actif',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'actif' => 'boolean',
    ];

    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeMember($query)
    {
        return $query->where('role', 'member');
    }
}
