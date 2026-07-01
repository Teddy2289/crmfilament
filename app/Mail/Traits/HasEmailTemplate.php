<?php

namespace App\Mail\Traits;

use App\Models\EmailTemplate;
use App\Models\SentEmail;

trait HasEmailTemplate
{
    protected string $templateKey = '';
    protected array $templateVariables = [];
    protected ?EmailTemplate $resolvedTemplate = null;

    protected function resolveTemplate(): EmailTemplate
    {
        if ($this->resolvedTemplate) {
            return $this->resolvedTemplate;
        }

        $template = EmailTemplate::findByCle($this->templateKey);

        if (!$template) {
            throw new \RuntimeException("Modèle d'e-mail introuvable : {$this->templateKey}. Créez-le dans Communication > Modèles d'e-mail.");
        }

        return $this->resolvedTemplate = $template;
    }

    protected function getRenderedSubject(): string
    {
        return $this->resolveTemplate()->renderSujet($this->templateVariables);
    }

    protected function getRenderedBody(): string
    {
        return $this->resolveTemplate()->renderCorps($this->templateVariables);
    }

    public function logEnvoi(mixed $emailable, string $destinataire, ?string $cc = null): void
    {
        SentEmail::create([
            'emailable_type' => get_class($emailable),
            'emailable_id'   => $emailable->id,
            'template_cle'   => $this->templateKey,
            'sujet'          => $this->getRenderedSubject(),
            'destinataire'   => $destinataire,
            'cc'             => $cc,
            'corps'          => $this->getRenderedBody(),
            'envoye_par'     => auth()->id(),
            'envoye_at'      => now(),
        ]);
    }
}
