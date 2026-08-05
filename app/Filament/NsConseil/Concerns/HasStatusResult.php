<?php

namespace App\Filament\NsConseil\Concerns;

use App\Models\Appel;
use App\Models\PipelineStatut;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use App\Services\Crm\PipelineStatutService;
use App\Services\Phoning\PhoningContactResolver;
use App\Support\CsePhoningWorkflow;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Concern : validation, formulaire et persistance du résultat d'appel.
 * Requirements 5.1–5.18, 13.1, 14.1, 14.4
 */
trait HasStatusResult
{
    // ── Propriétés Livewire ──────────────────────────────────────────

    public string $statut_resultat            = '';
    public string $commentaires               = '';
    public string $rappel_date                = '';
    public string $rappel_heure               = '';
    public string $nom_interlocuteur_standard = '';
    public string $creneaux_permanence_cse    = '';
    public string $email_general_standard     = '';
    public string $interlocuteur_nom          = '';
    public string $interlocuteur_fonction     = '';
    public string $interlocuteur_telephone    = '';
    public string $interlocuteur_email        = '';
    public string $interlocuteur_add_nom      = '';
    public string $interlocuteur_add_fonction = '';
    public string $interlocuteur_add_telephone = '';
    public string $interlocuteur_add_email    = '';
    public string $lieu_rdv                   = '';
    public bool   $invitation_agenda_envoyee  = false;
    public bool   $enregistrement_appel_joint = false;
    public string $enregistrement_raison      = '';
    public string $besoins_exprimes           = '';
    public string $objections_soulevees       = '';
    public string $points_attention_rdv       = '';
    public string $presence_cse               = '';
    public string $jour_dispo_appel           = '';
    public ?int   $lastAppelId                = null;

    // ── Codes statuts ────────────────────────────────────────────────

    /** Req 5.10 : rester public pour rétrocompatibilité PhoningWorkflowStatusesTest */
    public function getStatusValidationCodes(): array
    {
        $type = $this->contactType ?: 'prospect';

        return StatutPhoning::forModelType($type)
            ->pluck('code')
            ->filter(fn ($code) => filled($code))
            ->values()
            ->all();
    }

