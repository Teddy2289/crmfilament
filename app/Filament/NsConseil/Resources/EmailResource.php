<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\EmailResource\Pages;
use App\Models\Email;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EmailResource extends Resource
{
    protected static ?string $model = Email::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Emails';

    protected static ?int $navigationSort = 1;

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options([
                            Email::TYPE_SENT => 'Envoyé',
                            Email::TYPE_RECEIVED => 'Reçu',
                            Email::TYPE_DRAFT => 'Brouillon',
                        ])
                        ->default(Email::TYPE_SENT)
                        ->required(),

                    Forms\Components\TextInput::make('from_email')
                        ->label('De (email)')
                        ->email()
                        ->default(Auth::user()?->email)
                        ->required(),

                    Forms\Components\TextInput::make('from_name')
                        ->label('De (nom)')
                        ->default(Auth::user()?->nom_complet),

                    Forms\Components\Textarea::make('to_email')
                        ->label('À')
                        ->rows(2)
                        ->required(),

                    Forms\Components\Textarea::make('cc_email')
                        ->label('CC')
                        ->rows(2),

                    Forms\Components\Textarea::make('bcc_email')
                        ->label('CCI')
                        ->rows(2),

                    Forms\Components\TextInput::make('subject')
                        ->label('Sujet')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\RichEditor::make('body_html')
                        ->label('Message')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('body_text')
                        ->label('Version texte')
                        ->rows(5)
                        ->columnSpanFull()
                        ->helperText('Version texte alternative pour les clients qui ne supportent pas HTML'),

                    Forms\Components\Select::make('priority')
                        ->label('Priorité')
                        ->options([
                            Email::PRIORITY_LOW => 'Basse',
                            Email::PRIORITY_NORMAL => 'Normale',
                            Email::PRIORITY_HIGH => 'Haute',
                        ])
                        ->default(Email::PRIORITY_NORMAL),

                    Forms\Components\Select::make('folder')
                        ->label('Dossier')
                        ->options([
                            Email::FOLDER_INBOX => 'Boîte de réception',
                            Email::FOLDER_SENT => 'Envoyés',
                            Email::FOLDER_DRAFTS => 'Brouillons',
                            Email::FOLDER_TRASH => 'Corbeille',
                            Email::FOLDER_ARCHIVE => 'Archives',
                        ])
                        ->default(Email::FOLDER_SENT),

                    Forms\Components\TagsInput::make('labels')
                        ->label('Étiquettes')
                        ->placeholder('Ajouter une étiquette')
                        ->suggestions(['urgent', 'important', 'suivi', 'facture', 'contrat']),
                ])
                ->columns(2),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->color(fn ($record) => $record->is_read ? 'gray' : 'primary'),

                Tables\Columns\TextColumn::make('from')
                    ->label('De')
                    ->searchable(['from_email', 'from_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('to_email')
                    ->label('À')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Sujet')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('body_preview')
                    ->label('Aperçu')
                    ->limit(100)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('folder')
                    ->label('Dossier')
                    ->formatStateUsing(fn ($state) => $state ? Email::find($state)?->folder_label : $state)
                    ->colors([
                        'primary' => Email::FOLDER_INBOX,
                        'success' => Email::FOLDER_SENT,
                        'warning' => Email::FOLDER_DRAFTS,
                        'danger' => Email::FOLDER_TRASH,
                        'gray' => Email::FOLDER_ARCHIVE,
                    ]),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorité')
                    ->formatStateUsing(fn ($state) => $state ? Email::find($state)?->priority_label : $state)
                    ->colors([
                        'gray' => Email::PRIORITY_LOW,
                        'primary' => Email::PRIORITY_NORMAL,
                        'danger' => Email::PRIORITY_HIGH,
                    ]),

                Tables\Columns\IconColumn::make('has_attachments')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-paperclip')
                    ->falseIcon(''),

                Tables\Columns\TextColumn::make('received_at')
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('folder')
                    ->label('Dossier')
                    ->options([
                        Email::FOLDER_INBOX => 'Boîte de réception',
                        Email::FOLDER_SENT => 'Envoyés',
                        Email::FOLDER_DRAFTS => 'Brouillons',
                        Email::FOLDER_TRASH => 'Corbeille',
                        Email::FOLDER_ARCHIVE => 'Archives',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        Email::TYPE_SENT => 'Envoyé',
                        Email::TYPE_RECEIVED => 'Reçu',
                        Email::TYPE_DRAFT => 'Brouillon',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        Email::PRIORITY_HIGH => 'Haute',
                        Email::PRIORITY_NORMAL => 'Normale',
                        Email::PRIORITY_LOW => 'Basse',
                    ]),

                Tables\Filters\Filter::make('unread')
                    ->label('Non lus')
                    ->query(fn ($query) => $query->unread()),

                Tables\Filters\Filter::make('with_attachments')
                    ->label('Avec pièces jointes')
                    ->query(fn ($query) => $query->whereNotNull('attachments')->where('attachments', '!=', '[]')),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_as_read')
                    ->label('Marquer comme lu')
                    ->icon('heroicon-o-envelope-open')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->is_read)
                    ->action(fn ($record) => $record->markAsRead()),

                Tables\Actions\Action::make('mark_as_unread')
                    ->label('Marquer comme non lu')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn ($record) => $record->is_read)
                    ->action(fn ($record) => $record->markAsUnread()),

                Tables\Actions\Action::make('archive')
                    ->label('Archiver')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn ($record) => $record->folder !== Email::FOLDER_ARCHIVE)
                    ->action(fn ($record) => $record->archive()),

                Tables\Actions\Action::make('move_to_trash')
                    ->label('Corbeille')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn ($record) => $record->folder !== Email::FOLDER_TRASH)
                    ->action(fn ($record) => $record->moveToTrash()),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_read')
                        ->label('Marquer comme lus')
                        ->icon('heroicon-o-envelope-open')
                        ->action(fn ($records) => $records->each->markAsRead()),

                    Tables\Actions\BulkAction::make('mark_as_unread')
                        ->label('Marquer comme non lus')
                        ->icon('heroicon-o-envelope')
                        ->action(fn ($records) => $records->each->markAsUnread()),

                    Tables\Actions\BulkAction::make('archive')
                        ->label('Archiver')
                        ->icon('heroicon-o-archive-box')
                        ->action(fn ($records) => $records->each->archive()),

                    Tables\Actions\BulkAction::make('move_to_trash')
                        ->label('Corbeille')
                        ->icon('heroicon-o-trash')
                        ->action(fn ($records) => $records->each->moveToTrash()),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('received_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('user_id', Auth::id()));
    }

    // ─────────────────────────────────────────────────────────────────
    // PAGES
    // ─────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmails::route('/'),
            'create' => Pages\CreateEmail::route('/create'),
            'view' => Pages\ViewEmail::route('/{record}'),
            'edit' => Pages\EditEmail::route('/{record}/edit'),
        ];
    }
}
