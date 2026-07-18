<?php

use App\Jobs\SendWeeklyReportJob;
use App\Models\User;
use App\Services\Crm\WeeklyReportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ringover:sync --pages=2 --per-page=50')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// CDC WF5 / WF6 : rapport hebdomadaire téléprospecteurs (lundi 07h30).
Schedule::job(new SendWeeklyReportJob([User::ROLE_TELEPROSPECTEUR]))
    ->weeklyOn(1, '07:30')
    ->withoutOverlapping();

// CDC WF5 / WF6 : rapport hebdomadaire commerciaux (lundi 08h00).
Schedule::job(new SendWeeklyReportJob([
    User::ROLE_COMMERCIAL,
    User::ROLE_SUPERVISEUR,
    WeeklyReportService::ROLE_TEAM_LEADER,
]))
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping();

// Fiches Word : envoi automatique fiches jaunes J+7 (quotidien 08h00).
Schedule::job(new \App\Jobs\SendFicheJauneJ7Job())
    ->dailyAt('08:00')
    ->withoutOverlapping();

// WF7 — Rappel RP : créer tâches de rappel pour téléprospecteurs (toutes les 30 min).
Schedule::job(new \App\Jobs\SendRappelRpJob())
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Rappel STD-NR J+2 : créer tâches de rappel pour prospects STD-NR (quotidien 09h00).
Schedule::job(new \App\Jobs\SendRappelStdNrJob())
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Rappel RDV : créer une tâche de rappel pour le téléprospecteur/commercial à l'heure
// convenue avec le prospect (toutes les 30 min).
Schedule::job(new \App\Jobs\SendRappelRdvJob())
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Invitation agenda RDV : envoie l'invitation au commercial (fiche + enregistrement audio)
// une fois les pièces jointes disponibles, ou au plus tard après 2h (toutes les 10 min).
Schedule::job(new \App\Jobs\SendInvitationAgendaJob())
    ->everyTenMinutes()
    ->withoutOverlapping();
