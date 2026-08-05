<?php

namespace App\Filament\NsConseil\Concerns;

use App\Services\Phoning\PhoningContactSearchService;
use Livewire\Attributes\On;

/**
 * Concern : session d'appel Ringover et mode supervision.
 * Requirements 3.1–3.7
 */
trait HasCallSession
{
    // ── Propriétés Livewire ──────────────────────────────────────────

    public ?string $ringoverCallId        = null;
    public ?string $ringoverCallStartedAt = null;
    public ?string $ringoverCallEndedAt   = null;
    public array   $incomingCallMatches   = [];
    public ?string $incomingCallPhone     = null;
    public ?int    $supervisedUserId      = null;
    public bool    $isSupervisorMode      = false;

    // ── Appel sortant ────────────────────────────────────────────────

    /**
     * Déclenche un appel Ringover vers le contact courant.
     * Dispatche l'événement Livewire 'ringover-call' avec le numéro du contact.
     * Req 3.3
     */
    public function callNow(): void
    {
        $phone = $this->currentContact?->telephone
            ?? $this->currentContact?->telephone_direct
            ?? null;

        $this->dispatch('ringover-call', phone: $phone);
    }

    // ── Cycle de vie de l'appel ───────────────────────────────────────

    /**
     * Met à jour le cycle de vie de l'appel Ringover.
     * Si $callId est null, remet à zéro les trois propriétés.
     * Req 3.4
     */
    #[On('ringover-call-lifecycle')]
    public function updateRingoverCallLifecycle(
        ?string $callId = null,
        ?string $startedAt = null,
        ?string $endedAt = null
    ): void {
        if ($callId === null) {
            $this->ringoverCallId        = null;
            $this->ringoverCallStartedAt = null;
            $this->ringoverCallEndedAt   = null;

            return;
        }

        $this->ringoverCallId        = $callId;
        $this->ringoverCallStartedAt = $startedAt;
        $this->ringoverCallEndedAt   = $endedAt;
    }

    // ── Appel entrant ────────────────────────────────────────────────

    /**
     * Recherche les contacts correspondant au numéro d'un appel entrant.
     * Req 3.5
     */
    #[On('search-incoming-call')]
    public function searchIncomingCallMatch(
        string $phone,
        ?string $targetType = null,
        ?int $targetId = null
    ): void {
        $this->incomingCallPhone   = $phone;
        $this->incomingCallMatches = app(PhoningContactSearchService::class)->findByPhone($phone);
    }

    // ── Supervision ──────────────────────────────────────────────────

    /**
     * Passe en mode supervision sur l'utilisateur spécifié et recharge sa file.
     * Req 3.6
     */
    public function selectSupervisedUser(int $userId): void
    {
        $this->supervisedUserId  = $userId;
        $this->isSupervisorMode  = true;

        if (method_exists($this, 'loadQueue')) {
            $this->loadQueue();
        }
    }

    /**
     * Quitte le mode supervision et revient à la file de l'utilisateur authentifié.
     * Req 3.7
     */
    public function resetToSelf(): void
    {
        $this->isSupervisorMode = false;
        $this->supervisedUserId = null;

        if (method_exists($this, 'loadQueue')) {
            $this->loadQueue();
        }
    }
}
