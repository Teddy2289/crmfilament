<?php

namespace App\Services\Cache;

use App\Models\Client;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Enums\ProspectStatut;
use App\Enums\OrganizationStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NavigationBadgeCacheService
{
    /**
     * Durée de vie du cache en secondes (5 minutes)
     */
    protected int $cacheTtl = 300;

    /**
     * Récupère le compteur de prospects actifs depuis le cache
     */
    public function getProspectsCount(): int
    {
        return Cache::remember('navigation_badges.prospects_count', $this->cacheTtl, function () {
            return Prospect::whereNotIn('statut', [
                ProspectStatut::KO->value,
                ProspectStatut::QF->value,
            ])->count();
        });
    }

    /**
     * Récupère le compteur de partenaires actifs depuis le cache
     */
    public function getPartenairesCount(): int
    {
        return Cache::remember('navigation_badges.partenaires_count', $this->cacheTtl, function () {
            return Partenaire::whereNotIn('statut', [OrganizationStatus::Refus->value])->count();
        });
    }

    /**
     * Récupère le compteur de clients depuis le cache
     */
    public function getClientsCount(): int
    {
        return Cache::remember('navigation_badges.clients_count', $this->cacheTtl, function () {
            return Client::count();
        });
    }

    /**
     * Invalide le cache des compteurs de navigation
     */
    public function invalidateNavigationBadges(): void
    {
        Cache::forget('navigation_badges.prospects_count');
        Cache::forget('navigation_badges.partenaires_count');
        Cache::forget('navigation_badges.clients_count');
    }

    /**
     * Invalide le cache d'une resource spécifique
     */
    public function invalidateResourceBadge(string $resource): void
    {
        $key = "navigation_badges.{$resource}_count";
        Cache::forget($key);
    }

    /**
     * Rafraîchit le cache d'une resource spécifique
     */
    public function refreshResourceBadge(string $resource): int
    {
        $this->invalidateResourceBadge($resource);
        
        return match ($resource) {
            'prospects' => $this->getProspectsCount(),
            'partenaires' => $this->getPartenairesCount(),
            'clients' => $this->getClientsCount(),
            default => 0,
        };
    }

    /**
     * Rafraîchit tous les compteurs de navigation
     */
    public function refreshAllBadges(): array
    {
        $this->invalidateNavigationBadges();

        return [
            'prospects' => $this->getProspectsCount(),
            'partenaires' => $this->getPartenairesCount(),
            'clients' => $this->getClientsCount(),
        ];
    }

    /**
     * Définit la durée de vie du cache
     */
    public function setCacheTtl(int $seconds): self
    {
        $this->cacheTtl = $seconds;
        return $this;
    }

    /**
     * Récupère la durée de vie du cache actuelle
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }
}
