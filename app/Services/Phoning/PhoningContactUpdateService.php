<?php

namespace App\Services\Phoning;

use App\Enums\RendezVousStatut;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\StatutPhoning;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Mise à jour du contact après un appel phoning. Requirements 16.1–16.11
 */
class PhoningContactUpdateService
{
    public function updateContact(Model $contact, string $type, string $statut, array $fields): void
    {
        match ($type) {
            'prospect'    => $this->updateProspect($contact, $statut, $fields),
            'artisan'     => $this->updateArtisan($contact, $statut, $fields),
            'partenaire'  => $this->updatePartenaire($contact, $statut, $fields),
            'particulier' => $this->updateParticulier($contact, $statut, $fields),
            'client'      => $this->updateClient($contact, $statut, $fields),
            default       => throw new InvalidArgumentException("Type de contact non reconnu : {$type}"),
        };
    }

    protected function updateProspect(Prospect $prospect, string $statut, array $fields): void
    {
        $updateData = [];

        if (!empty($fields['interlocuteur_nom'])) {
            $parts = $this->splitFullName($fields['interlocuteur_nom']);
            $updateData['interlocuteur_prenom'] = $parts['prenom'];
            $updateData['interlocuteur_nom']    = $parts['nom'];
            if (!empty($fields['interlocuteur_fonction'])) {
                $updateData['interlocuteur_fonction'] = $fields['interlocuteur_fonction'];
            }
            if (!empty($fields['interlocuteur_telephone'])) {
                $updateData['interlocuteur_telephone'] = $fields['interlocuteur_telephone'];
            }
            if (!empty($fields['interlocuteur_email'])) {
                $updateData['interlocuteur_email'] = $fields['interlocuteur_email'];
            }
        }

        if (!empty($fields['nom_interlocuteur_standard'])) {
            $updateData['nom_interlocuteur_standard'] = $fields['nom_interlocuteur_standard'];
            if (!empty($fields['creneaux_permanence_cse'])) {
                $updateData['creneaux_permanence_cse'] = $fields['creneaux_permanence_cse'];
            }
            if (!empty($fields['email_general_standard'])) {
                $updateData['email_general_standard'] = $fields['email_general_standard'];
            }
        }

        if (!empty($fields['email']) && empty($prospect->email)) {
            $updateData['email'] = $fields['email'];
        }

        if (!empty($updateData)) {
            $prospect->update($updateData);
        }

        if ($this->statutExigeRappel($statut) && !empty($fields['rappel_date'])) {
            $this->appliquerRappelProspect($prospect, $fields);
        }

        if (!empty($fields['rappel_date'])) {
            $this->creerRendezVous($prospect, $fields);
        }
    }

    protected function updateArtisan(Model $artisan, string $statut, array $fields): void
    {
        $note = '[Appel du ' . now()->format('d/m/Y H:i') . ']'
            . (!empty($fields['commentaires']) ? ' ' . $fields['commentaires'] : '');
        $artisan->update([
            'notes' => ($artisan->notes ? $artisan->notes . "\n" : '') . $note,
        ]);
    }

    protected function updatePartenaire(Model $partenaire, string $statut, array $fields): void
    {
        $note = '[Appel du ' . now()->format('d/m/Y H:i') . ']'
            . (!empty($fields['commentaires']) ? "\n" . $fields['commentaires'] : '');
        $partenaire->update([
            'notes' => ($partenaire->notes ? $partenaire->notes . "\n" : '') . $note,
        ]);
    }

    protected function updateParticulier(Model $particulier, string $statut, array $fields): void
    {
        $note = '[Appel du ' . now()->format('d/m/Y H:i') . ']'
            . (!empty($fields['commentaires']) ? ' ' . $fields['commentaires'] : '');
        $particulier->update([
            'notes' => ($particulier->notes ? $particulier->notes . "\n" : '') . $note,
        ]);
    }

    protected function updateClient(Model $client, string $statut, array $fields): void
    {
        $note = '[Appel du ' . now()->format('d/m/Y H:i') . ']'
            . (!empty($fields['commentaires']) ? ' ' . $fields['commentaires'] : '');
        $extra = $client->extra_data ?? [];
        $extra['historique_appels'][] = $note;
        $client->update(['extra_data' => $extra]);
    }

    public function creerRendezVous(Prospect $prospect, array $fields): ?RendezVous
    {
        if (empty($fields['rappel_date'])) {
            return null;
        }

        $dateHeure = $fields['rappel_date'] . ' ' . ($fields['rappel_heure'] ?: '08:00');

        return RendezVous::create([
            'rdvable_type'       => Prospect::class,
            'rdvable_id'         => $prospect->id,
            'date_heure'         => $dateHeure,
            'statut'             => RendezVousStatut::Planifie,
            'teleprospecteur_id' => Auth::id(),
            'notes'              => $fields['commentaires'] ?? null,
        ]);
    }

    public function appliquerRappelProspect(Prospect $prospect, array $fields): void
    {
        $rappelAt = $fields['rappel_date'] . ' ' . ($fields['rappel_heure'] ?: '08:00');
        $prospect->update([
            'rappel_planifie_at' => $rappelAt,
            'teleprospecteur_id' => Auth::id(),
        ]);
    }

    /**
     * @return array{prenom: string, nom: string}
     */
    public function splitFullName(string $fullName): array
    {
        $fullName = trim($fullName);

        if ($fullName === '') {
            return ['prenom' => '', 'nom' => ''];
        }

        $parts = preg_split('/\s+/', $fullName, 2);

        if (count($parts) === 1) {
            return ['prenom' => '', 'nom' => $parts[0]];
        }

        return ['prenom' => $parts[0], 'nom' => $parts[1]];
    }

    protected function statutExigeRappel(string $statut): bool
    {
        return StatutPhoning::where('model_type', 'prospect')
            ->where('code', $statut)
            ->where(function ($q) {
                $q->whereNotNull('delai_rappel_jours')
                  ->orWhereNotNull('action_immediate');
            })
            ->exists();
    }
}
