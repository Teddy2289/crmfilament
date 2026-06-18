<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\ImportLogResource\Pages\ListImportLogs;
use App\Models\ImportLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImportLogResource extends Resource
{
    protected static ?string $model = ImportLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Logs d\'import';

    protected static ?string $navigationGroup = 'Logs & Audit';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('filename')->label('Fichier')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('model_type')->label('Modèle')->badge()->color('info'),
                Tables\Columns\TextColumn::make('rows_imported')->label('Importées')->badge()->color('success'),
                Tables\Columns\TextColumn::make('rows_skipped')->label('Ignorées')->badge()->color('warning'),
                Tables\Columns\TextColumn::make('rows_failed')->label('Échouées')
                    ->badge()->color(fn ($s) => (int) $s > 0 ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Modèle')
                    ->options(fn () => ImportLog::distinct()->pluck('model_type', 'model_type')->toArray())
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('voir_erreurs')
                    ->label('Erreurs')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn ($r) => ! empty($r->errors))
                    ->modalContent(fn ($r) => view('filament.super-admin.import-errors', ['errors' => $r->errors]))
                    ->modalHeading('Erreurs d\'import')
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportLogs::route('/'),
        ];
    }
}
