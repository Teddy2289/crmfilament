<?php

namespace App\Filament\Allopro\Pages;

use App\Models\AffaireIntervention;
use App\Models\Artisan;
use App\Models\BonDeCommande;
use App\Models\ContactPartenaire;
use App\Models\ContactParticulier;
use App\Models\Devis;
use App\Models\Facture;
use App\Models\ReclamationP8;
use App\Models\RapportSatisfactionP6;
use App\Models\Ticket;
use Filament\Pages\Page;
use Throwable;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static string $view = 'filament.allopro.pages.dashboard';

    protected static ?int $navigationSort = -2;

    public function getStats(): array
    {
        $ticketKpis = $this->safeArray(fn () => Ticket::getKpis());
        $devisKpis = $this->safeArray(fn () => Devis::getKpis());
        $bonDeCommandeKpis = $this->safeArray(fn () => BonDeCommande::getKpis());
        $factureKpis = $this->safeArray(fn () => Facture::getKpis());

        $contactsPartenaires = $this->safeCount(ContactPartenaire::class);
        $contactsParticuliers = $this->safeCount(ContactParticulier::class);

        return [
            'tickets' => $this->safeCount(Ticket::class),
            'tickets_actifs' => (int) ($ticketKpis['actifs'] ?? 0),
            'tickets_urgents' => (int) ($ticketKpis['urgents'] ?? 0),
            'tickets_retard' => (int) ($ticketKpis['en_retard'] ?? 0),
            'artisans' => $this->safeCount(Artisan::class),
            'contacts' => $contactsPartenaires + $contactsParticuliers,
            'contacts_partenaires' => $contactsPartenaires,
            'contacts_particuliers' => $contactsParticuliers,
            'devis' => $this->safeCount(Devis::class),
            'devis_attente' => (int) ($devisKpis['en_attente'] ?? 0),
            'devis_relancer' => (int) ($devisKpis['a_relancer'] ?? 0),
            'bons_de_commande' => $this->safeCount(BonDeCommande::class),
            'bons_de_commande_actifs' => (int) ($bonDeCommandeKpis['total_actifs'] ?? 0),
            'interventions' => $this->safeCount(AffaireIntervention::class),
            'factures' => $this->safeCount(Facture::class),
            'factures_attente' => (int) ($factureKpis['en_attente_paiement'] ?? 0),
            'factures_retard' => (int) ($factureKpis['en_retard'] ?? 0),
            'reclamations' => $this->safeCount(ReclamationP8::class),
            'satisfactions' => $this->safeCount(RapportSatisfactionP6::class),
        ];
    }

    private function safeCount(string $model): int
    {
        return $this->safeNumber(fn () => $model::query()->count());
    }

    private function safeNumber(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (Throwable) {
            return 0;
        }
    }

    private function safeArray(callable $callback): array
    {
        try {
            return (array) $callback();
        } catch (Throwable) {
            return [];
        }
    }
}
