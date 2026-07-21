<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages\CreateGroupeTelepro;
use App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages\EditGroupeTelepro;
use App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages\ListGroupeTelepros;
use App\Models\GroupeTelepro;
use App\Models\User;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GroupeTeleproResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = GroupeTelepro::class;

    protected static string $permissionPrefix = 'groupe_telepros';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Groupes de téléprospecteurs';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Groupe de téléprospecteurs';

    protected static ?string $pluralModelLabel = 'Groupes de téléprospecteurs';

    protected static function membresOptions(): array
    {
        return User::teleprospecteurs()
            ->where('actif', true)
            ->orderBy('nom')
            ->get()
            ->mapWithKeys(fn (User $u) => [$u->id => trim("{$u->prenom} {$u->nom}")])
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::applyFormFieldPermissions([
            Forms\Components\Section::make('Groupe')
                ->icon('heroicon-o-user-group')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nom')
                        ->label('Nom du groupe')
                        ->placeholder('ex : Groupe 44-45-75')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Toggle::make('actif')
                        ->label('Actif')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Select::make('membres')
                        ->label('Téléprospecteurs membres')
                        ->multiple()
                        ->options(fn () => static::membresOptions())
                        ->searchable()
                        ->columnSpanFull()
                        ->helperText('Un téléprospecteur peut appartenir à plusieurs groupes en même temps.')
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?GroupeTelepro $record) {
                            if ($record) {
                                $component->state($record->membres()->pluck('users.id')->toArray());
                            }
                        }),
                ]),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::applyShowFieldPermissions([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Groupe')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('membres_count')
                    ->label('Téléprospecteurs')
                    ->counts('membres')
                    ->suffix(' téléprospecteur(s)')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('campagnes_count')
                    ->label('Campagnes assignées')
                    ->counts('campagnes')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\ToggleColumn::make('actif')
                    ->label('Actif'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->filters([
                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nom');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroupeTelepros::route('/'),
            'create' => CreateGroupeTelepro::route('/create'),
            'edit' => EditGroupeTelepro::route('/{record}/edit'),
        ];
    }
}
