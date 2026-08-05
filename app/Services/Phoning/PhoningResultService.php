<?php

namespace App\Services\Phoning;

use App\Models\Appel;
use App\Models\StatutPhoning;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service : logique de résultat d'appel (statut, commentaire, email preview).
 * Requirements 5.3, 5.6
 */
class PhoningResultService
{
    /**
     * Vérifie la réservation cache puis crée un enregistrement Appel en transaction.
     *
     * @throws AuthorizationException Si le contact n'est pas réservé pour l'utilisateur courant.
     */
    public function applyResult(Model $contact, string $type, string $statut, array $fields): Appel
    {
        $cacheKey = "phoning_queue_reservation_{$type}_{$contact->id}";

        $reservedBy = Cache::get($cacheKey);

        if ($reservedBy !== Auth::id()) {
            throw new AuthorizationException(
                "Ce contact n'est pas réservé pour l'utilisateur courant."
            );
        }

        return DB::transaction(function () use ($contact, $statut, $fields): Appel {
            $appel = Appel::create([
                'appelable_type' => get_class($contact),
                'appelable_id'   => $contact->id,
                'phoning_status' => $statut,
                'commentaires'   => $fields['commentaires'] ?? null,
                'date_heure'     => now(),
                'user_id'        => Auth::id(),
                'campagne_id'    => $fields['campagne_id'] ?? null,
            ]);

            return $appel;
        });
    }

    /**
     * Indique si un aperçu email doit être affiché avant la persistance du résultat.
     * Req 5.5
     */
    public function shouldPreviewEmail(string $statut, string $contactType): bool
    {
        return in_array($statut, ['rdv', 'bloc', 'ncse_50', 'cse_hz'], true);
    }

    /**
     * Retourne le libellé du statut pour un type de contact donné.
     */
    public function getStatutLabel(string $statut, string $contactType): string
    {
        $statutPhoning = StatutPhoning::findFor($contactType, $statut);

        return $statutPhoning?->label ?? $statut;
    }

    /**
     * Indique si un commentaire est obligatoire pour le statut et type donnés.
     * Req 5.3
     */
    public function isCommentRequired(string $statut, string $contactType): bool
    {
        $statutPhoning = StatutPhoning::findFor($contactType, $statut);

        return (bool) ($statutPhoning?->note_obligatoire ?? false);
    }
}
