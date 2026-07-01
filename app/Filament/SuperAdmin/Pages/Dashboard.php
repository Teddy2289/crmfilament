<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\ImportLog;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.super-admin.pages.dashboard';

    public function getStats(): array
    {
        $tables = DB::select('SHOW TABLES');

        return [
            'users' => User::count(),
            'actifs' => User::where('actif', true)->count(),
            'tables' => count($tables),
            'roles' => Role::count(),
            'permissions' => Permission::count(),
            'imports' => class_exists(ImportLog::class) ? ImportLog::count() : 0,
            'db_size' => $this->getDatabaseSize(),
            'avances_en_attente' => class_exists(\App\Models\BonDeCommande::class) ? \App\Models\BonDeCommande::avecAcompteEnAttente()->count() : 0,
        ];
    }

    public function getRolesDistribution(): Collection
    {
        return Role::withCount('users')
            ->orderByDesc('users_count')->get();
    }

    public function getRecentUsers(): Collection
    {
        return User::latest()->take(5)->get();
    }

    protected function getDatabaseSize(): string
    {
        try {
            $size = DB::select('
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
            ')[0]->size_mb ?? 0;

            return $size.' MB';
        } catch (\Exception $e) {
            return 'n/a';
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrateur']) ?? false;
    }
}
