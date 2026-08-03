<?php

namespace App\Traits;

use App\Models\Appel;
use App\Models\Document;
use App\Models\HistoriqueInteractionUser;
use App\Models\RendezVous;
use App\Models\SentEmail;

trait HasMorphRelations
{
    /**
     * Relation avec les documents
     * Permet d'attacher des fichiers à n'importe quelle entité
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Relation avec les rendez-vous
     * Permet de planifier des RDV avec n'importe quelle entité
     */
    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }

    /**
     * Relation avec les appels
     * Permet de lier les appels téléphoniques à n'importe quelle entité
     */
    public function appels()
    {
        return $this->morphMany(Appel::class, 'appelable');
    }

    /**
     * Relation avec les emails envoyés
     * Permet de tracker les emails envoyés à n'importe quelle entité
     */
    public function sentEmails()
    {
        return $this->morphMany(SentEmail::class, 'emailable');
    }

    /**
     * Relation avec l'historique des interactions utilisateur
     * Permet de tracker les interactions sur n'importe quelle entité
     */
    public function historiqueInteractions()
    {
        return $this->morphMany(HistoriqueInteractionUser::class, 'interactable');
    }

    /**
     * Compte le nombre de documents attachés
     */
    public function getDocumentsCountAttribute(): int
    {
        return $this->documents()->count();
    }

    /**
     * Compte le nombre de rendez-vous planifiés
     */
    public function getRendezVousCountAttribute(): int
    {
        return $this->rendezVous()->count();
    }

    /**
     * Compte le nombre d'appels
     */
    public function getAppelsCountAttribute(): int
    {
        return $this->appels()->count();
    }

    /**
     * Compte le nombre d'emails envoyés
     */
    public function getSentEmailsCountAttribute(): int
    {
        return $this->sentEmails()->count();
    }

    /**
     * Vérifie si l'entité a des documents
     */
    public function hasDocuments(): bool
    {
        return $this->documents_count > 0;
    }

    /**
     * Vérifie si l'entité a des rendez-vous à venir
     */
    public function hasUpcomingRendezVous(): bool
    {
        return $this->rendezVous()
            ->where('date_heure', '>=', now())
            ->exists();
    }

    /**
     * Récupère le dernier appel
     */
    public function getLastAppelAttribute(): ?Appel
    {
        return $this->appels()->latest()->first();
    }

    /**
     * Récupère le dernier rendez-vous
     */
    public function getLastRendezVousAttribute(): ?RendezVous
    {
        return $this->rendezVous()->latest()->first();
    }
}
