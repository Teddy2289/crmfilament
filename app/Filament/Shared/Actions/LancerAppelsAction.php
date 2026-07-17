<?php

namespace App\Filament\Shared\Actions;

use App\Filament\NsConseil\Pages\PhoningWorkflow;
use App\Models\CampagnePhoning;
use Filament\Tables\Actions\Action;

/**
 * Header action linking to the active phoning campaign for a given
 * type_entite ('prospects', 'partenaires', 'clients'), if one exists
 * for the current user.
 */
class LancerAppelsAction
{
    public static function make(string $typeEntite): Action
    {
        return Action::make('lancer_appels')
            ->label('Lancer les appels')
            ->icon('heroicon-o-phone-arrow-up-right')
            ->color('primary')
            ->visible(function () use ($typeEntite) {
                $userId = auth()->id();

                return CampagnePhoning::active()
                    ->forUser($userId)
                    ->where('type_entite', $typeEntite)
                    ->exists();
            })
            ->url(function () use ($typeEntite) {
                $userId = auth()->id();
                $campagne = CampagnePhoning::active()
                    ->forUser($userId)
                    ->where('type_entite', $typeEntite)
                    ->first();

                return $campagne
                    ? PhoningWorkflow::getUrl(['campagne_id' => $campagne->id])
                    : '#';
            });
    }
}
