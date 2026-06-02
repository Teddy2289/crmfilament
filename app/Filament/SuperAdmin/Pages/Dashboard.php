<?php
namespace App\Filament\SuperAdmin\Pages;

use App\Models\User;
use App\Models\ImportLog;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Dashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Tableau de bord';
    protected static ?int    $navigationSort  = 0;
    protected static string  $view            = 'filament.super-admin.pages.dashboard';

    public function getStats(): array
    {
        $tables = DB::select('SHOW TABLES');

        return [
            'users'       => User::count(),
            'actifs'      => User::where('actif', true)->count(),
            'tables'      => count($tables),
            'roles'       => \Spatie\Permission\Models\Role::count(),
            'permissions' => \Spatie\Permission\Models\Permission::count(),
            'imports'     => class_exists(ImportLog::class) ? ImportLog::count() : 0,
            'db_size'     => $this->getDatabaseSize(),
        ];
    }

    public function getRolesDistribution(): \Illuminate\Support\Collection
    {
        return \Spatie\Permission\Models\Role::withCount('users')
            ->orderByDesc('users_count')->get();
    }

    public function getRecentUsers(): \Illuminate\Support\Collection
    {
        return User::latest()->take(5)->get();
    }

    protected function getDatabaseSize(): string
    {
        try {
            $size = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
            ")[0]->size_mb ?? 0;
            return $size . ' MB';
        } catch (\Exception $e) {
            return '—';
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrateur']) ?? false;
    }
}
