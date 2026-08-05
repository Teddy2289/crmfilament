<?php
$path = __DIR__ . '/../app/Mail/WeeklyCommercialRecap.php';
$p = file_get_contents($path);
$old = <<<'PHP'
    public function __construct(
        public User $user,
        public array $stats,
        public Carbon $startDate,
        public Carbon $endDate
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Récapitulatif hebdomadaire - Commercial',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-commercial-recap',
        );
    }

    public function attachments(): array
    {
        return [];
    }
PHP;
$new = <<<'PHP'
    public function __construct(
        public User $user,
        public array $stats,
        public Carbon $startDate,
        public Carbon $endDate
    ) {
        $this->templateKey = 'recap.weekly_commercial';

        $this->templateVariables = [
            'prenom' => $this->user->prenom ?? '',
            'nom' => $this->user->nom ?? '',
            'start_date' => $this->startDate->format('d/m/Y'),
            'end_date' => $this->endDate->format('d/m/Y'),
            'stats' => $this->stats,
        ];
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->getRenderedSubject());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template', with: [
            'corps' => $this->getRenderedBody(),
        ]);
    }

    public function attachments(): array
    {
        return [];
    }
PHP;
if (strpos($p, $old) !== false) {
    $p = str_replace($old, $new, $p);
    file_put_contents($path, $p);
    echo "patched2";
} else {
    echo "old not found";
}
