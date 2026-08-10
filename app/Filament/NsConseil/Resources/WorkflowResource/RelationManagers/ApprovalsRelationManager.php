<?php

namespace App\Filament\NsConseil\Resources\WorkflowResource\RelationManagers;

use App\Models\WorkflowApproval;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static ?string $title = 'Approbations';

    protected static ?string $modelLabel = 'Approbation';

    protected static ?string $pluralModelLabel = 'Approbations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails de l\'approbation')
                    ->schema([
                        Forms\Components\Select::make('statut')
                            ->label('Statut')
                            ->options(WorkflowApproval::STATUTS)
                            ->required()
                            ->default('pending'),
                        Forms\Components\Textarea::make('commentaire')
                            ->label('Commentaire')
                            ->rows(3),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('statut')
            ->columns([
                Tables\Columns\BadgeColumn::make('statut_label')
                    ->label('Statut')
                    ->color(fn (WorkflowApproval $record) => $record->statut_color),
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Type d\'entité'),
                Tables\Columns\TextColumn::make('entity_id')
                    ->label('ID entité'),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approuvé par')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approuvé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(WorkflowApproval::STATUTS),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (WorkflowApproval $record) => $record->statut === 'pending')
                    ->requiresConfirmation()
                    ->action(function (WorkflowApproval $record) {
                        $record->approve(Auth::id());
                        \Filament\Notifications\Notification::make()
                            ->title('Approbation enregistrée')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (WorkflowApproval $record) => $record->statut === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif du rejet')
                            ->required(),
                    ])
                    ->action(function (WorkflowApproval $record, array $data) {
                        $record->reject(Auth::id(), $data['reason']);
                        \Filament\Notifications\Notification::make()
                            ->title('Rejet enregistré')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
