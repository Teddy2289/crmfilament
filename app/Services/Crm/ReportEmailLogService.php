<?php

namespace App\Services\Crm;

use App\Models\CrmReportEmailLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class ReportEmailLogService
{
    public function begin(
        string $reportKey,
        string $recipientEmail,
        ?User $user = null,
        string $reportType = 'daily',
        ?string $scope = null,
        ?string $subject = null,
        ?string $executionUuid = null,
        array $metadata = [],
    ): ?CrmReportEmailLog {
        $executionUuid ??= (string) Str::uuid();
        $idempotencyKey = $this->idempotencyKey($reportKey, $recipientEmail, $reportType);

        $existing = CrmReportEmailLog::query()
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', CrmReportEmailLog::STATUS_SENT)
            ->first();

        if ($existing) {
            return null;
        }

        $log = CrmReportEmailLog::query()->firstOrNew([
            'idempotency_key' => $idempotencyKey,
        ]);

        $log->fill([
            'execution_uuid' => $executionUuid,
            'report_key' => $reportKey,
            'report_type' => $reportType,
            'scope' => $scope,
            'user_id' => $user?->getKey(),
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'status' => CrmReportEmailLog::STATUS_PENDING,
            'error_class' => null,
            'error_message' => null,
            'started_at' => Carbon::now(),
            'failed_at' => null,
            'metadata' => $metadata,
        ]);
        $log->save();

        return $log;
    }

    public function markSent(CrmReportEmailLog $log, ?string $messageId = null, array $metadata = []): void
    {
        $log->update([
            'status' => CrmReportEmailLog::STATUS_SENT,
            'message_id' => $messageId,
            'sent_at' => Carbon::now(),
            'metadata' => array_merge($log->metadata ?? [], $metadata),
        ]);
    }

    public function markFailed(CrmReportEmailLog $log, Throwable $exception): void
    {
        $log->update([
            'status' => CrmReportEmailLog::STATUS_FAILED,
            'error_class' => $exception::class,
            'error_message' => Str::limit($exception->getMessage(), 2000),
            'failed_at' => Carbon::now(),
        ]);
    }

    public function idempotencyKey(string $reportKey, string $recipientEmail, string $reportType = 'daily'): string
    {
        return implode(':', [
            'crm-report',
            $reportType,
            $reportKey,
            Carbon::now()->format('Y-m-d'),
            sha1(mb_strtolower(trim($recipientEmail))),
        ]);
    }
}

