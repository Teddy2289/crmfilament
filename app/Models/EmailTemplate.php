<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'nom',
        'cle',
        'sujet',
        'corps',
        'description',
        'contact_type',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public static function findByCle(string $cle): ?self
    {
        return static::where('cle', $cle)->where('actif', true)->first();
    }

    public function renderSujet(array $variables): string
    {
        return $this->remplacerVariables($this->sujet, $variables);
    }

    /**
     * Le corps peut être soit du HTML riche (éditeur WYSIWYG, contient des
     * balises), soit un ancien modèle en texte brut — le corps est alors
     * échappé ici pour être injecté sans échappement supplémentaire dans
     * la vue (voir emails.template), comme le faisait auparavant le
     * `{{ $corps }}` de la vue pour tout le contenu.
     */
    public function renderCorps(array $variables): string
    {
        $corps = $this->remplacerVariables($this->corps, $variables);

        return $this->estHtml($this->corps) ? $corps : e($corps);
    }

    /**
     * Version texte brut du corps rendu (pour la partie text/plain d'un
     * envoi sans vue Blade, cf. SendEmailAction) : on retire les balises
     * HTML du contenu riche, ou on décode les entités du texte brut déjà
     * échappé par renderCorps().
     */
    public function renderCorpsPlainText(array $variables): string
    {
        $corps = $this->renderCorps($variables);

        if (! $this->estHtml($this->corps)) {
            return html_entity_decode($corps, ENT_QUOTES);
        }

        $texte = str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $corps);

        return trim(html_entity_decode(strip_tags($texte), ENT_QUOTES));
    }

    public function corpsEstHtml(): bool
    {
        return $this->estHtml($this->corps);
    }

    private function estHtml(string $corps): bool
    {
        return str_contains($corps, '<');
    }

    private function remplacerVariables(string $texte, array $variables): string
    {
        $estHtml = $this->estHtml($texte);
        $variables = collect($variables)->mapWithKeys(fn ($value, $key) => [strtolower(trim((string) $key)) => (string) $value])->all();

        return preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}|\[\[\s*([A-Za-z0-9_.-]+)\s*\]\]|(?<!\{)\{\s*([A-Za-z0-9_.-]+)\s*\}(?!\})/',
            function (array $match) use ($variables, $estHtml): string {
                $cle = strtolower(trim((string) ($match[1] ?: $match[2] ?: $match[3])));
                if (! array_key_exists($cle, $variables)) {
                    return $match[0];
                }
                $valeur = $variables[$cle];
                return $estHtml ? e($valeur) : $valeur;
            },
            $texte
        ) ?? $texte;
    }
}
