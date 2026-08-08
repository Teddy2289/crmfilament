<?php

namespace App\Filament\NsConseil\Concerns;

use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\Prospect;
use App\Services\Phoning\PhoningContactResolver;
use App\Services\Phoning\PhoningQueueBuilder;
use App\Services\Phoning\PhoningQueueService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Concern : file d'appels, navigation, recherche, campagnes.
 * Requirements 2.1–2.16
 */
trait HasContactQueue
{
    // ── Propriétés Livewire ──────────────────────────────────────────

    public array   $contactQueue        = [];
    public mixed   $currentContact      = null;
    public ?int    $currentCampagneId   = null;
    public ?int    $campagneFiltreId    = null;
    public int     $progress            = 0;
    public int     $total               = 0;
    public int     $completed           = 0;
    public string  $searchQuery         = '';
    public array   $searchResults       = [];
    public bool    $showSearchResults   = false;
    public ?int    $requestedContactId   = null;
    public ?string $requestedContactType = null;
    public ?int    $selectedContactId    = null;
    public ?string $selectedContactType  = null;

    // ── File ─────────────────────────────────────────────────────────

    /**
     * Charge la file pour l'utilisateur actif (ou le supervisé).
     * Si requestedContactId est défini, le place en tête via ensureRequestedContactPriority().
     * Req 2.3
     */
    public function loadQueue(): void
    {
        $userId = $this->resolveActiveUserId();

        $this->contactQueue = app(PhoningQueueService::class)
            ->getQueueForUser($userId, $this->campagneFiltreId);

        if ($this->requestedContactId !== null) {
            $this->ensureRequestedContactPriority();
        }

        $this->total = count($this->contactQueue);
        $this->recalculerProgression();
        $this->loadNextContact();
    }

    /** Reset currentContact puis recharge. Req 2.4 */
    public function refreshQueue(): void
    {
        $this->currentContact = null;
        $this->loadQueue();
    }

    /**
     * Avance au contact suivant.
     * Résout le modèle via PhoningContactResolver et construit un tableau de données.
     * Saute récursivement les modèles disparus.
     * Req 2.5
     */
    public function loadNextContact(): void
    {
        // Task 6.1 — Libérer le verrou du contact précédent (Req 3.2)
        if ($this->currentContact !== null) {
            $type = $this->currentContact['type'] ?? null;
            $id   = (int) ($this->currentContact['id'] ?? 0);
            if ($type && $id > 0) {
                app(PhoningQueueBuilder::class)
                    ->releaseQueueReservationForUser(Auth::id(), $type, $id);
            }
        }

        if (empty($this->contactQueue)) {
            $this->currentContact     = null;
            $this->currentContactData = [];
            $this->contactType        = '';

            return;
        }

        $item  = array_shift($this->contactQueue);
        $this->contactType = $item['type'] ?? '';
        $model = app(PhoningContactResolver::class)
            ->resolveModel($this->contactType, (int) ($item['id'] ?? 0));

        if ($model === null) {
            $this->loadNextContact(); // skip silencieux

            return;
        }

        $contactData          = app(PhoningContactResolver::class)
            ->buildContactData($model, $this->contactType);
        $this->currentContact     = array_merge($item, $contactData);
        $this->currentContactData = $this->currentContact;
        $this->populateContactFormFields($model, $this->contactType);
        $this->currentCampagneId  = $item['campagne_id'] ?? null;

        // Task 6.1 — Acquérir le verrou sur le nouveau contact (Req 1.1, 1.2)
        if ($this->currentContact !== null) {
            $type = $this->currentContact['type'] ?? null;
            $id   = (int) ($this->currentContact['id'] ?? 0);
            if ($type && $id > 0) {
                $acquired = app(PhoningQueueBuilder::class)
                    ->acquireContactLock(Auth::id(), $type, $id);
                if (! $acquired) {
                    $this->loadNextContact(); // contact verrouillé → passer au suivant
                }
            }
        }
    }

    // ── Recherche ────────────────────────────────────────────────────

    /** Hook Livewire sur searchQuery. Req 2.6 */
    public function updatedSearchQuery(): void
    {
        if (strlen(trim($this->searchQuery)) < 2) {
            $this->searchResults     = [];
            $this->showSearchResults = false;

            return;
        }

        $this->searchResults     = app(PhoningQueueService::class)->search($this->searchQuery);
        $this->showSearchResults = true;
    }

    /** Insère le contact sélectionné en tête de file. Req 2.7 */
    public function selectSearchResult(int $id, string $type): void
    {
        $this->contactQueue = array_values(array_filter(
            $this->contactQueue,
            fn (array $item) => ! ($item['id'] === $id && $item['type'] === $type)
        ));

        array_unshift($this->contactQueue, [
            'type'        => $type,
            'id'          => $id,
            'campagne_id' => $this->currentCampagneId,
        ]);

        $this->loadNextContact();

        $this->searchQuery       = '';
        $this->searchResults     = [];
        $this->showSearchResults = false;
    }

    /** Vide la recherche. */
    public function clearSearch(): void
    {
        $this->searchQuery       = '';
        $this->searchResults     = [];
        $this->showSearchResults = false;
    }

    // ── Navigation ───────────────────────────────────────────────────

