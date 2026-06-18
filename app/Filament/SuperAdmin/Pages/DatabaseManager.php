<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\FieldVisibility;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseManager extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Gestionnaire BDD';

    protected static ?string $navigationGroup = 'Base de données';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.super-admin.pages.database-manager';

    // ── État de la page ──────────────────────────────────────────
    public ?string $selectedTable = null;

    public array $tableData = [];

    public array $tableColumns = [];

    public int $currentPage = 1;

    public int $perPage = 25;

    public int $totalRows = 0;

    public ?string $searchQuery = null;

    public string $activeTab = 'data'; // data | structure | sql

    public string $sqlQuery = '';

    public ?string $sqlResult = null;

    public bool $sqlError = false;

    // ── Tables disponibles ───────────────────────────────────────
    public function getTables(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($t) => array_values((array) $t)[0])
            ->sort()
            ->values()
            ->toArray();
    }

    public function selectTable(string $table): void
    {
        $this->selectedTable = $table;
        $this->currentPage = 1;
        $this->searchQuery = null;
        $this->activeTab = 'structure';
        $this->loadTableData();
        $this->loadTableStructure();
    }

    // ── Chargement des données ───────────────────────────────────
    public function loadTableData(): void
    {
        if (! $this->selectedTable) {
            return;
        }

        $query = DB::table($this->selectedTable);

        if ($this->searchQuery) {
            $columns = Schema::getColumnListing($this->selectedTable);
            $query->where(function ($q) use ($columns) {
                foreach ($columns as $col) {
                    $q->orWhere($col, 'LIKE', '%'.$this->searchQuery.'%');
                }
            });
        }

        $this->totalRows = $query->count();
        $offset = ($this->currentPage - 1) * $this->perPage;

        $this->tableData = $query
            ->offset($offset)
            ->limit($this->perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }

    public function loadTableStructure(): void
    {
        if (! $this->selectedTable) {
            return;
        }

        $this->tableColumns = DB::select("DESCRIBE `{$this->selectedTable}`");
    }

    // ── Pagination ───────────────────────────────────────────────
    public function nextPage(): void
    {
        $maxPage = ceil($this->totalRows / $this->perPage);
        if ($this->currentPage < $maxPage) {
            $this->currentPage++;
            $this->loadTableData();
        }
    }

    public function prevPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadTableData();
        }
    }

    public function updatedSearchQuery(): void
    {
        $this->currentPage = 1;
        $this->loadTableData();
    }

    // ── Ajouter une colonne ──────────────────────────────────────
    public function getAddColumnAction(): Action
    {
        return Action::make('add_column')
            ->label('Ajouter une colonne')
            ->icon('heroicon-o-plus')
            ->color('success')
            ->visible(fn () => $this->selectedTable !== null)
            ->form([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('column_name')
                        ->label('Nom de la colonne')
                        ->required()
                        ->regex('/^[a-z_][a-z0-9_]*$/')
                        ->helperText('snake_case uniquement'),

                    Forms\Components\Select::make('column_type')
                        ->label('Type')
                        ->options([
                            'VARCHAR(255)' => 'VARCHAR(255) — Texte court',
                            'TEXT' => 'TEXT — Texte long',
                            'LONGTEXT' => 'LONGTEXT — Texte très long',
                            'INT' => 'INT — Entier',
                            'BIGINT' => 'BIGINT — Grand entier',
                            'DECIMAL(10,2)' => 'DECIMAL(10,2) — Décimal',
                            'FLOAT' => 'FLOAT — Flottant',
                            'BOOLEAN' => 'BOOLEAN — Booléen',
                            'DATE' => 'DATE — Date',
                            'DATETIME' => 'DATETIME — Date + heure',
                            'TIMESTAMP' => 'TIMESTAMP — Horodatage',
                            'JSON' => 'JSON — Données JSON',
                            'ENUM' => 'ENUM — Valeurs fixes (voir ci-dessous)',
                        ])
                        ->required()->native(false),

                    Forms\Components\TextInput::make('enum_values')
                        ->label('Valeurs ENUM (séparées par virgule)')
                        ->helperText('Ex: valeur1,valeur2,valeur3 — uniquement si type ENUM')
                        ->nullable(),

                    Forms\Components\Toggle::make('nullable')
                        ->label('Nullable (valeur nulle autorisée)')
                        ->default(true)->inline(false),

                    Forms\Components\TextInput::make('default_value')
                        ->label('Valeur par défaut')
                        ->nullable(),

                    Forms\Components\TextInput::make('after_column')
                        ->label('Après la colonne (optionnel)')
                        ->nullable()
                        ->helperText('Laisser vide pour ajouter à la fin'),
                ]),
            ])
            ->action(function (array $data) {
                try {
                    $table = $this->selectedTable;
                    $col = $data['column_name'];
                    $type = $data['column_type'];
                    $null = $data['nullable'] ? 'NULL' : 'NOT NULL';
                    $after = $data['after_column'] ? "AFTER `{$data['after_column']}`" : '';

                    if ($type === 'ENUM' && ! empty($data['enum_values'])) {
                        $vals = collect(explode(',', $data['enum_values']))
                            ->map(fn ($v) => "'".trim($v)."'")
                            ->join(',');
                        $type = "ENUM($vals)";
                    }

                    $default = $data['default_value'] !== null
                        ? "DEFAULT '".addslashes($data['default_value'])."'"
                        : '';

                    DB::statement("ALTER TABLE `$table` ADD COLUMN `$col` $type $null $default $after");

                    $this->loadTableStructure();
                    $this->loadTableData();

                    Notification::make()
                        ->title("Colonne `$col` ajoutée avec succès")
                        ->success()->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Erreur SQL')
                        ->body($e->getMessage())
                        ->danger()->send();
                }
            });
    }

    // ── Supprimer une colonne ────────────────────────────────────
    public function dropColumn(string $column): void
    {
        // Protection colonnes système
        if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
            Notification::make()
                ->title('Colonne protégée')
                ->body("La colonne `$column` ne peut pas être supprimée.")
                ->danger()->send();

            return;
        }

        try {
            DB::statement("ALTER TABLE `{$this->selectedTable}` DROP COLUMN `$column`");
            $this->loadTableStructure();
            $this->loadTableData();
            Notification::make()->title("Colonne `$column` supprimée")->warning()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
        }
    }

    // ── Modifier une colonne ─────────────────────────────────────
    public function getEditColumnAction(): Action
    {
        return Action::make('edit_column')
            ->label('Modifier colonne')
            ->icon('heroicon-o-pencil')
            ->color('warning')
            ->visible(fn () => $this->selectedTable !== null)
            ->form([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('column_name')
                        ->label('Colonne à modifier')
                        ->options(
                            fn () => $this->selectedTable
                                ? collect(Schema::getColumnListing($this->selectedTable))
                                    ->mapWithKeys(fn ($c) => [$c => $c])->toArray()
                                : []
                        )
                        ->required()->native(false),

                    Forms\Components\Select::make('new_type')
                        ->label('Nouveau type')
                        ->options([
                            'VARCHAR(255)' => 'VARCHAR(255)',
                            'VARCHAR(500)' => 'VARCHAR(500)',
                            'TEXT' => 'TEXT',
                            'LONGTEXT' => 'LONGTEXT',
                            'INT' => 'INT',
                            'BIGINT' => 'BIGINT',
                            'DECIMAL(10,2)' => 'DECIMAL(10,2)',
                            'DECIMAL(15,2)' => 'DECIMAL(15,2)',
                            'BOOLEAN' => 'BOOLEAN',
                            'DATE' => 'DATE',
                            'DATETIME' => 'DATETIME',
                            'JSON' => 'JSON',
                        ])
                        ->required()->native(false),

                    Forms\Components\Toggle::make('nullable')
                        ->label('Nullable')->default(true)->inline(false),
                ]),
            ])
            ->action(function (array $data) {
                try {
                    $null = $data['nullable'] ? 'NULL' : 'NOT NULL';
                    DB::statement(
                        "ALTER TABLE `{$this->selectedTable}` MODIFY COLUMN `{$data['column_name']}` {$data['new_type']} $null"
                    );
                    $this->loadTableStructure();
                    Notification::make()->title('Colonne modifiée')->success()->send();
                } catch (\Exception $e) {
                    Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
                }
            });
    }

    // ── Créer une table ──────────────────────────────────────────
    public function getCreateTableAction(): Action
    {
        return Action::make('create_table')
            ->label('Nouvelle table')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->form([
                Forms\Components\TextInput::make('table_name')
                    ->label('Nom de la table')
                    ->required()
                    ->regex('/^[a-z_][a-z0-9_]*$/')
                    ->helperText('snake_case uniquement — ex: mes_donnees'),

                Forms\Components\Toggle::make('with_timestamps')
                    ->label('Inclure created_at / updated_at')
                    ->default(true)->inline(false),

                Forms\Components\Toggle::make('with_soft_deletes')
                    ->label('Inclure deleted_at (soft deletes)')
                    ->default(false)->inline(false),
            ])
            ->action(function (array $data) {
                try {
                    $name = $data['table_name'];
                    $timestamps = $data['with_timestamps']
                        ? '`created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,'
                        : '';
                    $softDelete = $data['with_soft_deletes']
                        ? '`deleted_at` TIMESTAMP NULL,'
                        : '';

                    DB::statement("CREATE TABLE IF NOT EXISTS `$name` (
                        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        $timestamps
                        $softDelete
                        INDEX (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                    Notification::make()->title("Table `$name` créée")->success()->send();
                } catch (\Exception $e) {
                    Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
                }
            });
    }

    // ── Supprimer une table ──────────────────────────────────────
    public function dropTable(string $table): void
    {
        // Tables protégées
        $protected = ['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'migrations'];
        if (in_array($table, $protected)) {
            Notification::make()
                ->title('Table protégée')
                ->body("La table `$table` est protégée et ne peut pas être supprimée.")
                ->danger()->send();

            return;
        }

        try {
            DB::statement("DROP TABLE IF EXISTS `$table`");
            if ($this->selectedTable === $table) {
                $this->selectedTable = null;
                $this->tableData = [];
                $this->tableColumns = [];
            }
            Notification::make()->title("Table `$table` supprimée")->warning()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
        }
    }

    // ── Exécuter SQL libre ───────────────────────────────────────
    public function executeSql(): void
    {
        if (empty(trim($this->sqlQuery))) {
            return;
        }

        // Sécurité : interdire certaines commandes destructives sans confirmation
        $dangerous = ['DROP DATABASE', 'TRUNCATE', 'DROP TABLE users', 'DELETE FROM users'];
        foreach ($dangerous as $cmd) {
            if (Str::contains(strtoupper($this->sqlQuery), strtoupper($cmd))) {
                $this->sqlResult = "⚠️ Commande refusée : `$cmd` est interdite dans l'éditeur SQL libre.";
                $this->sqlError = true;

                return;
            }
        }

        try {
            $isSelect = Str::startsWith(strtoupper(trim($this->sqlQuery)), 'SELECT');

            if ($isSelect) {
                $results = DB::select($this->sqlQuery);
                $this->sqlResult = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $this->sqlError = false;
            } else {
                $affected = DB::statement($this->sqlQuery);
                $this->sqlResult = '✅ Requête exécutée avec succès.';
                $this->sqlError = false;

                if ($this->selectedTable) {
                    $this->loadTableData();
                    $this->loadTableStructure();
                }
            }
        } catch (\Exception $e) {
            $this->sqlResult = '❌ Erreur : '.$e->getMessage();
            $this->sqlError = true;
        }
    }

    // ── Supprimer une ligne ──────────────────────────────────────
    public function deleteRow(int $id): void
    {
        try {
            DB::table($this->selectedTable)->where('id', $id)->delete();
            $this->loadTableData();
            Notification::make()->title("Ligne #$id supprimée")->warning()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
        }
    }

    public function getTableCount(): int
    {
        return count($this->getTables());
    }

    public function getTotalPages(): int
    {
        return $this->totalRows > 0 ? (int) ceil($this->totalRows / $this->perPage) : 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateTableAction(),
            $this->getAddColumnAction(),
            $this->getEditColumnAction(),
            $this->getManageVisibilityAction(),
        ];
    }

    public function getManageVisibilityAction(): Action
    {
        return Action::make('manage_visibility')
            ->label('Visibilité par rôle')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->visible(fn () => $this->selectedTable !== null)
            ->form(function () {
                $roles = Role::pluck('name')->toArray();
                $columns = collect($this->tableColumns)->pluck('Field')->toArray();

                $schema = [];
                foreach ($columns as $col) {
                    $schema[] = Forms\Components\Section::make($col)
                        ->compact()
                        ->schema([
                            Forms\Components\CheckboxList::make("visibility_{$col}")
                                ->label('Visible pour les rôles')
                                ->options(array_combine($roles, $roles))
                                ->default(function () use ($col, $roles) {
                                    // Par défaut : tous les rôles voient le champ
                                    return collect($roles)
                                        ->filter(
                                            fn ($role) => FieldVisibility::isVisible(
                                                $this->selectedTable,
                                                $col,
                                                $role
                                            )
                                        )
                                        ->values()
                                        ->toArray();
                                })
                                ->columns(3)
                                ->gridDirection('row'),
                        ]);
                }

                return $schema;
            })
            ->action(function (array $data) {
                $roles = Role::pluck('name')->toArray();
                $columns = collect($this->tableColumns)->pluck('Field')->toArray();

                foreach ($columns as $col) {
                    $visibleRoles = $data["visibility_{$col}"] ?? [];

                    foreach ($roles as $role) {
                        FieldVisibility::updateOrCreate(
                            [
                                'table_name' => $this->selectedTable,
                                'column_name' => $col,
                                'role_name' => $role,
                            ],
                            ['visible' => in_array($role, $visibleRoles)]
                        );
                    }
                }

                Notification::make()
                    ->title('Visibilité mise à jour')
                    ->success()->send();
            });
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrateur']) ?? false;
    }
}
