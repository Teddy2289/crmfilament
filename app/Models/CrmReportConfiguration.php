<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CrmReportConfiguration extends Model
{
    public const TYPE_DAILY = 'daily';
    public const TYPE_WEEKLY = 'weekly';
    public const TYPE_CAMPAIGNS = 'campaigns';

    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';

    public const RECIPIENT_USERS = 'users';
    public const RECIPIENT_ROLES = 'roles';
    public const RECIPIENT_EMAILS = 'emails';

    public const PERIOD_PREVIOUS_DAY = 'previous_day';
    public const PERIOD_PREVIOUS_WEEK = 'previous_week';
    public const PERIOD_PREVIOUS_MONTH = 'previous_month';
    public const PERIOD_CURRENT = 'current';

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

    protected $table = 'crm_report_configurations';

    protected $fillable = [
        'name',
        'slug',
        'report_type',
        'description',
        'active',
        'frequency',
        'execution_time',
        'timezone',
        'weekdays',
        'recipient_mode',
        'recipient_user_ids',
        'recipient_roles',
        'recipient_emails',
        'sections',
        'period_type',
        'options',
        'last_run_at',
        'next_run_at',
        'last_status',
        'last_error',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'weekdays' => 'array',
            'recipient_user_ids' => 'array',
            'recipient_roles' => 'array',
            'recipient_emails' => 'array',
            'sections' => 'array',
            'options' => 'array',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $configuration): void {
            $configuration->slug ??= Str::slug($configuration->name).'-'.Str::lower(Str::random(6));
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function isScheduledWeekly(): bool
    {
        return $this->frequency === self::FREQUENCY_WEEKLY;
    }

    public function recipientCount(): int
    {
        $value = match ($this->recipient_mode) {
            self::RECIPIENT_USERS => $this->recipient_user_ids,
            self::RECIPIENT_ROLES => $this->recipient_roles,
            self::RECIPIENT_EMAILS => $this->recipient_emails,
            default => null,
        };

        if (is_array($value)) {
            return count($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return count($decoded);
            }

            return trim($value) === '' ? 0 : 1;
        }

        return 0;
    }

    public function scheduleDescription(): string
    {
        $time = substr((string) $this->execution_time, 0, 5);
        $timezone = $this->timezone ?: 'Europe/Paris';

        if (! $this->isScheduledWeekly()) {
            return "Chaque jour à {$time} ({$timezone})";
        }

        $labels = [
            'monday' => 'lundi',
            'tuesday' => 'mardi',
            'wednesday' => 'mercredi',
            'thursday' => 'jeudi',
            'friday' => 'vendredi',
            'saturday' => 'samedi',
            'sunday' => 'dimanche',
        ];

        $days = collect($this->weekdays ?? ['monday'])
            ->map(fn (string $day): string => $labels[$day] ?? $day)
            ->implode(', ');

        return "Chaque {$days} à {$time} ({$timezone})";
    }

    public function markRunStarted(?Carbon $nextRunAt = null): void
    {
        $this->forceFill([
            'last_run_at' => now(),
            'last_status' => self::STATUS_PENDING,
            'last_error' => null,
            'next_run_at' => $nextRunAt,
        ])->save();
    }

    public function markRunCompleted(string $status, ?string $error = null): void
    {
        $this->forceFill([
            'last_status' => $status,
            'last_error' => $error,
        ])->save();
    }
}
