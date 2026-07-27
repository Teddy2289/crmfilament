<?php

namespace App\Console\Commands;

use App\Models\Appel;
use App\Models\Prospect;
use Illuminate\Console\Command;

/**
 * Détecte les prospects dont teleprospecteur_id a été rempli avant tout appel
 * réel — normalement ce champ n'est fixé que par PhoningWorkflow::updateProspect()
 * via Prospect::assignerTeleprospecteur(), après un appel effectif. Le seul
 * autre endroit qui pouvait le renseigner était un default() erroné sur le
 * formulaire Filament (ProspectResource), retiré depuis : cette commande sert
 * à vérifier qu'aucune fiche ne retombe dans cet état, et à corriger si besoin.
 */
class AuditTeleprospecteurAssignment extends Command
{
    protected $signature = 'crm:audit-teleprospecteur
        {--fix : Corrige automatiquement les incohérences trouvées (sinon simple rapport)}';

    protected $description = "Vérifie que teleprospecteur_id n'est jamais rempli avant un appel réel";

    public function handle(): int
    {
        $suspects = Prospect::whereNotNull('teleprospecteur_id')
            ->whereColumn('teleprospecteur_id', 'commercial_id')
            ->with(['teleprospecteur', 'commercial'])
            ->get();

        if ($suspects->isEmpty()) {
            $this->info('Aucune incohérence : rien à vérifier.');
            return self::SUCCESS;
        }

        $fix = (bool) $this->option('fix');
        $lignes = [];
        $corriges = 0;

        foreach ($suspects as $prospect) {
            $nbAppels = Appel::where('appelable_type', Prospect::class)
                ->where('appelable_id', $prospect->id)
                ->count();

            $incoherent = $nbAppels === 0 && is_null($prospect->date_premier_contact);

            $statutLigne = $incoherent ? 'À corriger' : 'OK (appel réel existant)';

            if ($incoherent && $fix) {
                $prospect->update(['teleprospecteur_id' => null]);
                $statutLigne = 'Corrigé';
                $corriges++;
            }

            $lignes[] = [
                $prospect->id,
                $prospect->nom,
                $prospect->teleprospecteur?->nom ?? '—',
                $nbAppels,
                $prospect->date_premier_contact?->format('d/m/Y') ?? 'Jamais',
                $statutLigne,
            ];
        }

        $this->table(['ID', 'Nom', 'Téléprospecteur', 'Appels réels', '1er contact', 'Statut'], $lignes);

        if (! $fix) {
            $this->newLine();
            $this->comment("{$suspects->count()} fiche(s) avec teleprospecteur_id = commercial_id détectée(s). Relancez avec --fix pour corriger celles marquées « À corriger ».");
        } else {
            $this->newLine();
            $this->info("{$corriges} fiche(s) corrigée(s).");
        }

        return self::SUCCESS;
    }
}
