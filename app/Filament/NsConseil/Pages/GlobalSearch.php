<?php

namespace App\Filament\NsConseil\Pages;

use App\Services\Crm\SearchAndRelationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GlobalSearch extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Recherche globale';

    protected static ?string $navigationGroup = 'Recherche';

    protected static string $view = 'filament.ns-conseil.pages.global-search';

    public ?string $searchQuery = null;

    public array $data = ['searchQuery' => null, 'departmentFilter' => null];

    public array $results = [];

    protected ?SearchAndRelationService $searchService = null;

    public function mount(): void
    {
        $this->form->fill();
        $this->searchService = new SearchAndRelationService();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('searchQuery')
                    ->label('Rechercher (téléphone, email, nom, ref_client...)')
                    ->placeholder('Ex: 0612345678, jean.dupont@email.com, CLI-2024-001')
                    ->live()
                    ->debounce(500)
                    ->afterStateUpdated(fn ($state) => $this->search($state)),
                Forms\Components\Select::make('departmentFilter')
                    ->label('Filtrer par département')
                    ->placeholder('Tous les départements')
                    ->options($this->departmentOptions())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->applyDepartmentFilter()),
            ])
            ->statePath('data');
    }

    public function search(?string $query = null): void
    {
        $this->searchService ??= app(SearchAndRelationService::class);
        $query = $query ?? ($this->data['searchQuery'] ?? $this->searchQuery ?? null);

        if (empty($query) || strlen($query) < 3) {
            $this->results = [];
            return;
        }

        $this->results = $this->searchService->searchGlobal($query);
        $this->applyDepartmentFilter();
    }

    private function departmentOptions(): array
    {
        $options = [];
        for ($i = 1; $i <= 95; $i++) {
            $code = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $options[$code] = $code;
        }
        $options['2A'] = '2A — Corse-du-Sud';
        $options['2B'] = '2B — Haute-Corse';
        return $options;
    }

    public function applyDepartmentFilter(): void
    {
        $department = strtoupper(trim((string) ($this->data['departmentFilter'] ?? '')));
        if ($department === '' || empty($this->results)) return;

        $departmentNames = [
            '01' => 'AIN', '02' => 'AISNE', '16' => 'CHARENTE', '17' => 'CHARENTE-MARITIME',
            '19' => 'CORREZE', '24' => 'DORDOGNE', '33' => 'GIRONDE', '40' => 'LANDES',
            '47' => 'LOT-ET-GARONNE', '64' => 'PYRENEES-ATLANTIQUES', '79' => 'DEUX-SEVRES',
            '86' => 'VIENNE', '87' => 'HAUTE-VIENNE',
        ];
        $targetName = $departmentNames[$department] ?? $department;

        foreach ($this->results as $type => $items) {
            $this->results[$type] = array_values(array_filter($items, static function (array $item) use ($department, $targetName): bool {
                $value = strtoupper(trim((string) ($item['departement'] ?? '')));
                $postal = preg_replace('/[^0-9]/', '', (string) ($item['code_postal'] ?? $item['postal_code'] ?? ''));
                $postalDepartment = substr($postal, 0, 2);
                return $value === $department || $value === $targetName || $postalDepartment === $department;
            }));
        }
        $this->results = array_filter($this->results, static fn (array $items): bool => !empty($items));
    }

    private function exportRows(): array
    {
        $rows = [];
        foreach ($this->results as $type => $items) {
            foreach ($items as $item) {
                $rows[] = [
                    'Type' => ucfirst((string) $type),
                    'Nom' => $item['nom'] ?? '',
                    'Référence' => $item['ref_client'] ?? ($item['siret'] ?? ''),
                    'Téléphone' => $item['telephone'] ?? '',
                    'E-mail' => $item['email'] ?? '',
                    'Ville' => $item['ville'] ?? '',
                    'Département' => $item['departement'] ?? '',
                    'Statut' => $item['statut'] ?? ($item['etat'] ?? ''),
                    'CSE' => $item['cse_contact'] ?? ($item['presence_cse'] ?? ''),
                    'Lien' => $item['url'] ?? '',
                ];
            }
        }
        return $rows;
    }

    public function exportCsv()
    {
        $rows = $this->exportRows();
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\\xEF\\xBB\\xBF");
            $headers = array_keys($rows[0] ?? ['Type' => '', 'Nom' => '']);
            fputcsv($handle, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($handle, array_map(static fn ($value) => is_scalar($value) ? $value : '', $row), ';');
            }
            fclose($handle);
        }, 'recherche-crm-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel()
    {
        $rows = $this->exportRows();
        return response()->streamDownload(function () use ($rows) {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><table border="1"><tr>';
            foreach (array_keys($rows[0] ?? ['Type' => '', 'Nom' => '']) as $header) {
                echo '<th>'.e($header).'</th>';
            }
            echo '</tr>';
            foreach ($rows as $row) {
                echo '<tr>'; foreach ($row as $value) echo '<td>'.e((string) $value).'</td>'; echo '</tr>';
            }
            echo '</table></body></html>';
        }, 'recherche-crm-'.now()->format('Ymd-His').'.xls', ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function linkEntities(string $fromType, int $fromId, string $toType, int $toId): void
    {
        // Logique de liaison entre entités
        // À implémenter selon les besoins métier
        Notification::make()
            ->title('Liaison créée')
            ->success()
            ->send();
    }
}
