<?php

namespace App\Jobs;

use App\Mail\WeeklyReportMail;
use App\Models\User;
use App\Services\Crm\ReportEmailLogService;
use App\Services\Crm\WeeklyReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie les rapports hebdomadaires CRM (CDC WF5 / WF6).
 */
class SendWeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(public array $roles = [
        User::ROLE_TELEPROSPECTEUR,
        User::ROLE_COMMERCIAL,
        User::ROLE_SUPERVISEUR,
        WeeklyReportService::ROLE_TEAM_LEADER,
    ]) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('crm-report-weekly-' . now()->format('o-W')))->expireAfter(7200),
        ];
    }

    public function handle(WeeklyReportService $service, ?ReportEmailLogService $emailLog = null): int
    {
        $emailLog ??= app(ReportEmailLogService::class);
        $envoyes = 0;
        $executionUuid = (string) \Illuminate\Support\Str::uuid();

        foreach ($service->destinatairesPourRoles($this->roles) as $user) {
            $rapport = match ($user->role_cache) {
                User::ROLE_TELEPROSPECTEUR => $service->pourTeleprospecteur($user),
                User::ROLE_COMMERCIAL => $service->pourCommercial($user),
                default => $service->pourTeamLeader($user),
            };

            $log = $emailLog->begin(
                reportKey: 'weekly',
                recipientEmail: $user->email,
                user: $user,
                reportType: 'weekly',
                scope: $user->role_cache,
                subject: 'Rapport hebdomadaire CRM — ' . ($user->prenom ?: $user->email),
                executionUuid: $executionUuid,
            );

            if (! $log) {
                continue;
            }

            try {
                $sentMessage = Mail::to($user->email)->send(new WeeklyReportMail($rapport));
                $emailLog->markSent($log, $sentMessage?->getMessageId());
                $envoyes++;
            } catch (\Throwable $exception) {
                $emailLog->markFailed($log, $exception);
                Log::error('Échec du rapport hebdomadaire CRM.', [
                    'recipient' => $user->email,
                    'exception' => $exception::class,
                ]);
            }
        }

        Log::info("Rapport hebdomadaire CRM envoye a {$envoyes} destinataire(s).");

        return $envoyes;
    }
}
