<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\CrmReportEmailLogResource\Pages;
use App\Models\CrmReportEmailLog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CrmReportEmailLogResource extends Resource
{
    protected static ?string $model = CrmReportEmailLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Rapports envoyés';

    protected static ?string $modelLabel = 'journal de rapport';

    protected static ?string $pluralModelLabel = 'journaux des rapports';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdmin() || $user->isSuperviseur());
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Exécution')
                ->schema([
                    TextEntry::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('execution_uuid')->label('Exécution'),
                    TextEntry::make('report_key')->label('Rapport'),
                    TextEntry::make('report_type')->label('Type'),
                    TextEntry::make('scope')->label('Périmètre')->placeholder('—'),
                    TextEntry::make('status')->label('Statut')->badge(),
                ])->columns(2),
            Section::make('Destinataire')
                ->schema([
                    TextEntry::make('recipient_email')->label('E-mail')->copyable(),
                    TextEntry::make('user.nom_complet')->label('Utilisateur')->placeholder('—'),
                    TextEntry::make('subject')->label('Sujet')->placeholder('—'),
                    TextEntry::make('message_id')->label('Message-ID')->placeholder('—')->copyable(),
                ])->columns(2),
            Section::make('Diagnostic')
                ->schema([
                    TextEntry::make('error_class')->label('Classe erreur')->placeholder('—'),
                    TextEntry::make('error_message')->label('Message')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('metadata')->label('Métadonnées')->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Exécuté le')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_key')
                    ->label('Rapport')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'daily-test' ? 'Test quotidien' : 'Quotidien'),
                Tables\Columns\TextColumn::make('recipient_email')
                    ->label('Destinataire')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.nom_complet')
                    ->label('Utilisateur')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CrmReportEmailLog::STATUS_SENT => 'Envoyé',
                        CrmReportEmailLog::STATUS_FAILED => 'Échec',
                        CrmReportEmailLog::STATUS_SKIPPED => 'Ignoré',
                        default => 'En attente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CrmReportEmailLog::STATUS_SENT => 'success',
                        CrmReportEmailLog::STATUS_FAILED => 'danger',
                        CrmReportEmailLog::STATUS_SKIPPED => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('message_id')
                    ->label('Message-ID')
                    ->limit(32)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Erreur')
                    ->limit(80)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        CrmReportEmailLog::STATUS_SENT => 'Envoyé',
                        CrmReportEmailLog::STATUS_FAILED => 'Échec',
                        CrmReportEmailLog::STATUS_PENDING => 'En attente',
                        CrmReportEmailLog::STATUS_SKIPPED => 'Ignoré',
                    ]),
                SelectFilter::make('report_key')
                    ->label('Rapport')
                    ->options([
                        'daily' => 'Quotidien',
                        'daily-test' => 'Test quotidien',
                    ]),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Depuis'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Jusqu’à'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmReportEmailLogs::route('/'),
            'view' => Pages\ViewCrmReportEmailLog::route('/{record}'),
        ];
    }
}

