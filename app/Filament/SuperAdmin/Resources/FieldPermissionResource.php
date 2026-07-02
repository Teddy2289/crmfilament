<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\FieldPermissionResource\Pages;
use App\Models\User;
use App\Models\FieldPermission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FieldPermissionResource extends Resource
{
    protected static ?string $model = FieldPermission::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Permissions champs';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Rôle')
                            ->options(User::ROLES)
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('resource')
                            ->label('Ressource')
                            ->options([
                                'prospects' => 'Prospects',
                                'clients' => 'Clients',
                                'partenaires' => 'Partenaires',
                            ])
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('field_name')
                            ->label('Nom du champ')
                            ->required()
                            ->helperText('Ex: nom, email, telephone, statut, etc.'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Visibilité')
                    ->schema([
                        Forms\Components\Toggle::make('visible_list')
                            ->label('Visible dans la liste')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('visible_view')
                            ->label('Visible dans les détails')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('visible_edit')
                            ->label('Visible en édition')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('read_only')
                            ->label('Lecture seule')
                            ->default(false)
                            ->helperText('Le champ sera visible mais non modifiable')
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle')
                    ->formatStateUsing(fn ($state) => User::ROLES[$state] ?? $state)
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('resource')
                    ->label('Ressource')
                    ->badge()
                    ->color('success')
                    ->searchable(),

                Tables\Columns\TextColumn::make('field_name')
                    ->label('Champ')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('visible_list')
                    ->label('Liste')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                Tables\Columns\IconColumn::make('visible_view')
                    ->label('Détails')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                Tables\Columns\IconColumn::make('visible_edit')
                    ->label('Édition')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                Tables\Columns\IconColumn::make('read_only')
                    ->label('RO')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->tooltip('Lecture seule'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rôle')
                    ->options(User::ROLES),

                Tables\Filters\SelectFilter::make('resource')
                    ->label('Ressource')
                    ->options([
                        'prospects' => 'Prospects',
                        'clients' => 'Clients',
                        'partenaires' => 'Partenaires',
                    ]),
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
            ->defaultSort('resource');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFieldPermissions::route('/'),
            'create' => Pages\CreateFieldPermission::route('/create'),
            'edit' => Pages\EditFieldPermission::route('/{record}/edit'),
        ];
    }
}
