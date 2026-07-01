<?php

namespace App\Providers;

use App\Models\CrmSetting;
use App\Services\Aopia\AopiaIcsService;
use App\Services\Crm\CrmSettingsService;
use App\Services\RingoverService;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RingoverService::class);
        $this->app->singleton(AopiaIcsService::class);
        $this->app->singleton(CrmSettingsService::class);

        // Telescope reste un paquet dev (composer require-dev) : on ne
        // l'enregistre qu'en local pour ne jamais casser
        // `composer install --no-dev` en production/CI.
        if ($this->app->environment('local')) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('siret', function ($attribute, $value) {
            return preg_match('/^\d{14}$/', $value);
        }, 'Le SIRET doit contenir exactement 14 chiffres.');

        // Validation description P2 >= 30 chars
        Validator::extend('description_p2', function ($attribute, $value) {
            return strlen($value) >= 30;
        }, 'La description doit contenir au minimum 30 caractères.');

        CrmSetting::saved(fn() => app(CrmSettingsService::class)->forget());
        CrmSetting::deleted(fn() => app(CrmSettingsService::class)->forget());

        if ($redirectTo = config('mail.redirect_all_to')) {
            Event::listen(MessageSending::class, function (MessageSending $event) use ($redirectTo) {
                $original = collect($event->message->getTo())
                    ->keys()
                    ->implode(', ');

                // Redirige le destinataire principal
                $event->message->to($redirectTo);

                // Vide Cc et Bcc en manipulant les headers Mime directement
                // (Email::cc()/bcc() sont variadiques : Address|string ...$addresses,
                // donc passer un tableau littéral [] lève une TypeError)
                $event->message->getHeaders()->remove('Cc');
                $event->message->getHeaders()->remove('Bcc');

                // Garde une trace du destinataire d'origine dans le sujet (pratique pour debug)
                $subject = $event->message->getSubject();
                $event->message->subject("[Test → {$original}] {$subject}");
            });
        }
    }
}
