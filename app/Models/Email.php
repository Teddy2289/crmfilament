<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Email extends Model
{
    protected $fillable = [
        'emailable_type',
        'emailable_id',
        'type', // sent, received, draft
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
        'sent_at',
        'received_at',
        'read_at',
        'user_id',
        'folder', // inbox, sent, drafts, trash, archive
        'priority', // low, normal, high
        'labels', // array de labels
    ];

    protected $casts = [
        'attachments' => 'array',
        'labels' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // ── Constants ───────────────────────────────────────────────────

    const TYPE_SENT = 'sent';
    const TYPE_RECEIVED = 'received';
    const TYPE_DRAFT = 'draft';

    const FOLDER_INBOX = 'inbox';
    const FOLDER_SENT = 'sent';
    const FOLDER_DRAFTS = 'drafts';
    const FOLDER_TRASH = 'trash';
    const FOLDER_ARCHIVE = 'archive';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getFromAttribute(): string
    {
        return trim($this->from_name.' <'.$this->from_email.'>');
    }

    public function getToAttribute(): string
    {
        return $this->to_email;
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

    public function getFolderLabelAttribute(): string
    {
        return match ($this->folder) {
            self::FOLDER_INBOX => 'Boîte de réception',
            self::FOLDER_SENT => 'Envoyés',
            self::FOLDER_DRAFTS => 'Brouillons',
            self::FOLDER_TRASH => 'Corbeille',
            self::FOLDER_ARCHIVE => 'Archives',
            default => $this->folder,
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_NORMAL => 'Normale',
            self::PRIORITY_HIGH => 'Haute',
            default => 'Normale',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'primary',
            self::PRIORITY_HIGH => 'danger',
            default => 'gray',
        };
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeSent($query)
    {
        return $query->where('type', self::TYPE_SENT);
    }

    public function scopeReceived($query)
    {
        return $query->where('type', self::TYPE_RECEIVED);
    }

    public function scopeDrafts($query)
    {
        return $query->where('type', self::TYPE_DRAFT);
    }

    public function scopeInbox($query)
    {
        return $query->where('folder', self::FOLDER_INBOX);
    }

    public function scopeSentFolder($query)
    {
        return $query->where('folder', self::FOLDER_SENT);
    }

    public function scopeDraftsFolder($query)
    {
        return $query->where('folder', self::FOLDER_DRAFTS);
    }

    public function scopeTrash($query)
    {
        return $query->where('folder', self::FOLDER_TRASH);
    }

    public function scopeArchive($query)
    {
        return $query->where('folder', self::FOLDER_ARCHIVE);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('received_at', 'desc')->orderBy('sent_at', 'desc');
    }

    public function scopeWithLabel($query, $label)
    {
        return $query->whereJsonContains('labels', $label);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', self::PRIORITY_HIGH);
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

    public function moveToFolder(string $folder): void
    {
        $this->update(['folder' => $folder]);
    }

    public function moveToTrash(): void
    {
        $this->moveToFolder(self::FOLDER_TRASH);
    }

    public function archive(): void
    {
        $this->moveToFolder(self::FOLDER_ARCHIVE);
    }

    public function restore(): void
    {
        if ($this->type === self::TYPE_SENT) {
            $this->moveToFolder(self::FOLDER_SENT);
        } else {
            $this->moveToFolder(self::FOLDER_INBOX);
        }
    }

    public function addLabel(string $label): void
    {
        $labels = $this->labels ?? [];
        if (! in_array($label, $labels)) {
            $labels[] = $label;
            $this->update(['labels' => $labels]);
        }
    }

    public function removeLabel(string $label): void
    {
        $labels = $this->labels ?? [];
        $labels = array_filter($labels, fn ($l) => $l !== $label);
        $this->update(['labels' => array_values($labels)]);
    }

    public function setPriority(string $priority): void
    {
        $this->update(['priority' => $priority]);
    }

    public function getAttachmentPaths(): array
    {
        return collect($this->attachments)->map(function ($attachment) {
            return storage_path('app/emails/'.$attachment['path']);
        })->toArray();
    }

    // ── Relations ────────────────────────────────────────────────────

    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(Email::class, 'in_reply_to', 'message_id');
    }

    public function parent()
    {
        return $this->belongsTo(Email::class, 'in_reply_to', 'message_id');
    }
}
