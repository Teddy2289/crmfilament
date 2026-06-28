<?php

namespace App\Filament\Widgets;

use App\Services\Crm\ViewManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ViewSelectorWidget extends Widget
{
    protected static string $view = 'filament.widgets.view-selector';

    protected static bool $isLazy = false;

    public ?string $resource = null;

    public ?string $currentView = null;

    protected ViewManagementService $viewService;

    public function mount(?string $resource = null): void
    {
        $this->resource = $resource;
        $this->viewService = new ViewManagementService();
        $this->viewService->setResource($resource);
        $this->currentView = $this->viewService->getCurrentView();
    }

    public function getViewSelectorOptions(): array
    {
        return $this->viewService->getAvailableViews();
    }

    public function selectView(string $view): void
    {
        $this->viewService->setCurrentView($view);
        $this->currentView = $view;

        $this->redirect(request()->header('Referer'));
    }

    public function saveView(array $data): void
    {
        try {
            $this->viewService->saveView($data);

            Notification::make()
                ->title('Vue sauvegardée')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de la sauvegarde')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteView(int $viewId): void
    {
        if ($this->viewService->deleteView($viewId)) {
            Notification::make()
                ->title('Vue supprimée')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Erreur lors de la suppression')
                ->danger()
                ->send();
        }
    }
}
