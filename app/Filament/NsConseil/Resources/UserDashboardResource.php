<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\UserDashboardResource\Pages;
use App\Models\UserDashboard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserDashboardResource extends Resource
{
    protected static ?string $model = UserDashboard::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'Mes Tableaux de Bord';

    protected static ?string $modelLabel = 'tableau de bord';

    protected static ?string $pluralModelLabel = 'tableaux de bord';

    protected static ?string $navigationGroup = 'Personnalisation';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('nom')
                            ->label('Nom du tableau de bord')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('par_defaut')
                            ->label('Tableau de bord par défaut')
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Widgets')
                    ->schema([
                        Forms\Components\KeyValue::make('widgets_config')
                            ->label('Configuration des widgets')
                            ->keyLabel('Widget')
                            ->valueLabel('Configuration')
                            ->editable()
                            ->addable()
                            ->deletable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('par_defaut')
                    ->label('Par défaut')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('par_defaut')
                    ->label('Par défaut')
                    ->query(fn ($query) => $query->where('par_defaut', true))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('definir_par_defaut')
                    ->label('Définir par défaut')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (UserDashboard $record) => !$record->par_defaut)
                    ->action(fn (UserDashboard $record) => $record->definirParDefaut()),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUserDashboards::route('/'),
            'create' => Pages\CreateUserDashboard::route('/create'),
            'edit' => Pages\EditUserDashboard::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
