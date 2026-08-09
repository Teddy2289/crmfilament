<?php

namespace App\Services\Email;

use App\Models\EmailConfiguration;
use Illuminate\Support\Collection;

interface MailboxSwitcherServiceInterface
{
    /**
     * Retourne la liste des EmailConfiguration disponibles pour l'utilisateur,
     * triée : personnelles en premier, globales ensuite.
     *
     * @param  int  $userId
     * @return Collection<int, EmailConfiguration>
     */
    public function getAvailableMailboxes(int $userId): Collection;

    /**
     * Résout l'Active_Mailbox pour l'utilisateur :
     * 1. Session active_mailbox_id si valide et accessible
     * 2. Fallback sur la première config disponible
     * 3. null si aucune config disponible
     *
     * @param  int  $userId
     * @return EmailConfiguration|null
     */
    public function resolveActiveMailbox(int $userId): ?EmailConfiguration;

    /**
     * Enregistre le choix en session.
     *
     * @param  int  $configId
     * @return void
     */
    public function switchMailbox(int $configId): void;

    /**
     * Construit le label d'affichage pour une configuration email.
     * Format : "{from_name} <{email}>" si from_name renseigné, sinon "{email}".
     * Ajoute l'indicateur 🌐 si la config est globale.
     *
     * @param  EmailConfiguration  $config
     * @return string
     */
    public function buildOptionLabel(EmailConfiguration $config): string;
}

class MailboxSwitcherService implements MailboxSwitcherServiceInterface
{
    /**
     * Retourne la liste des EmailConfiguration disponibles pour l'utilisateur,
     * triées : personnelles (is_global = false) en premier, globales ensuite.
     *
     * @param  int  $userId
     * @return Collection<int, EmailConfiguration>
     */
    public function getAvailableMailboxes(int $userId): Collection
    {
        $configs = EmailConfiguration::forUser($userId)
            ->active()
            ->get();

        return $this->sortMailboxes($configs);
    }

    /**
     * Trie les configurations : personnelles en premier, globales ensuite.
     * Méthode publique pour faciliter les tests.
     *
     * @param  Collection<int, EmailConfiguration>  $configs
     * @return Collection<int, EmailConfiguration>
     */
    public function sortMailboxes(Collection $configs): Collection
    {
        return $configs->sortBy(function (EmailConfiguration $config) {
            // Les configs personnelles (is_global = false) ont priorité 0
            // Les configs globales (is_global = true) ont priorité 1
            return $config->is_global ? 1 : 0;
        })->values();
    }

    /**
     * Résout l'Active_Mailbox pour l'utilisateur.
     * Priorité :
     * 1. Session active_mailbox_id si la config est encore active et accessible
     * 2. Fallback sur la première config disponible selon l'ordre de tri
     * 3. null si aucune config disponible
     *
     * Met à jour la session avec l'ID résolu en cas de fallback.
     *
     * @param  int  $userId
     * @return EmailConfiguration|null
     */
    public function resolveActiveMailbox(int $userId): ?EmailConfiguration
    {
        $availableMailboxes = $this->getAvailableMailboxes($userId);

        if ($availableMailboxes->isEmpty()) {
            return null;
        }

        // 1. Chercher en session
        $sessionId = session('active_mailbox_id');

        if ($sessionId !== null) {
            $sessionConfig = $availableMailboxes->firstWhere('id', (int) $sessionId);

            if ($sessionConfig !== null) {
                return $sessionConfig;
            }
        }

        // 2. Fallback : première config disponible selon l'ordre de tri
        $fallback = $availableMailboxes->first();

        // Mettre à jour la session avec le fallback résolu
        if ($fallback !== null) {
            $this->switchMailbox($fallback->id);
        }

        return $fallback;
    }

    /**
     * Enregistre le choix de boîte mail en session.
     *
     * @param  int  $configId
     * @return void
     */
    public function switchMailbox(int $configId): void
    {
        session(['active_mailbox_id' => $configId]);
    }

    /**
     * Construit le label d'affichage pour une configuration email.
     *
     * Format :
     * - "{from_name} <{email}>" si from_name est renseigné
     * - "{email}" sinon
     * - Suffixe " 🌐" si la configuration est globale (is_global = true)
     *
     * @param  EmailConfiguration  $config
     * @return string
     */
    public function buildOptionLabel(EmailConfiguration $config): string
    {
        if (! empty($config->from_name)) {
            $label = "{$config->from_name} <{$config->email}>";
        } else {
            $label = $config->email;
        }

        if ($config->is_global) {
            $label .= ' 🌐';
        }

        return $label;
    }
}
