<?php
require __DIR__ . '/vendor/autoload.php';

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Prospect;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DummyLivewire implements HasTable {
    protected Table $table;
    public function setTable(Table $table) { $this->table = $table; }
    public function getTable(): Table { return $this->table; }
    public function getTableFilterState(string $name): ?array { return null; }
    public function callTableColumnAction(string $name, string $recordKey): mixed {}
    public function deselectAllTableRecords(): void {}
    public function getActiveTableLocale(): ?string { return null; }
    public function getAllSelectableTableRecordKeys(): array { return []; }
    public function getAllTableRecordsCount(): int { return 0; }
    public function getAllSelectableTableRecordsCount(): int { return 0; }
    public function getSelectedTableRecords(bool $shouldFetchSelectedRecords = true) {}
    public function parseTableFilterName(string $name): string { return $name; }
    public function getTableGrouping() { return null; }
    public function getMountedTableAction() { return null; }
    public function getMountedTableActionForm() { return null; }
    public function getMountedTableActionRecord() { return null; }
    public function getMountedTableActionRecordKey() { return null; }
    public function getMountedTableBulkAction() { return null; }
    public function getMountedTableBulkActionForm() { return null; }
    public function getTableRecords() {}
    public function getTableRecordsPerPage() { return null; }
    public function getTablePage() { return 1; }
    public function getTableSortColumn() { return null; }
    public function getTableSortDirection() { return null; }
    public function getAllTableSummaryQuery() {}
    public function getPageTableSummaryQuery() {}
    public function isTableColumnToggledHidden(string $name): bool { return false; }
    public function getTableColumnToggleForm() {}
    public function getTableRecord(?string $key) {}
    public function getTableRecordKey(Model $record): string { return ''; }
    public function mountedTableAction(string $name, ?string $record = null, array $arguments = []) {}
    public function toggleTableReordering(): void {}
    public function isTableReordering(): bool { return false; }
    public function hasTableSearch(): bool { return false; }
    public function resetTableSearch(): void {}
    public function resetTableColumnSearch(string $column): void {}
    public function getTableSearchIndicator() {}
    public function getTableColumnSearchIndicators(): array { return []; }
    public function getFilteredTableQuery() {}
    public function getFilteredSortedTableQuery() {}
    public function getTableQueryForExport() {}
    public function makeFilamentTranslatableContentDriver() { return null; }
    public function callMountedTableAction(array $arguments = []): mixed {}
    public function mountTableAction(string $name, ?string $record = null, array $arguments = []): mixed {}
    public function replaceMountedTableAction(string $name, ?string $record = null, array $arguments = []): void {}
    public function mountTableBulkAction(string $name, ?array $selectedRecords = null): mixed {}
    public function replaceMountedTableBulkAction(string $name, ?array $selectedRecords = null): void {}
}

$livewire = new DummyLivewire();
$table = Table::make($livewire);
$livewire->setTable($table);

$filter = SelectFilter::make('commercial_id')->relationship('commercial','nom');
$filter->table($table);

var_dump($filter->getRelationshipName());
try {
    $relationship = $filter->getRelationship();
    var_dump($relationship);
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
