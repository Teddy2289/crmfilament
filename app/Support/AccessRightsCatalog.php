<?php

namespace App\Support;

use App\Models\Client;
use App\Models\CampagnePhoning;
use App\Models\DossierFormation;
use App\Models\Entreprise;
use App\Models\Opportunite;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\ScriptAppel;
use App\Models\StatutPhoning;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class AccessRightsCatalog
{
    /**
     * @var array<int|string, array<int, string>>
     */
    private static array $userPermissionCache = [];

    /**
     * Source de vérité des droits par module/entité exposés en super administration.
     *
     * @return array<string, array{label: string, panel: string, permissions: array<string, string>}>
     */
    public static function modules(): array
    {
        $modules = [
            'prospects' => [
                'label' => 'AOPIA - Prospects',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'prospects.view_any' => 'Lister',
                    'prospects.view' => 'Voir',
                    'prospects.create' => 'Créer',
                    'prospects.update' => 'Modifier',
                    'prospects.valider_qf' => 'Valider QF',
                ],
            ],
            'partenaires' => [
                'label' => 'AOPIA - Partenaires',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'partenaires.view_any' => 'Lister',
                    'partenaires.view' => 'Voir',
                    'partenaires.create' => 'Créer',
                    'partenaires.update' => 'Modifier',
                    'partenaires.delete' => 'Supprimer',
                ],
            ],
            'clients' => [
                'label' => 'AOPIA - Clients',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'clients.view_any' => 'Lister',
                    'clients.view' => 'Voir',
                    'clients.create' => 'Créer',
                    'clients.update' => 'Modifier',
                ],
            ],
            'opportunites' => [
                'label' => 'AOPIA - Opportunités',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'opportunites.view_any' => 'Lister',
                    'opportunites.view' => 'Voir',
                    'opportunites.create' => 'Créer',
                    'opportunites.update' => 'Modifier',
                    'opportunites.delete' => 'Supprimer',
                ],
            ],
            'rendez_vous' => [
                'label' => 'AOPIA - Rendez-vous',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'rendez_vous.view_any' => 'Lister',
                    'rendez_vous.view' => 'Voir',
                    'rendez_vous.create' => 'Créer',
                    'rendez_vous.update' => 'Modifier',
                    'rendez_vous.delete' => 'Supprimer',
                ],
            ],
            'entreprises' => [
                'label' => 'AOPIA - Entreprises',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'entreprises.view_any' => 'Lister',
                    'entreprises.view' => 'Voir',
                    'entreprises.create' => 'Créer',
                    'entreprises.update' => 'Modifier',
                    'entreprises.delete' => 'Supprimer',
                ],
            ],
            'campagne_phonings' => [
                'label' => 'AOPIA - Campagnes phoning',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'campagne_phonings.view_any' => 'Lister',
                    'campagne_phonings.view' => 'Voir',
                    'campagne_phonings.create' => 'Créer',
                    'campagne_phonings.update' => 'Modifier',
                    'campagne_phonings.delete' => 'Supprimer',
                ],
            ],
            'dossier_formations' => [
                'label' => 'AOPIA - Dossiers formation',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'dossier_formations.view_any' => 'Lister',
                    'dossier_formations.view' => 'Voir',
                    'dossier_formations.create' => 'Créer',
                    'dossier_formations.update' => 'Modifier',
                    'dossier_formations.delete' => 'Supprimer',
                ],
            ],
            'activites' => [
                'label' => 'AOPIA - Activités',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'activites.create' => 'Créer',
                    'activites.update' => 'Modifier',
                ],
            ],
            'rapports' => [
                'label' => 'AOPIA - Rapports',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'rapports.view' => 'Voir',
                    'rapports.export' => 'Exporter',
                ],
            ],
            'document_knowledges' => [
                'label' => 'AOPIA - Base de connaissances',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'document_knowledges.view_any' => 'Lister',
                    'document_knowledges.view' => 'Voir',
                    'document_knowledges.create' => 'Créer',
                    'document_knowledges.update' => 'Modifier',
                    'document_knowledges.delete' => 'Supprimer',
                ],
            ],
            'script_appels' => [
                'label' => 'AOPIA - Scripts appel',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'script_appels.view_any' => 'Lister',
                    'script_appels.view' => 'Voir',
                    'script_appels.create' => 'Créer',
                    'script_appels.update' => 'Modifier',
                    'script_appels.delete' => 'Supprimer',
                ],
            ],
            'statut_phonings' => [
                'label' => 'AOPIA - Statuts phoning',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'statut_phonings.view_any' => 'Lister',
                    'statut_phonings.view' => 'Voir',
                    'statut_phonings.create' => 'Créer',
                    'statut_phonings.update' => 'Modifier',
                    'statut_phonings.delete' => 'Supprimer',
                ],
            ],
            'tickets' => [
                'label' => 'AlloPro - Tickets',
                'panel' => 'allopro',
                'permissions' => [
                    'tickets.create' => 'Créer',
                    'tickets.view' => 'Voir',
                    'tickets.update_statut' => 'Modifier le statut',
                ],
            ],
            'fiche_p2' => [
                'label' => 'AlloPro - Fiche P2',
                'panel' => 'allopro',
                'permissions' => [
                    'fiche_p2.create' => 'Créer',
                    'fiche_p2.view' => 'Voir',
                    'fiche_p2.update' => 'Modifier',
                ],
            ],
            'artisans' => [
                'label' => 'AlloPro - Artisans',
                'panel' => 'allopro',
                'permissions' => [
                    'artisans.view' => 'Voir',
                    'artisans.update' => 'Modifier',
                ],
            ],
            'reclamations' => [
                'label' => 'AlloPro - Réclamations',
                'panel' => 'allopro',
                'permissions' => [
                    'reclamations.view' => 'Voir',
                    'reclamations.create' => 'Créer',
                    'reclamations.valider' => 'Valider',
                ],
            ],
            'rapports_satisfaction' => [
                'label' => 'AlloPro - Satisfaction',
                'panel' => 'allopro',
                'permissions' => [
                    'rapports_satisfaction.create' => 'Créer',
                ],
            ],
            'prospection_artisans' => [
                'label' => 'AlloPro - Prospection artisans',
                'panel' => 'allopro',
                'permissions' => [
                    'prospection_artisans.create' => 'Créer',
                    'prospection_artisans.update' => 'Modifier',
                ],
            ],
            'dashboard' => [
                'label' => 'AlloPro - Temps réel',
                'panel' => 'allopro',
                'permissions' => [
                    'dashboard.temps_reel' => 'Voir le tableau de bord temps réel',
                ],
            ],
        ];

        return $modules + static::dynamicTableModules($modules);
    }

    /**
     * @return array<string, string>
     */
    public static function fieldActions(): array
    {
        return [
            'show' => 'Voir',
            'create' => 'Créer',
            'edit' => 'Modifier',
            'flux' => 'Flux',
            'all' => 'Tout',
        ];
    }

    /**
     * Source de vérité des droits par champ exposés en super administration.
     *
     * @return array<string, array{label: string, panel: string, fields: array<string, string>}>
     */
    public static function fieldModules(): array
    {
        $modules = [
            'prospects' => [
                'label' => 'AOPIA - Prospects',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(Prospect::class, [
                    'nom' => 'Nom',
                    'type_pressenti' => 'Type pressenti',
                    'telephone' => 'Téléphone',
                    'telephone_alt' => 'Téléphone alternatif',
                    'email' => 'Email',
                    'adresse' => 'Adresse',
                    'code_postal' => 'Code postal',
                    'ville' => 'Ville',
                    'siret' => 'SIRET',
                    'statut' => 'Statut',
                    'teleprospecteur_id' => 'Téléprospecteur',
                    'commercial_id' => 'Commercial',
                    'interlocuteur_nom' => 'Interlocuteur',
                    'interlocuteur_email' => 'Email interlocuteur',
                    'description' => 'Description',
                ]),
            ],
            'partenaires' => [
                'label' => 'AOPIA - Partenaires',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(Partenaire::class, [
                    'nom' => 'Nom',
                    'entreprise' => 'Entreprise',
                    'nom_retenu' => 'Nom retenu',
                    'siret' => 'SIRET',
                    'type' => 'Type',
                    'telephone' => 'Téléphone',
                    'email' => 'Email',
                    'adresse' => 'Adresse',
                    'code_postal' => 'Code postal',
                    'ville' => 'Ville',
                    'statut' => 'Statut',
                    'commercial_id' => 'Commercial',
                    'conseiller_id' => 'Conseiller',
                    'date_signature' => 'Date signature',
                    'date_convention' => 'Date convention',
                    'notes' => 'Notes internes',
                    'commentaires' => 'Commentaires',
                ]),
            ],
            'clients' => [
                'label' => 'AOPIA - Clients',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(Client::class, [
                    'ref_client' => 'Référence client',
                    'civilite' => 'Civilité',
                    'nom_tiers' => 'Nom',
                    'email' => 'Email',
                    'telephone' => 'Téléphone',
                    'adresse' => 'Adresse',
                    'code_postal' => 'Code postal',
                    'ville' => 'Ville',
                    'date_naissance' => 'Date de naissance',
                    'entreprise' => 'Entreprise',
                    'etat' => 'État',
                    'montant_cpf' => 'Montant CPF',
                    'ne_plus_contacter' => 'Ne plus contacter',
                    'partenaire_id' => 'Partenaire',
                    'notes_commerciales' => 'Notes commerciales',
                ]),
            ],
            'opportunites' => [
                'label' => 'AOPIA - Opportunités',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(Opportunite::class, [
                    'nom_entite' => 'Nom entité',
                    'type_pressenti' => 'Type pressenti',
                    'departement' => 'Département',
                    'telephone' => 'Téléphone',
                    'email' => 'Email',
                    'adresse' => 'Adresse',
                    'siret' => 'SIRET',
                    'secteur_activite' => 'Secteur activité',
                    'nb_salaries' => 'Nombre salariés',
                    'chiffre_affaires' => 'Chiffre affaires',
                    'source_detection' => 'Source détection',
                    'details_source' => 'Détails source',
                    'potentiel' => 'Potentiel',
                    'statut' => 'Statut',
                    'interlocuteur_nom' => 'Interlocuteur',
                    'interlocuteur_email' => 'Email interlocuteur',
                    'assigne_a' => 'Assigné à',
                    'raison_perte' => 'Raison perte',
                    'converti_en_prospect_id' => 'Prospect converti',
                ]),
            ],
            'rendez_vous' => [
                'label' => 'AOPIA - Rendez-vous',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(RendezVous::class, [
                    'rdvable_type' => 'Type d\'entité liée',
                    'rdvable_id' => 'Entité liée',
                    'commercial_id' => 'Commercial',
                    'teleprospecteur_id' => 'Téléprospecteur',
                    'type' => 'Type',
                    'statut' => 'Statut',
                    'date_heure' => 'Date et heure',
                    'lieu' => 'Lieu',
                    'adresse_lieu' => 'Adresse lieu',
                    'interlocuteur_nom' => 'Interlocuteur',
                    'interlocuteur_tel' => 'Téléphone interlocuteur',
                    'interlocuteur_email' => 'Email interlocuteur',
                    'pdf_recap' => 'PDF récap',
                    'enregistrement_audio' => 'Audio',
                    'email_confirmation_envoye' => 'Email confirmation',
                    'email_invitation_envoye' => 'Email invitation',
                    'outlook_event_id' => 'Outlook event',
                    'google_event_id' => 'Google event',
                ]),
            ],
            'entreprises' => [
                'label' => 'AOPIA - Entreprises',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(Entreprise::class, [
                    'raison_sociale' => 'Raison sociale',
                    'siret' => 'SIRET',
                    'siren' => 'SIREN',
                    'numero_tva' => 'Numéro TVA',
                    'forme_juridique' => 'Forme juridique',
                    'capital' => 'Capital',
                    'adresse' => 'Adresse',
                    'code_postal' => 'Code postal',
                    'ville' => 'Ville',
                    'pays' => 'Pays',
                    'telephone' => 'Téléphone',
                    'email' => 'Email',
                    'site_web' => 'Site web',
                    'secteur_activite' => 'Secteur activité',
                    'effectif' => 'Effectif',
                    'code_naf' => 'Code NAF',
                    'date_creation' => 'Date création',
                    'description' => 'Description',
                ]),
            ],
            'campagne_phonings' => [
                'label' => 'AOPIA - Campagnes phoning',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(CampagnePhoning::class, [
                    'nom' => 'Nom',
                    'description' => 'Description',
                    'statut' => 'Statut',
                    'type_entite' => 'Cible',
                    'criteres' => 'Critères',
                    'date_debut' => 'Date début',
                    'date_fin' => 'Date fin',
                    'user_id' => 'Assigné à',
                    'entite_id' => 'Entité commerciale',
                ]),
            ],
            'dossier_formations' => [
                'label' => 'AOPIA - Dossiers formation',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(DossierFormation::class, [
                    'ref_client' => 'Référence client',
                    'intitule_programme' => 'Programme',
                    'entite_id' => 'Entité commerciale',
                    'personne_id' => 'Client',
                    'montant_ht' => 'Montant HT',
                    'montant_cpf' => 'Montant CPF',
                    'date_vente' => 'Date vente',
                    'statut_formation' => 'Statut formation',
                    'no_dossier_edof' => 'No dossier EDOF',
                    'etat' => 'État',
                    'consultant_accueil_id' => 'Consultant accueil',
                    'consultant_formateur_id' => 'Consultant formateur',
                ]),
            ],
            'script_appels' => [
                'label' => 'AOPIA - Scripts appel',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(ScriptAppel::class, [
                    'titre' => 'Titre',
                    'slug' => 'Slug',
                    'type_contact' => 'Type contact',
                    'campagne_id' => 'Campagne',
                    'onglet' => 'Onglet',
                    'contenu' => 'Contenu',
                    'conseil' => 'Conseil',
                    'variables_disponibles' => 'Variables disponibles',
                    'objections' => 'Objections',
                    'kpis' => 'KPIs',
                    'actif' => 'Actif',
                    'ordre' => 'Ordre',
                ]),
            ],
            'statut_phonings' => [
                'label' => 'AOPIA - Statuts phoning',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(StatutPhoning::class, [
                    'model_type' => 'Type modèle',
                    'groupe' => 'Groupe',
                    'groupe_label' => 'Libellé groupe',
                    'code' => 'Code',
                    'label' => 'Libellé',
                    'description' => 'Description',
                    'action_immediate' => 'Action immédiate',
                    'note_obligatoire' => 'Note obligatoire',
                    'message_note_obligatoire' => 'Message note obligatoire',
                    'delai_rappel_jours' => 'Délai rappel jours',
                    'prioritaire' => 'Prioritaire',
                    'fiche_type' => 'Fiche type',
                    'retire_de_file' => 'Retiré de file',
                    'pipeline_statut' => 'Pipeline statut',
                    'compte_comme_tentative' => 'Compte comme tentative',
                    'couleur' => 'Couleur',
                    'icone' => 'Icône',
                    'ordre' => 'Ordre',
                    'actif' => 'Actif',
                ]),
            ],
            'tickets' => [
                'label' => 'AlloPro - Tickets',
                'panel' => 'allopro',
                'fields' => static::fieldLabelsForModel(Ticket::class, [
                    'reference' => 'Référence',
                    'contact_particulier_id' => 'Contact particulier',
                    'artisan_id' => 'Artisan',
                    'operateur_id' => 'Opérateur',
                    'statut' => 'Statut',
                    'niveau_priorite' => 'Niveau priorité',
                    'corps_de_metier' => 'Corps de métier',
                    'rdv_planifie_at' => 'RDV planifié',
                    'rappel_promise_at' => 'Rappel promis',
                    'ringover_call_id' => 'Ringover call ID',
                    'source_appel' => 'Source appel',
                    'notes' => 'Notes',
                ]),
            ],
        ];

        return $modules + static::dynamicFieldModules($modules);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedPermissionOptions(): array
    {
        return collect(static::modules())
            ->mapWithKeys(fn (array $module) => [$module['label'] => $module['permissions']])
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public static function permissionOptions(): array
    {
        $options = [];

        foreach (static::modules() as $module) {
            foreach ($module['permissions'] as $permission => $label) {
                $options[$permission] = "{$module['label']} - {$label}";
            }
        }

        return $options;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedFieldPermissionOptions(): array
    {
        return collect(static::fieldModules())
            ->mapWithKeys(function (array $module, string $entity) {
                $options = [];

                foreach ($module['fields'] as $field => $fieldLabel) {
                    foreach (static::fieldActions() as $action => $actionLabel) {
                        $options[static::fieldPermissionName($entity, $field, $action)] = "{$fieldLabel} - {$actionLabel}";
                    }
                }

                return [$module['label'] => $options];
            })
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public static function fieldPermissionOptions(): array
    {
        $options = [];

        foreach (static::fieldModules() as $entity => $module) {
            foreach ($module['fields'] as $field => $fieldLabel) {
                foreach (static::fieldActions() as $action => $actionLabel) {
                    $permission = static::fieldPermissionName($entity, $field, $action);
                    $options[$permission] = "{$module['label']} - {$fieldLabel} - {$actionLabel}";
                }
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function permissionDescriptions(): array
    {
        $descriptions = [];

        foreach (static::modules() as $module) {
            foreach (array_keys($module['permissions']) as $permission) {
                $descriptions[$permission] = $module['label'];
            }
        }

        return $descriptions;
    }

    /**
     * @return array<string, string>
     */
    public static function fieldPermissionDescriptions(): array
    {
        $descriptions = [];

        foreach (static::fieldModules() as $entity => $module) {
            foreach ($module['fields'] as $field => $fieldLabel) {
                foreach (array_keys(static::fieldActions()) as $action) {
                    $descriptions[static::fieldPermissionName($entity, $field, $action)] = "{$module['label']} / {$fieldLabel}";
                }
            }
        }

        return $descriptions;
    }

    /**
     * @return array<int, string>
     */
    public static function modulePermissionNames(): array
    {
        return collect(static::modules())
            ->flatMap(fn (array $module) => array_keys($module['permissions']))
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public static function allFieldPermissionNames(): array
    {
        return collect(static::fieldPermissionOptions())
            ->keys()
            ->values()
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public static function allPermissionNames(): array
    {
        return collect(static::modulePermissionNames())
            ->merge(static::allFieldPermissionNames())
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNamesForPanel(string $panel): array
    {
        $modulePermissions = collect(static::modules())
            ->filter(fn (array $module) => $module['panel'] === $panel)
            ->flatMap(fn (array $module) => array_keys($module['permissions']))
            ->values();

        $fieldPermissions = collect(static::fieldModules())
            ->filter(fn (array $module) => $module['panel'] === $panel)
            ->flatMap(function (array $module, string $entity) {
                $permissions = [];

                foreach (array_keys($module['fields']) as $field) {
                    foreach (array_keys(static::fieldActions()) as $action) {
                        $permissions[] = static::fieldPermissionName($entity, $field, $action);
                    }
                }

                return $permissions;
            })
            ->values();

        return $modulePermissions
            ->merge($fieldPermissions)
            ->unique()
            ->values()
            ->toArray();
    }

    public static function hasPermission(string $permission): bool
    {
        return in_array($permission, static::allPermissionNames(), true);
    }

    public static function ensurePermissionsExist(): void
    {
        foreach (static::allPermissionNames() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    public static function syncFullAccess(Role $role): void
    {
        static::ensurePermissionsExist();
        $role->syncPermissions(static::allPermissionNames());
        static::$userPermissionCache = [];
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public static function syncSelectiveAccess(Role $role, array $permissions): void
    {
        static::ensurePermissionsExist();

        $role->syncPermissions(
            collect($permissions)
                ->intersect(static::allPermissionNames())
                ->values()
                ->all()
        );

        static::$userPermissionCache = [];
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function roleHasFullAccess(?Role $role): bool
    {
        if (! $role) {
            return false;
        }

        $catalogPermissions = static::allPermissionNames();

        if ($catalogPermissions === []) {
            return false;
        }

        $rolePermissions = $role->permissions->pluck('name')->all();

        return collect($catalogPermissions)->diff($rolePermissions)->isEmpty();
    }

    /**
     * @return array<int, string>
     */
    public static function roleModulePermissionNames(?Role $role): array
    {
        return static::rolePermissionNames($role, static::modulePermissionNames());
    }

    /**
     * @return array<int, string>
     */
    public static function roleFieldPermissionNames(?Role $role): array
    {
        return static::rolePermissionNames($role, static::allFieldPermissionNames());
    }

    public static function userCan(?Authenticatable $user, string $permission): bool
    {
        if (! $user instanceof User || ! $user->actif) {
            return false;
        }

        try {
            return $user->can($permission);
        } catch (Throwable) {
            return false;
        }
    }

    public static function fieldPermissionName(string $entity, string $field, string $action): string
    {
        return 'fields.'.Str::snake($entity).'.'.Str::snake($field).'.'.static::normalizeFieldAction($action);
    }

    public static function userCanField(?Authenticatable $user, string $entity, string $field, string $action): bool
    {
        $action = static::normalizeFieldAction($action);

        if (! static::hasFieldDefinition($entity, $field)) {
            return true;
        }

        if (! static::userHasAnyFieldPermission($user, $entity)) {
            return true;
        }

        $permissions = static::userPermissionNames($user);

        return in_array(static::fieldPermissionName($entity, $field, 'all'), $permissions, true)
            || in_array(static::fieldPermissionName($entity, $field, $action), $permissions, true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function filterFieldDataForUser(?Authenticatable $user, string $entity, array $data, string $action): array
    {
        if (! static::userHasAnyFieldPermission($user, $entity)) {
            return $data;
        }

        foreach (array_keys($data) as $field) {
            if (static::hasFieldDefinition($entity, $field) && ! static::userCanField($user, $entity, $field, $action)) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    private static function userHasAnyFieldPermission(?Authenticatable $user, string $entity): bool
    {
        $prefix = 'fields.'.Str::snake($entity).'.';

        foreach (static::userPermissionNames($user) as $permission) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $allowedPermissions
     * @return array<int, string>
     */
    private static function rolePermissionNames(?Role $role, array $allowedPermissions): array
    {
        if (! $role) {
            return [];
        }

        return $role->permissions
            ->pluck('name')
            ->intersect($allowedPermissions)
            ->values()
            ->all();
    }

    public static function hasFieldDefinition(string $entity, string $field): bool
    {
        $entity = Str::snake($entity);
        $field = Str::snake($field);

        return isset(static::fieldModules()[$entity]['fields'][$field]);
    }

    private static function normalizeFieldAction(string $action): string
    {
        return match ($action) {
            'view' => 'show',
            'update' => 'edit',
            default => $action,
        };
    }

    /**
     * @return array<int, string>
     */
    private static function userPermissionNames(?Authenticatable $user): array
    {
        if (! $user instanceof User || ! $user->actif || ! method_exists($user, 'getAllPermissions')) {
            return [];
        }

        $cacheKey = spl_object_id($user);

        if (! array_key_exists($cacheKey, static::$userPermissionCache)) {
            try {
                static::$userPermissionCache[$cacheKey] = $user->getAllPermissions()->pluck('name')->all();
            } catch (Throwable) {
                static::$userPermissionCache[$cacheKey] = [];
            }
        }

        return static::$userPermissionCache[$cacheKey];
    }

    /**
     * @param  array<string, array{label: string, panel: string, permissions: array<string, string>}>  $existingModules
     * @return array<string, array{label: string, panel: string, permissions: array<string, string>}>
     */
    private static function dynamicTableModules(array $existingModules): array
    {
        $modules = [];

        foreach (static::modelClasses() as $modelClass) {
            $model = new $modelClass();
            $table = $model->getTable();
            $key = Str::snake($table);

            if (isset($existingModules[$key]) || $key === '') {
                continue;
            }

            $modules[$key] = [
                'label' => static::dynamicTableLabel($modelClass, $table),
                'panel' => static::dynamicPanelForTable($table),
                'permissions' => static::defaultTablePermissions($key),
            ];
        }

        ksort($modules);

        return $modules;
    }

    /**
     * @param  array<string, array{label: string, panel: string, fields: array<string, string>}>  $existingModules
     * @return array<string, array{label: string, panel: string, fields: array<string, string>}>
     */
    private static function dynamicFieldModules(array $existingModules): array
    {
        $modules = [];

        foreach (static::modelClasses() as $modelClass) {
            $model = new $modelClass();
            $table = $model->getTable();
            $key = Str::snake($table);

            if (isset($existingModules[$key]) || $key === '') {
                continue;
            }

            $fields = static::fieldLabelsForDynamicModel($modelClass);

            if ($fields === []) {
                continue;
            }

            $modules[$key] = [
                'label' => static::dynamicTableLabel($modelClass, $table),
                'panel' => static::dynamicPanelForTable($table),
                'fields' => $fields,
            ];
        }

        ksort($modules);

        return $modules;
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private static function modelClasses(): array
    {
        return collect(File::files(app_path('Models')))
            ->map(fn ($file): string => 'App\\Models\\'.$file->getFilenameWithoutExtension())
            ->filter(fn (string $class): bool => class_exists($class))
            ->filter(function (string $class): bool {
                try {
                    $reflection = new ReflectionClass($class);

                    return $reflection->isInstantiable()
                        && $reflection->isSubclassOf(Model::class);
                } catch (Throwable) {
                    return false;
                }
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function defaultTablePermissions(string $key): array
    {
        return [
            "{$key}.view_any" => 'Lister',
            "{$key}.view" => 'Voir',
            "{$key}.create" => 'Créer',
            "{$key}.update" => 'Modifier',
            "{$key}.delete" => 'Supprimer',
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function dynamicTableLabel(string $modelClass, string $table): string
    {
        return 'Table - '.Str::headline(str_replace('_', ' ', $table));
    }

    private static function dynamicPanelForTable(string $table): string
    {
        $table = Str::snake($table);

        if (Str::contains($table, [
            'artisan',
            'contact_particulier',
            'fiche_p2',
            'reclamation',
            'rapport_satisfaction',
            'ticket',
        ])) {
            return 'allopro';
        }

        if (Str::contains($table, [
            'api_key',
            'crm_',
            'env_',
            'field_',
            'profile',
            'setting',
            'theme',
            'user',
            'view',
            'webhook',
            'workflow',
        ])) {
            return 'super-admin';
        }

        return 'ns-conseil';
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    private static function fieldLabelsForDynamicModel(string $modelClass): array
    {
        $model = new $modelClass();
        $fields = $model->getFillable();

        if ($fields === []) {
            try {
                if (Schema::hasTable($model->getTable())) {
                    $fields = Schema::getColumnListing($model->getTable());
                }
            } catch (Throwable) {
                $fields = [];
            }
        }

        return collect($fields)
            ->reject(fn (string $field): bool => in_array($field, static::ignoredDynamicFields(), true))
            ->mapWithKeys(fn (string $field): array => [$field => static::defaultFieldLabel($field)])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function ignoredDynamicFields(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
            'password',
            'two_factor_recovery_codes',
            'two_factor_secret',
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private static function fieldLabelsForModel(string $modelClass, array $overrides = []): array
    {
        $model = new $modelClass();
        $labels = [];

        foreach ($model->getFillable() as $field) {
            $labels[$field] = $overrides[$field] ?? static::defaultFieldLabel($field);
        }

        return $labels;
    }

    private static function defaultFieldLabel(string $field): string
    {
        return Str::headline(str_replace('_', ' ', $field));
    }
}
