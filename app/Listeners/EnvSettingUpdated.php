<?php

namespace App\Listeners;

use App\Models\EnvSetting;
use App\Services\EnvSettingsService;
use Illuminate\Database\Events\Updated;
use Illuminate\Support\Facades\App;

class EnvSettingUpdated
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Updated $event): void
    {
        if ($event->model instanceof EnvSetting && $event->model->is_editable) {
            App::make(EnvSettingsService::class)->syncToEnv();
            App::make(EnvSettingsService::class)->clearConfigCache();
        }
    }
}
