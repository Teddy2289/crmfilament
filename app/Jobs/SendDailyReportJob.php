<?php

namespace App\Jobs;

use App\Mail\DailyReportMail;
use App\Models\EmailConfiguration;
use App\Models\User;
use App\Services\Crm\DailyReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(DailyReportService $service): int
    {
        $this->configureMailerFromActiveEmailConfiguration();

        $envoyes = 0;

        $destinataires = $service->destinatairesPourRoles([
            User::ROLE_TELEPROSPECTEUR,
            User::ROLE_COMMERCIAL,
            User::ROLE_SUPERVISEUR,
            DailyReportService::ROLE_TEAM_LEADER,
        ]);

        foreach ($destinataires as $user) {
            if (! filled($user->email)) {
                Log::warning("Rapport quotidien ignoré pour utilisateur #{$user->id} sans email valide");
                continue;
            }

            $rapport = match ($user->role_cache) {
                User::ROLE_TELEPROSPECTEUR => $service->pourTeleprospecteur($user),
                User::ROLE_COMMERCIAL => $service->pourCommercial($user),
                default => $service->pourTeamLeader($user),
            };

            Mail::to($user->email)->send(new DailyReportMail($rapport));
            $envoyes++;
        }

        Log::info("Rapport quotidien CRM envoye a {$envoyes} destinataire(s).");

        return $envoyes;
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