    /** Déplace le premier élément en dernière position sans créer d'Appel. Req 2.9 */
    public function skipCall(): void
    {
        if (empty($this->contactQueue) && $this->currentContact === null) {
            return;
        }

        // Task 6.2 — Libérer le verrou du contact courant avant le skip (Req 3.3)
        if ($this->currentContact !== null) {
            app(PhoningQueueBuilder::class)->releaseQueueReservationForUser(
                Auth::id(),
                $this->currentContact['type'] ?? '',
                (int) ($this->currentContact['id'] ?? 0),
            );
        }

        if (! empty($this->contactQueue)) {
            array_push($this->contactQueue, array_shift($this->contactQueue));
        }
    }

    /**
     * Renouvelle le verrou du contact courant. Appelé par polling Livewire.
     * Si le verrou a expiré ou a été repris, notifie l'utilisateur et passe au suivant.
     * Task 6.3 — Req 2.1, 2.2, 2.3
     */
    public function renewCurrentContactLock(): void
    {
        if ($this->currentContact === null) {
            return;
        }

        $type = $this->currentContact['type'] ?? null;
        $id   = (int) ($this->currentContact['id'] ?? 0);

        if (! $type || $id <= 0) {
            return;
        }

        $renewed = app(PhoningQueueBuilder::class)
            ->renewContactLock(Auth::id(), $type, $id);

        if (! $renewed) {
            Notification::make()
                ->title('Fiche libérée')
                ->body('Le verrou sur cette fiche a expiré. Passage au contact suivant.')
                ->warning()
                ->send();

            $this->loadNextContact();
        }
    }

    /**
     * Libère le verrou du contact courant à la destruction de la page Livewire.
     * Task 6.4 — Req 3.4
     */
    public function dehydrate(): void
    {
        if ($this->currentContact !== null) {
            app(PhoningQueueBuilder::class)->releaseQueueReservationForUser(
                Auth::id(),
                $this->currentContact['type'] ?? '',
                (int) ($this->currentContact['id'] ?? 0),
            );
        }
    }

    /** Assure que le contact demandé via URL est en tête de file. Req 2.10 */
    protected function ensureRequestedContactPriority(): void
    {
        $this->contactQueue = array_values(array_filter(
            $this->contactQueue,
            fn (array $item) => ! (
                (int) $item['id'] === $this->requestedContactId &&
                $item['type']     === $this->requestedContactType
            )
        ));

        array_unshift($this->contactQueue, [
            'type'        => $this->requestedContactType,
            'id'          => $this->requestedContactId,
            'campagne_id' => null,
        ]);
    }

    // ── Campagnes ────────────────────────────────────────────────────

    /** Filtre la file sur une campagne puis recharge. Req 2.11 */
    public function selectCampagne(int $campagneId): void
    {
        $this->campagneFiltreId = $campagneId;
        $this->loadQueue();
    }

    /** Supprime le filtre campagne et recharge. Req 2.12 */
    public function clearCampagne(): void
    {
        $this->campagneFiltreId = null;
        $this->loadQueue();
    }

    /** Campagnes actives accessibles à l'utilisateur courant. Req 2.13 */
    public function getCampagnesDisponibles(): array
    {
        return app(PhoningQueueService::class)
            ->getCampagnesDisponibles(Auth::id());
    }

    /** Stats de la campagne filtrée, ou null si aucun filtre. Req 2.14 */
    public function getCampagneInfo(): ?array
    {
        if ($this->campagneFiltreId === null) {
            return null;
        }

        return app(PhoningQueueService::class)
            ->getCampagneInfo($this->campagneFiltreId);
    }

    /** Contacts restants dans la file (scopé à campagneFiltreId si défini). Req 2.15 */
    public function getContactsRestantsCount(): int
    {
        if ($this->campagneFiltreId !== null) {
            $campagne = CampagnePhoning::find($this->campagneFiltreId);

            return $campagne ? $campagne->countQueueContacts() : 0;
        }

        return count($this->contactQueue) + ($this->currentContact ? 1 : 0);
    }

    /** 15 derniers Appel pour le contact courant, tri created_at desc. Req 2.16 */
    public function getCallHistory(): array
    {
        if ($this->currentContact === null) {
            return [];
        }

        $modelClass = match ($this->currentContact['type'] ?? '') {
            'prospect'   => Prospect::class,
            'partenaire' => ContactPartenaire::class,
            'client'     => Client::class,
            default      => null,
        };

        if ($modelClass === null) {
            return [];
        }

        return Appel::where('appelable_type', $modelClass)
            ->where('appelable_id', $this->currentContact['id'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->toArray();
    }

    // ── Progression ──────────────────────────────────────────────────

    /** Hook Livewire : recalcule progress quand completed change. Req 2.8 */
    public function updatedCompleted(): void
    {
        $this->recalculerProgression();
    }

    /** Hook Livewire : recalcule progress quand total change. Req 2.8 */
    public function updatedTotal(): void
    {
        $this->recalculerProgression();
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Recalcule progress = round(completed / total × 100) si total > 0, sinon 0.
     * Req 2.8
     */
    protected function recalculerProgression(): void
    {
        $this->progress = $this->total > 0
            ? (int) round(($this->completed / $this->total) * 100)
            : 0;
    }

    /**
     * Résout l'userId actif : supervisedUserId si isSupervisorMode, sinon Auth::id().
     */
    private function resolveActiveUserId(): int
    {
        if (
            property_exists($this, 'isSupervisorMode') &&
            $this->isSupervisorMode &&
            ! empty($this->supervisedUserId)
        ) {
            return (int) $this->supervisedUserId;
        }

        return (int) Auth::id();
    }
}
