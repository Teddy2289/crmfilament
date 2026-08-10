<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'chat_room_id',
        'user_id',
        'contenu',
        'lu',
        'lu_at',
        'reply_to',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'lu_at' => 'datetime',
    ];

    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to');
    }

    public function markAsRead(): void
    {
        $this->update([
            'lu' => true,
            'lu_at' => now(),
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('lu', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
