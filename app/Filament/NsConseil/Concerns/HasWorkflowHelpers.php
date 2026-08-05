<?php

namespace App\Filament\NsConseil\Concerns;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Crm\CrmSettingsService;
use Illuminate\Database\Eloquent\Model;

/**
 * Concern : helpers utilitaires du workflow phoning (téléprospecteurs, reset form, données contact).
 */
trait HasWorkflowHelpers
{
    // ── Téléprospecteurs ─────────────────────────────────────────────

    protected function queryTeleprospecteurs()
    {
        $roles = app(CrmSettingsService::class)->get('roles.teleprospecteur_roles', ['teleprospecteur']);

        return User::where(function ($q) use ($roles) {
            $q->whereHas('roles', fn ($r) => $r->whereIn('name', $roles));
            foreach ($roles as $role) {
                $q->orWhere('role_cache', $role);
            }
        })
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom');
    }

    public function getTeleprospecteurs(): array
    {
        return $this->queryTeleprospecteurs()
            ->withCount([
                'prospectsTeleprospecteur as nb_prospects' => fn ($query) => $query
                    ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                    ->whereNull('deleted_at'),
            ])
            ->get()
            ->map(fn ($u) => [
                'id'         => $u->id,
                'nom_complet' => trim("{$u->prenom} {$u->nom}"),
                'initiales'  => $u->initiales,
                'nb_prospects' => $u->nb_prospects,
            ])
            ->toArray();
    }

    // ── Formulaire contact ───────────────────────────────────────────

    protected function resetContactFormFields(): void
    {
        $this->reset([
            'selectedContactId', 'selectedContactType',
            'commentaires', 'statut_resultat',
            'rappel_date', 'rappel_heure',
            'nom_interlocuteur_standard', 'creneaux_permanence_cse', 'email_general_standard',
            'interlocuteur_nom', 'interlocuteur_fonction', 'interlocuteur_telephone', 'interlocuteur_email',
            'interlocuteur_add_nom', 'interlocuteur_add_fonction',
            'interlocuteur_add_telephone', 'interlocuteur_add_email',
            'lieu_rdv', 'invitation_agenda_envoyee', 'enregistrement_appel_joint', 'enregistrement_raison',
            'besoins_exprimes', 'objections_soulevees', 'points_attention_rdv',
            'presence_cse', 'jour_dispo_appel',
        ]);
    }

    protected function populateContactFormFields(Model $model, string $type): void
    {
        if ($type !== 'prospect' || ! $model instanceof Prospect) {
            return;
        }

        $this->nom_interlocuteur_standard  = $model->nom_interlocuteur_standard ?? '';
        $this->creneaux_permanence_cse     = $model->creneaux_permanence_cse ?? '';
        $this->email_general_standard      = $model->email_general_standard ?? '';
        $this->interlocuteur_nom           = trim(
            ($model->interlocuteur_prenom ? $model->interlocuteur_prenom . ' ' : '') .
            ($model->interlocuteur_nom ?? '')
        );
        $this->interlocuteur_fonction      = $model->interlocuteur_fonction ?? '';
        $this->interlocuteur_telephone     = $model->interlocuteur_telephone ?? '';
        $this->interlocuteur_email         = $model->interlocuteur_email ?? '';
        $this->interlocuteur_add_nom       = $model->interlocuteur_add_nom ?? '';
        $this->interlocuteur_add_fonction  = $model->interlocuteur_add_fonction ?? '';
        $this->interlocuteur_add_telephone = $model->interlocuteur_add_telephone ?? '';
        $this->interlocuteur_add_email     = $model->interlocuteur_add_email ?? '';
    }

    // ── Données enrichies pour la vue ────────────────────────────────

    public function getContactInfo(): array
    {
        return array_merge($this->currentContactData, [
            'nom_interlocuteur_standard' => $this->nom_interlocuteur_standard
                ?: ($this->currentContactData['nom_interlocuteur_standard'] ?? null),
            'creneaux_permanence_cse'    => $this->creneaux_permanence_cse
                ?: ($this->currentContactData['creneaux_permanence_cse'] ?? null),
            'email_general_standard'     => $this->email_general_standard
                ?: ($this->currentContactData['email_general_standard'] ?? null),
            'interlocuteur_nom'          => $this->interlocuteur_nom
                ?: ($this->currentContactData['interlocuteur_nom'] ?? null),
            'interlocuteur_fonction'     => $this->interlocuteur_fonction
                ?: ($this->currentContactData['interlocuteur_fonction'] ?? null),
            'interlocuteur_telephone'    => $this->interlocuteur_telephone
                ?: ($this->currentContactData['interlocuteur_telephone'] ?? null),
            'interlocuteur_email'        => $this->interlocuteur_email
                ?: ($this->currentContactData['interlocuteur_email'] ?? null),
        ]);
    }
}
