<?php

namespace App\Services\Crm;

use App\Models\Appel;
use App\Models\TemplateFiche;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;

class FicheWordService
{
    public function __construct()
    {
        Settings::setTempDir(storage_path('app/temp'));
    }

    /**
     * Génère un document Word depuis un template et des données.
     */
    public function generer(TemplateFiche $template, array $data): string
    {
        $templatePath = storage_path('app/'.$template->fichier_path);

        if (! file_exists($templatePath)) {
            throw new \Exception("Template introuvable : {$templatePath}");
        }

        $phpWord = $this->chargerTemplate($templatePath);
        $this->remplacerVariables($phpWord, $data);

        $filename = "fiche-{$template->type}-".now()->format('Ymd-His').'.docx';
        $outputPath = storage_path('app/temp/'.$filename);

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

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
            $text = str_replace($placeholder, $value ?? '', $text);
        }

        return $text;
    }

    /**
     * Stocke le fichier généré dans le storage.
     */
    public function stocker(string $localPath, string $destination): string
    {
        $filename = basename($localPath);
        Storage::disk('public')->putFileAs(
            'fiches/'.$destination,
            $localPath,
            $filename
        );

        return Storage::disk('public')->url('fiches/'.$destination.'/'.$filename);
    }
}
