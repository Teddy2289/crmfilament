<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\CrmSetting;
use App\Providers\Filament\NsConseilPanelProvider;
use App\Services\Crm\CrmSettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NavigationOrderSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bars-arrow-down';

    protected static ?string $navigationLabel = 'Ordre des menus';

    protected static ?string $title = 'Ordre des menus — NS Conseil';

    protected static ?string $navigationGroup = 'Paramétrage CRM';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.super-admin.pages.navigation-order-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $labels = NsConseilPanelProvider::navigationGroupLabels();

        $order = NsConseilPanelProvider::sanitizeNavigationGroupOrder(
            app(CrmSettingsService::class)->get(
                NsConseilPanelProvider::NAVIGATION_ORDER_SETTING_KEY,
                NsConseilPanelProvider::defaultNavigationGroupOrder(),
            ) ?? NsConseilPanelProvider::defaultNavigationGroupOrder(),
        );

        $this->form->fill([
            'groups' => collect($order)
                ->map(fn (string $name) => [
                    'name' => $name,
                    'label' => $labels[$name] ?? $name,
                ])
                ->toArray(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Repeater::make('groups')
                    ->label('')
                    ->schema([
                        Hidden::make('name'),
                        TextInput::make('label')
                            ->label('')
                            ->disabled(),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable()
                    ->reorderableWithButtons()
                    ->collapsible(false)
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer l\'ordre')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $order = NsConseilPanelProvider::sanitizeNavigationGroupOrder(
            collect($this->form->getState()['groups'] ?? [])
                ->pluck('name')
                ->values()
                ->all(),
        );

        CrmSetting::updateOrCreate(
            ['groupe' => 'navigation', 'cle' => 'ns_conseil_group_order'],
            [
                'default_crm' => 'ns-conseil',
                'valeur' => json_encode($order, JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'label' => 'Ordre des groupes de menu (NS Conseil)',
            ],
        );

        app(CrmSettingsService::class)->forget();

        Notification::make()
            ->title('Ordre des menus mis à jour')
            ->body('Le nouveau classement s\'appliquera au prochain chargement du menu NS Conseil.')
            ->success()
            ->send();
    }
}
