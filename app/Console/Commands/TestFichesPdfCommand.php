<?php

namespace App\Console\Commands;

use App\Mail\TestFichesPdfMail;
use App\Models\Prospect;
use App\Services\Phoning\FichePdfGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TestFichesPdfCommand extends Command
{
    protected $signature = 'test:fiches-pdf {email}';
    protected $description = 'Génère et envoie les 3 fiches PDF par email pour test';

    public function handle(FichePdfGenerationService $pdfService): int
    {
        $email = $this->argument('email');
        
        $this->info("Génération des fiches PDF pour {$email}...");

        $prospect = Prospect::first();
        if (! $prospect) {
            $this->error('Aucun prospect trouvé dans la base de données');
            return self::FAILURE;
        }

        try {
            // Générer les 3 fiches
            $this->info('Génération de la fiche bleue...');
            $dataBleue = $pdfService->preparerDonneesFicheBleue($prospect);
            $filenameBleue = $pdfService->genererNomFichier('bleue', $prospect);
            $urlBleue = $pdfService->generer('bleue', $dataBleue, $filenameBleue);

            $this->info('Génération de la fiche jaune...');
            $dataJaune = $pdfService->preparerDonneesFicheJaune($prospect);
            $filenameJaune = $pdfService->genererNomFichier('jaune', $prospect);
            $urlJaune = $pdfService->generer('jaune', $dataJaune, $filenameJaune);

            $this->info('Génération de la fiche verte...');
            $dataVerte = $pdfService->preparerDonneesFicheVerte($prospect);
            $filenameVerte = $pdfService->genererNomFichier('verte', $prospect);
            $urlVerte = $pdfService->generer('verte', $dataVerte, $filenameVerte);

            // Convertir les URLs en chemins locaux pour les pièces jointes
            $localBase = storage_path('app/public');
            $bleuePath = str_replace('https://manage.ns-conseil.com/storage/', $localBase . '/', $urlBleue);
            $jaunePath = str_replace('https://manage.ns-conseil.com/storage/', $localBase . '/', $urlJaune);
            $vertePath = str_replace('https://manage.ns-conseil.com/storage/', $localBase . '/', $urlVerte);

            $this->info('Envoi de l\'email...');
            Mail::to($email)->send(new TestFichesPdfMail($bleuePath, $jaunePath, $vertePath));

            $this->info('✅ Email envoyé avec succès !');
            $this->table(['Fiche', 'Nom du fichier', 'URL'], [
                ['Bleue', $filenameBleue, $urlBleue],
                ['Jaune', $filenameJaune, $urlJaune],
                ['Verte', $filenameVerte, $urlVerte],
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Erreur : {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}