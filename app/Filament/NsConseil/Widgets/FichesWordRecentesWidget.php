<?php
namespace App\Filament\NsConseil\Widgets;

use App\Mail\FichePdfResendMail;
use App\Models\Appel;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class FichesWordRecentesWidget extends BaseWidget
{
    protected static ?string $heading = 'Fiches PDF générées récemment';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $query = Appel::query()
            ->whereNotNull('fiche_word_path')
            ->with(['appelable', 'user'])
            ->latest('fiche_word_generated_at')
            ->limit(10);

        if (! $user->hasRoleCache('admin') && ! $user->hasRoleCache('superviseur') && ! $user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('fiche_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bleue' => 'blue',
                        'jaune' => 'yellow',
                        'verte' => 'green',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('appelable.nom')
                    ->label('Contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destinataire')
                    ->label('Destinataire')
                    ->state(function (Appel $record): string {
                        $contact = $record->appelable;
                        if (! $contact || ! method_exists($contact, 'commercial')) {
                            return 'Non attribué';
                        }
                        $contact->loadMissing('commercial');
                        return $contact->commercial?->email ?: 'Non attribué';
                    })
                    ->placeholder('Non attribué')
                    ->copyable(),
                Tables\Columns\TextColumn::make('phoning_status')
                    ->label('Statut appel')
                    ->badge(),
                Tables\Columns\TextColumn::make('fiche_word_generated_at')
                    ->label('Générée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Téléprospecteur')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('fiche_jaune_j7_envoye_at')
                    ->label('J+7 envoyé')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('fiche_verte_envoyee_at')
                    ->label('Verte envoyée')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fiche_word_path')
                    ->label('PDF')
                    ->formatStateUsing(fn ($state) => 'Télécharger')
                    ->url(fn ($record) => $record->fiche_word_path)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->actions([
                Action::make('preview')
                    ->label('Aperçu')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Appel $record): ?string => $record->fiche_word_path ?: null)
                    ->openUrlInNewTab()
                    ->visible(fn (Appel $record): bool => filled($record->fiche_word_path)),
                Action::make('resend')
                    ->label('Renvoyer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Renvoyer la fiche PDF')
                    ->modalDescription('La fiche PDF sera envoyée au destinataire commercial indiqué dans la ligne.')
                    ->action(function (Appel $record): void {
                        $contact = $record->appelable;
                        if (! $contact || ! method_exists($contact, 'commercial')) {
                            Notification::make()->title('Destinataire introuvable')->danger()->send();
                            return;
                        }
                        $contact->loadMissing('commercial');
                        $email = $contact->commercial?->email;
                        $mailable = new FichePdfResendMail($record, (string) $email);
                        if (! $email || ! $mailable->fichePdfPathAbsolu() || ! is_file($mailable->fichePdfPathAbsolu())) {
                            Notification::make()->title('PDF ou destinataire indisponible')->body('Aucun renvoi n’a été placé en file.')->danger()->send();
                            return;
                        }
                        Mail::to($email)->queue($mailable);
                        Notification::make()->title('Fiche mise en file')->body("Destinataire : {$email}")->success()->send();
                    }),
            ])
            ->defaultSort('fiche_word_generated_at', 'desc')
            ->paginated(false);
    }
}
