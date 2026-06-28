<?php

namespace App\Filament\Widgets;

use App\Models\UserView;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ViewSelectorWidget extends Widget
{
    protected static string $view = 'filament.widgets.view-selector';

    protected static bool $isLazy = false;

    public ?string $resource = null;

    public ?string $currentView = null;

    public function mount(?string $resource = null): void
    {
        $this->resource = $resource;
        $this->currentView = session()->get("view_{$this->resource}", 'list');
    }

    public function getViewSelectorOptions(): array
    {
        $user = Auth::user();
        if (! $user) {
            return ['list' => 'Liste', 'kanban' => 'Kanban'];
        }

        $customViews = UserView::where('user_id', $user->id)
            ->where('resource', $this->resource)
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        return array_merge(
            ['list' => 'Liste', 'kanban' => 'Kanban'],
            $customViews
        );
    }

    public function selectView(string $view): void
    {
        $this->currentView = $view;
        session()->put("view_{$this->resource}", $view);

        // Rafraîchir la page pour appliquer la vue
        $this->redirect(request()->header('Referer'));
    }

    public function saveView(array $data): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        UserView::updateOrCreate(
            [
                'user_id' => $user->id,
                'resource' => $this->resource,
                'name' => $data['name'],
            ],
            [
                'type' => $data['type'],
                'config' => $data['config'] ?? [],
                'is_default' => $data['is_default'] ?? false,
            ]
        );

        Notification::make()
            ->title('Vue sauvegardée')
            ->success()
            ->send();
    }

    public function deleteView(int $viewId): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        UserView::where('id', $viewId)
            ->where('user_id', $user->id)
            ->delete();

        Notification::make()
            ->title('Vue supprimée')
            ->success()
            ->send();
    }
}
