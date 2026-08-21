<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmReportEmailLog extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'crm_report_email_logs';

    protected $fillable = [
        'execution_uuid',
        'idempotency_key',
        'report_key',
        'report_type',
        'scope',
        'user_id',
        'recipient_email',
        'subject',
        'status',
        'message_id',
        'error_class',
        'error_message',
        'started_at',
        'sent_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