    /** Req 5.12 : retourner le StatutPhoning courant ou null. */
    public function getSelectedStatus(): ?StatutPhoning
    {
        if (blank($this->statut_resultat)) {
            return null;
        }

        return StatutPhoning::where('model_type', $this->contactType ?: 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();
    }

    /** Req 5.18 : codes déclenchant un rappel. */
    public function getRappelStatusCodes(): array
    {
        $type = $this->contactType ?: 'prospect';

        return StatutPhoning::forModelType($type)
            ->filter(fn (StatutPhoning $s) => filled($s->action_immediate)
                || filled($s->fiche_type)
                || (int) ($s->delai_rappel_jours ?? 0) > 0)
            ->pluck('code')
            ->filter(fn ($code) => filled($code))
            ->values()
            ->all();
    }

    public function commentaireRequis(): bool
    {
        if (blank($this->statut_resultat)) {
            return false;
        }

        return (bool) ($this->getSelectedStatus()?->note_obligatoire);
    }

    public function messageCommentaireObligatoire(): string
    {
        $statut = $this->getSelectedStatus();

        if ($statut?->message_note_obligatoire) {
            return 'Note obligatoire : ' . $statut->message_note_obligatoire;
        }

        return 'Un commentaire est obligatoire pour ce statut.';
    }

    /** Req 5.16 */
    public function compterTentativesNonAbouties(?Model $contact = null): int
    {
        $contact = $contact ?? $this->currentContact;
        if (! $contact) {
            return 0;
        }

        $codes = StatutPhoning::where('model_type', 'prospect')
            ->where('compte_comme_tentative', true)
            ->pluck('code')
            ->toArray();

        if (empty($codes)) {
            $codes = ['nrp', 'fax', 'std_nr'];
        }

        $appelableType = is_array($contact)
            ? match ($contact['type'] ?? 'prospect') {
                'prospect'   => Prospect::class,
                default      => Prospect::class,
            }
            : get_class($contact);

        $appelableId = is_array($contact) ? ($contact['id'] ?? 0) : $contact->id;

        return Appel::where('appelable_type', $appelableType)
            ->where('appelable_id', $appelableId)
            ->whereIn('phoning_status', $codes)
            ->count();
    }

    /** Req 5.17 */
    public function getTentativesAppel(): int
    {
        return $this->compterTentativesNonAbouties();
    }

    // ── Vue statuts ──────────────────────────────────────────────────

    /** Req 5.13 */
    public function getStatutsPhoning(): array
    {
        $type = $this->contactType ?: 'prospect';

        return StatutPhoning::forModelType($type)
            ->map(fn ($s) => [
                'value'          => $s->code,
                'label'          => $s->label,
                'libelle'        => $s->libelle,
                'sub'            => $s->description,
                'action'         => $s->action_immediate,
                'couleur'        => $s->couleur,
                'bar'            => $s->couleur_css,
                'icon'           => $s->icone,
                'icone'          => $s->icone,
                'note_obligatoire' => $s->note_obligatoire,
                'prioritaire'    => $s->prioritaire,
                'fiche_type'     => $s->fiche_type,
                'groupe'         => $s->groupe,
                'groupe_label'   => $s->groupe_label,
                'pipeline_statut' => $s->pipeline_statut,
                'pipeline_label' => $this->pipelineLabelFor($type, $s->pipeline_statut),
            ])
            ->toArray();
    }

    /** Req 5.14 */
    public function getStatutsPhoningGroupes(): array
    {
        if (($this->contactType ?: 'prospect') !== 'prospect') {
            return ['default' => ['label' => 'Résultats', 'statuts' => $this->getStatutsPhoning()]];
        }

        return CsePhoningWorkflow::statutsGroupesPourProspect();
    }

    /** Req 5.15 */
    public function getPipelineTransitionPreview(): ?array
    {
        if (($this->contactType ?: 'prospect') !== 'prospect' || blank($this->statut_resultat)) {
            return null;
        }

        $selected = $this->getSelectedStatus();
        if (! $selected) {
            return null;
        }

        $currentCode = is_array($this->currentContact)
            ? ($this->currentContact['statut_code'] ?? null)
            : ($this->currentContactData['statut_code'] ?? null);

        $nextCode = $selected->pipeline_statut;

        return [
            'current'     => $this->pipelineStatutPayload('prospect', $currentCode),
            'call_status' => [
                'code'  => $selected->code,
                'label' => $selected->label,
                'icon'  => $selected->icone,
                'bar'   => $selected->couleur_css,
            ],
            'next'      => $this->pipelineStatutPayload('prospect', $nextCode),
            'unchanged' => filled($nextCode) && $nextCode === $currentCode,
        ];
    }

    /** @return array{code: ?string, label: string, badge_style: string}|null */
    protected function pipelineStatutPayload(string $modelType, ?string $code): ?array
    {
        if (! $code) {
            return null;
        }

        $statut = PipelineStatut::findFor($modelType, $code);

        return [
            'code'        => $code,
            'label'       => $statut?->label ?? app(PipelineStatutService::class)->label($modelType, $code),
            'badge_style' => $statut?->badge_style
                ?? 'background:rgb(243 244 246); color:rgb(55 65 81); border:1px solid rgb(229 231 235);',
        ];
    }

    protected function pipelineLabelFor(string $modelType, ?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return app(PipelineStatutService::class)->label($modelType, $code);
    }

    // ── Interlocuteur ────────────────────────────────────────────────

    /** Req 5.11 */
    public function saveInterlocuteur(): void
    {
        $contact = $this->currentContact;
        $type    = $this->contactType ?? '';

        if (! $contact || $type !== 'prospect') {
            return;
        }

        // currentContact peut être un array (après HasContactQueue) ou un Model
        $prospectId = is_array($contact) ? ($contact['id'] ?? null) : $contact->id;
        if (! $prospectId) {
            return;
        }

        $this->validate([
            'interlocuteur_email'  => 'nullable|email',
            'email_general_standard' => 'nullable|email',
        ]);

        $prospect = Prospect::find($prospectId);
        if (! $prospect) {
            return;
        }

        $this->persistProspectInterlocuteurFields($prospect);

        // Recharger currentContact via HasContactQueue resolver
        $resolver = app(PhoningContactResolver::class);
        $model    = $resolver->resolveModel($type, $prospectId);
        if ($model) {
            $this->currentContact     = array_merge(
                is_array($contact) ? $contact : [],
                $resolver->buildContactData($model, $type)
            );
            $this->currentContactData = is_array($this->currentContact) ? $this->currentContact : [];
        }

        Notification::make()
            ->title('Interlocuteur enregistré')
            ->body('Les informations ont bien été sauvegardées.')
            ->success()
            ->send();
    }

    public function getProspectInterlocuteurUpdateData(): array
    {
        $updateData = [];

        foreach ([
            'nom_interlocuteur_standard', 'creneaux_permanence_cse', 'email_general_standard',
            'interlocuteur_fonction', 'interlocuteur_telephone', 'interlocuteur_email',
            'interlocuteur_add_nom', 'interlocuteur_add_fonction',
            'interlocuteur_add_telephone', 'interlocuteur_add_email',
        ] as $field) {
            if ($this->$field !== '') {
                $updateData[$field] = $this->$field;
            }
        }

        if ($this->interlocuteur_nom !== '') {
            [$prenom, $nom] = $this->splitFullName($this->interlocuteur_nom);
            if ($prenom !== null) {
                $updateData['interlocuteur_prenom'] = $prenom;
            }
            if ($nom !== null) {
                $updateData['interlocuteur_nom'] = $nom;
            }
        }

        return $updateData;
    }

    protected function persistProspectInterlocuteurFields(Prospect $prospect): void
    {
        $updateData = $this->getProspectInterlocuteurUpdateData();
        if (! empty($updateData)) {
            $prospect->update($updateData);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────

    protected function splitFullName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return [null, null];
        }

        $parts  = preg_split('/\s+/', $fullName);
        $prenom = array_shift($parts);
        $nom    = implode(' ', $parts);

        return [$prenom, $nom ?: null];
    }
}
