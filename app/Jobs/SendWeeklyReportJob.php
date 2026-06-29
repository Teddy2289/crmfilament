<?php

namespace App\Jobs;

use App\Mail\WeeklyReportMail;
use App\Models\User;
use App\Services\Crm\WeeklyReportService;
use Illuminate\Bus\Queueable;
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

    public function handle(WeeklyReportService $service): int
    {
        $envoyes = 0;

        foreach ($service->destinatairesPourRoles($this->roles) as $user) {
            $rapport = match ($user->role_cache) {
                User::ROLE_TELEPROSPECTEUR => $service->pourTeleprospecteur($user),
                User::ROLE_COMMERCIAL => $service->pourCommercial($user),
                default => $service->pourTeamLeader($user),
            };

            Mail::to($user->email)->send(new WeeklyReportMail($rapport));
            $envoyes++;
        }

        Log::info("Rapport hebdomadaire CRM envoye a {$envoyes} destinataire(s).");

        return $envoyes;
    }
}
