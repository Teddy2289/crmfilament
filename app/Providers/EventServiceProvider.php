<?php

namespace App\Providers;

use App\Events\Mail2EnvoyeEvent;
use App\Listeners\NotifierTeamLeaderMail2Listener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Mail2EnvoyeEvent::class => [
            NotifierTeamLeaderMail2Listener::class,
        ],
    ];
}
