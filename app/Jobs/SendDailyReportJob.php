<?php

namespace App\Jobs;

use App\Mail\DailyReportMail;
use App\Models\EmailConfiguration;
use App\Models\User;
use App\Services\Crm\DailyReportService;
use App\Services\Crm\ReportEmailLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDailyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $roles = [],
        public ?int $userId = null,
        public string $reportKey = 'daily',
        public string $executionUuid = '',
    ) {
        $this->executionUuid = $executionUuid ?: (string) Str::uuid();
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->lockKey()))->expireAfter(3600),
        ];
    }

<<<<<<< HEAD
    public function handle(DailyReportService $service, ?ReportEmailLogService $emailLog = null): int
    {
=======
    public function handle(
        DailyReportService $service,
        ?CrmSettingsService $settings = null,
        ?ReportEmailLogService $emailLog = null,
    ): int
    {
        $settings ??= app(CrmSettingsService::class);
>>>>>>> 624e1c0b (feat(reporting): journaliser les envois de rapports)
        $emailLog ??= app(ReportEmailLogService::class);
        $this->configureMailerFromActiveEmailConfiguration();

        $envoyes = 0;

        $destinataires = $this->userId !== null
            ? User::query()->whereKey($this->userId)->whereNotNull('email')->get()
            : $service->destinatairesPourRoles($this->roles ?: [
                User::ROLE_TELEPROSPECTEUR,
                User::ROLE_COMMERCIAL,
                User::ROLE_SUPERVISEUR,
                DailyReportService::ROLE_TEAM_LEADER,
            ]);

        foreach ($destinataires as $user) {
            $rapport = match ($user->role_cache) {
                User::ROLE_TELEPROSPECTEUR => $service->pourTeleprospecteur($user),
                User::ROLE_COMMERCIAL => $service->pourCommercial($user),
                default => $service->pourTeamLeader($user),
            };

            $prenom = $rapport['user']->prenom ?? '';
            $mailable = new DailyReportMail($rapport);
            $log = $emailLog->begin(
                reportKey: $this->reportKey,
                recipientEmail: $user->email,
                user: $user,
                reportType: 'daily',
                scope: $this->userId !== null ? 'targeted' : 'roles',
                subject: 'Rapport quotidien CRM'.($prenom ? " — {$prenom}" : ''),
                executionUuid: $this->executionUuid,
                metadata: ['role' => $user->role_cache],
            );

            if (! $log) {
                Log::warning('Rapport quotidien CRM ignoré : destinataire déjà traité.', [
                    'report_key' => $this->reportKey,
                    'recipient' => $user->email,
                ]);
                continue;
            }

            try {
                $sentMessage = Mail::mailer('smtp')->to($user->email)->send($mailable);
                $messageId = null;
                if (is_object($sentMessage) && method_exists($sentMessage, 'getSymfonySentMessage')) {
                    $messageId = $sentMessage->getSymfonySentMessage()?->getMessageId();
                }
                $emailLog->markSent($log, $messageId);
                $envoyes++;
            } catch (Throwable $exception) {
                $emailLog->markFailed($log, $exception);
                throw $exception;
            }
        }

        Log::info("Rapport quotidien CRM envoye a {$envoyes} destinataire(s).");

        return $envoyes;
    }

    protected function lockKey(): string
    {
        $scope = $this->userId !== null
            ? 'user-'.$this->userId
            : 'roles-'.sha1(implode(',', $this->roles ?: ['default']));

        return 'crm-daily-report:'.Carbon::now()->format('Y-m-d').':'.$this->reportKey.':'.$scope;
    }

    protected function configureMailerFromActiveEmailConfiguration(): void
    {
        $config = EmailConfiguration::query()
            ->where('is_global', true)
            ->where('active', true)
            ->first();

        if (! $config) {
            return;
        }

        $smtpConfig = [
            'transport' => 'smtp',
            // Ne pas conserver un scheme smtps hérité lorsque la configuration active utilise TLS.
            'scheme' => $config->smtp_encryption === 'ssl' ? 'smtps' : null,
            'host' => $config->smtp_host,
            'port' => $config->smtp_port,
            'username' => $config->email,
            'password' => $config->password,
            'encryption' => $config->smtp_encryption === 'none' ? null : $config->smtp_encryption,
            'timeout' => null,
            'local_domain' => config('mail.mailers.smtp.local_domain'),
        ];

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), $smtpConfig),
            'mail.from.address' => $config->email,
            'mail.from.name' => $config->from_name ?: config('mail.from.name'),
        ]);
    }
}
