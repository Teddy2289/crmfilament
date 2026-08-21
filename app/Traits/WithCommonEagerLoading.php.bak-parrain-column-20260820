<?php

namespace App\Traits;

trait WithCommonEagerLoading
{
    /**
     * Charge les relations communes pour les prospects
     * Utilisé dans les widgets et resources pour éviter les requêtes N+1
     */
    protected function loadCommonProspectRelations($query)
    {
        return $query->with([
            'commercial:id,nom,prenom,email',
            'teleprospecteur:id,nom,prenom,email',
            'validePar:id,nom,prenom,email',
        ]);
    }

    /**
     * Charge les relations communes pour les partenaires
     */
    protected function loadCommonPartenaireRelations($query)
    {
        return $query->with([
            'commercial:id,nom,prenom,email',
            'conseiller:id,nom,prenom,email',
            'entite:id,nom',
            'entreprise:id,raison_sociale',
        ]);
    }

    /**
     * Charge les relations communes pour les clients
     */
    protected function loadCommonClientRelations($query)
    {
        return $query->with([
            'partenaire:id,nom,entreprise',
            'commercial:id,nom,prenom,email',
            'parrain:id,nom,prenom',
        ]);
    }

    /**
     * Charge les relations communes pour les rendez-vous
     */
    protected function loadCommonRendezVousRelations($query)
    {
        return $query->with([
            'commercial:id,nom,prenom,email',
            'teleprospecteur:id,nom,prenom,email',
            'rdvable',
        ]);
    }

    /**
     * Charge les relations communes pour les appels
     */
    protected function loadCommonAppelRelations($query)
    {
        return $query->with([
            'user:id,nom,prenom,email',
            'appelable',
        ]);
    }
}
