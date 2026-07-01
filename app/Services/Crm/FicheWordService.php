<?php

namespace App\Services\Crm;

use App\Models\Appel;
use App\Models\TemplateFiche;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;

class FicheWordService
{
    public function __construct()
    {
        $tempDir = storage_path('app/temp');

        // Le dossier temp doit exister physiquement AVANT que PhpWord
        // n'essaie d'y écrire, sinon on obtient un "mkdir(): No such
        // file or directory" silencieux dans le worker.
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        Settings::setTempDir($tempDir);
    }

    /**
     * Génère un document Word depuis un template et des données.
     */
    public function generer(TemplateFiche $template, array $data): string
    {
        // Depuis Laravel 11, le disque 'local' pointe vers storage/app/private.
        // On passe par Storage::disk() plutôt que storage_path() brut pour
        // que le chemin résolu corresponde à l'emplacement réel des fichiers.
        $templatePath = Storage::disk('local')->path($template->fichier_path);

        if (! file_exists($templatePath)) {
            throw new \Exception("Template introuvable : {$templatePath}");
        }

        $phpWord = $this->chargerTemplate($templatePath);
        $this->remplacerVariables($phpWord, $data);

        $filename = "fiche-{$template->type}-".now()->format('Ymd-His').'.docx';
        $outputDir = storage_path('app/temp');

        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $outputPath = $outputDir.DIRECTORY_SEPARATOR.$filename;

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

        if (! file_exists($outputPath)) {
            throw new \Exception("Échec de l'écriture du document généré : {$outputPath}");
        }

        return $outputPath;
    }

    /**
     * Génère une fiche pour un appel selon son type.
     */
    public function genererPourAppel(Appel $appel): ?string
    {
        if (! $appel->fiche_type || ! $appel->fiche_data) {
            return null;
        }

        $template = TemplateFiche::actifs()
            ->parType($appel->fiche_type)
            ->first();

        if (! $template) {
            return null;
        }

        return $this->generer($template, $appel->fiche_data);
    }

    /**
     * Charge un template Word.
     */
    protected function chargerTemplate(string $path): PhpWord
    {
        return IOFactory::load($path);
    }

    /**
     * Remplace les variables dans le document.
     * Format attendu : {{variable}}
     */
    protected function remplacerVariables(PhpWord $phpWord, array $data): void
    {
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text = $element->getText();
                    $text = $this->remplacerDansTexte($text, $data);
                    $element->setText($text);
                }

                if (method_exists($element, 'getElements')) {
                    $this->remplacerDansElements($element->getElements(), $data);
                }
            }
        }
    }

    /**
     * Remplace les variables dans un tableau d'éléments.
     */
    protected function remplacerDansElements(array $elements, array $data): void
    {
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $text = $element->getText();
                $text = $this->remplacerDansTexte($text, $data);
                if (method_exists($element, 'setText')) {
                    $element->setText($text);
                }
            }

            if (method_exists($element, 'getElements')) {
                $this->remplacerDansElements($element->getElements(), $data);
            }
        }
    }

    /**
     * Remplace les variables dans une chaîne de caractères.
     */
    protected function remplacerDansTexte(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $text = str_replace($placeholder, is_scalar($value) ? (string) $value : '', $text);
        }

        return $text;
    }

    /**
     * Stocke le fichier généré dans le storage.
     */
    public function stocker(string $localPath, string $destination): string
    {
        if (! file_exists($localPath)) {
            throw new \Exception("Fichier local introuvable pour stockage : {$localPath}");
        }

        $filename = basename($localPath);

        // putFileAs() attend un objet File|UploadedFile, pas une string brute
        // (une string est traitée comme le CONTENU du fichier, pas un chemin).
        Storage::disk('public')->putFileAs(
            'fiches/'.$destination,
            new \Illuminate\Http\File($localPath),
            $filename
        );

        // Nettoyage du fichier temporaire une fois copié vers le disque public
        File::delete($localPath);

        return Storage::disk('public')->url('fiches/'.$destination.'/'.$filename);
    }
}