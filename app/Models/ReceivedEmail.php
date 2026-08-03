<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReceivedEmail extends Model
{
    protected $fillable = [
        'receivable_type',
        'receivable_id',
        'message_id',
        'from_email',
        'from_name',
        'to_email',
        'cc_email',
        'bcc_email',
        'subject',
        'body_text',
        'body_html',
        'attachments',
        'received_at',
        'read_at',
        'processed',
        'user_id',
    ];

    protected $casts = [
        'attachments' => 'array',
        'received_at' => 'datetime',
        'read_at' => 'datetime',
        'processed' => 'boolean',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getFromAttribute(): string
    {
        return trim($this->from_name.' <'.$this->from_email.'>');
    }

    public function getIsReadAttribute(): bool
    {
        return ! is_null($this->read_at);
    }

    public function getHasAttachmentsAttribute(): bool
    {
        return ! empty($this->attachments);
    }

    public function getBodyPreviewAttribute(): string
    {
        $body = $this->body_html ?: $this->body_text;
        
        return strip_tags($body);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('received_at', 'desc');
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function markAsProcessed(): void
    {
        $this->update(['processed' => true]);
    }

    public function getAttachmentPaths(): array
    {
        return collect($this->attachments)->map(function ($attachment) {
            return storage_path('app/emails/'.$attachment['path']);
        })->toArray();
    }

    // ── Relations ────────────────────────────────────────────────────

    public function receivable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
