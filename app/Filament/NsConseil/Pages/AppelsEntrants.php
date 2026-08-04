<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Pages\PhoningWorkflow;
use App\Services\Crm\CrmProfileService;
use Illuminate\Support\Facades\Auth;

class AppelsEntrants extends PhoningWorkflow
{
    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Appels entrants';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'appels-entrants';

    protected static ?string $title = 'Appels entrants';

    protected static string $view = 'filament.ns-conseil.pages.appels-entrants';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $user = Auth::user();

        $this->isSupervisorMode = app(CrmProfileService::class)
            ->userHasCapability($user, 'supervisor');

        $this->supervisedUserId = $user?->id;
    }
}
