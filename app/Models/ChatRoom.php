<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom',
        'description',
        'type',
        'created_by',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatRoomParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function addParticipant(User $user, string $role = 'member'): ChatRoomParticipant
    {
        return $this->participants()->updateOrCreate(
            ['user_id' => $user->id],
            ['role' => $role, 'actif' => true]
        );
    }

    public function removeParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->update(['actif' => false]);
    }

    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }

    public function scopeGroup($query)
    {
        return $query->where('type', 'group');
    }

    public function scopeChannel($query)
    {
        return $query->where('type', 'channel');
    }

    public function getUnreadCountForUser(User $user): int
    {
        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->whereColumn('messages.created_at', '>', 'chat_room_participants.last_read_at');
            })
            ->count();
    }
}
