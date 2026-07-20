<?php

namespace App\Filament\NsConseil\Actions;

use App\Jobs\VerifierRattachementsClientsJob;
use App\Models\Client;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

/**
 * L'import de clients ne rattache un partenaire que si la nomenclature
 * correspond exactement au moment de l'import : un partenaire créé ou
 * renommé après coup laisse des clients orphelins sans repasser dessus.
 * Ce bouton relance la même correspondance tolérante que la commande CLI
 * `clients:relier-partenaires`, en tâche de fond.
 */
class VerifierRattachementsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'verifier_rattachements';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Vérifier les rattachements')
            ->icon('heroicon-o-link')
            ->color('rattachement')
            ->badge(fn () => Client::partenaireNonRattaches()->count() ?: null)
            ->badgeColor('rattachement')
            ->requiresConfirmation()
            ->modalHeading('Vérifier les rattachements partenaire')
            ->modalDescription(
                fn () => Client::partenaireNonRattaches()->count()
                    .' client(s) sont actuellement marqués « partenaire non rattaché ». '
                    .'Cette action retente le rattachement en tâche de fond et vous notifie une fois terminé.'
            )
            ->modalSubmitActionLabel('Lancer la vérification')
            ->action(function () {
                VerifierRattachementsClientsJob::dispatch(auth()->id());

                Notification::make()
                    ->title('Vérification des rattachements lancée')
                    ->body('Vous serez notifié une fois la vérification terminée.')
                    ->success()
                    ->send();
            });
    }
}
