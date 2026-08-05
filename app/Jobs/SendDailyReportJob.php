<?php

namespace App\Jobs;

use App\Mail\DailyReportMail;
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
        $envoyes = 0;

        $destinataires = User::query()
            ->where('actif', true)
            ->whereNotNull('email')
            ->get();

        foreach ($destinataires as $user) {
            $rapport = match ($user->role_cache) {
                User::ROLE_TELEPROSPECTEUR => $service->pourTeleprospecteur($user),
                User::ROLE_COMMERCIAL => $service->pourCommercial($user),
                default => $service->pourTeamLeader($user),
            };

            Mail::to($user->email)->send(new DailyReportMail($rapport));
            $envoyes++;
        }

        Log::info("Rapport quotidien CRM envoye a {$envoyes} destinataire(s).\n");

        return $envoyes;
    }
}
