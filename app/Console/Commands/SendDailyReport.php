<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyReportJob;
use App\Models\User;
use App\Services\Crm\DailyReportService;
use Illuminate\Console\Command;

class SendDailyReport extends Command
{
    protected $signature = 'crm:daily-report
                            {--user= : Adresse email ou identifiant du destinataire cible}
                            {--roles= : Rôles ciblés, séparés par des virgules (défaut : teleprospecteur,commercial,responsable_plateau,team_leader)}
                            {--sync : Exécuter immédiatement sans passer par la file}';

    protected $description = 'Envoie le rapport quotidien CRM aux utilisateurs ciblés';

    public function handle(): int
    {
        $userOption = $this->option('user');
        $roles = $this->option('roles')
            ? array_map('trim', explode(',', (string) $this->option('roles')))
            : [
                User::ROLE_TELEPROSPECTEUR,
                User::ROLE_COMMERCIAL,
                User::ROLE_SUPERVISEUR,
                DailyReportService::ROLE_TEAM_LEADER,
            ];

        if ($userOption) {
            $user = $this->findUser($userOption);

            if (! $user) {
                $this->error("Utilisateur actif avec email introuvable pour '{$userOption}'.");
                return self::FAILURE;
            }

            $job = new SendDailyReportJob([], $user->id);
        } else {
            $job = new SendDailyReportJob($roles);
        }

        if ($this->option('sync')) {
            $envoyes = $job->handle(app(DailyReportService::class));
            $this->info("Rapport quotidien CRM envoyé à {$envoyes} destinataire(s).");

            return self::SUCCESS;
        }

        dispatch($job);

        if ($userOption) {
            $this->info("Rapport quotidien CRM mis en file pour {$user->email}.");
        } else {
            $this->info('Rapport quotidien CRM mis en file pour les rôles : '.implode(', ', $roles));
        }

        return self::SUCCESS;
    }

    private function findUser(string $value): ?User
    {
        $query = User::query();

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $query->where('email', $value)->first();
        }

        if (ctype_digit($value)) {
            return $query->find((int) $value);
        }

        return $query->where('email', $value)->first();
    }
}
