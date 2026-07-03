<?php

namespace App\Providers;

use App\Events\Mail2EnvoyeEvent;
use App\Listeners\NotifierTeamLeaderMail2Listener;
use App\Listeners\EnvSettingUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\RedirectAllMailListener;
use Illuminate\Database\Events\Updated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Mail2EnvoyeEvent::class => [
            NotifierTeamLeaderMail2Listener::class,
        ],
        \Illuminate\Mail\Events\MessageSending::class => [
            RedirectAllMailListener::class,
        ],
        Updated::class => [
            EnvSettingUpdated::class,
        ],
    ];
}
