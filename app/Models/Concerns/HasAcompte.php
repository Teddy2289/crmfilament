<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasAcompte
{
    /**
     * Détermine si l'entité nécessite un acompte.
     */
    public function getNecessiteAcompteAttribute(): bool
    {
        return $this->acompte_montant && $this->acompte_montant > 0;
    }

    /**
     * Détermine si l'acompte est en attente d'encaissement.
     */
    public function getAcompteEnAttenteAttribute(): bool
    {
        return $this->necessite_acompte && ! $this->acompte_encaisse;
    }

    /**
     * Enregistre le paiement d'un acompte.
     */
    public function enregistrerAcompte(float $montant): void
    {
        $this->update([
            'acompte_montant' => $montant,
            'acompte_encaisse' => true,
        ]);
    }

    /**
     * Scope pour filtrer les entités ayant un acompte en attente.
     */
    public function scopeAvecAcompteEnAttente(Builder $query): Builder
    {
        return $query->whereNotNull('acompte_montant')
            ->where('acompte_encaisse', false);
    }
}
