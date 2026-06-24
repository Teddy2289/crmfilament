<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('aircall:sync --pages=2 --per-page=50')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// CDC WF5 / WF6 : rapport hebdomadaire (lundi 07h30).
Schedule::command('crm:weekly-report')
    ->weeklyOn(1, '07:30')
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
