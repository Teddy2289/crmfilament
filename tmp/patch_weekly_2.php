<?php
$path = __DIR__ . '/../app/Mail/WeeklyCommercialRecap.php';
$p = file_get_contents($path);
$old = "    public function __construct(\n        public User $user,\n        public array $stats,\n        public Carbon $startDate,\n        public Carbon $endDate\n    ) {}\n\n    public function envelope(): Envelope\n    {\n        return new Envelope(\n            subject: 'Récapitulatif hebdomadaire - Commercial',\n        );\n    }\n\n    public function content(): Content\n    {\n        return new Content(\n            view: 'emails.weekly-commercial-recap',\n        );\n    }\n\n    public function attachments(): array\n    {\n        return [];\n    }";
$new = "    public function __construct(\n        public User $user,\n        public array $stats,\n        public Carbon $startDate,\n        public Carbon $endDate\n    ) {\n        $this->templateKey = 'recap.weekly_commercial';\n\n        $this->templateVariables = [\n            'prenom' => $this->user->prenom ?? '',\n            'nom' => $this->user->nom ?? '',\n            'start_date' => $this->startDate->format('d/m/Y'),\n            'end_date' => $this->endDate->format('d/m/Y'),\n            'stats' => $this->stats,\n        ];\n    }\n\n    public function envelope(): Envelope\n    {\n        return new Envelope(subject: $this->getRenderedSubject());\n    }\n\n    public function content(): Content\n    {\n        return new Content(view: 'emails.template', with: [\n            'corps' => $this->getRenderedBody(),\n        ]);\n    }\n\n    public function attachments(): array\n    {\n        return [];\n    }";
if (strpos($p, $old) !== false) {
    $p = str_replace($old, $new, $p);
    file_put_contents($path, $p);
    echo "patched2";
} else {
    echo "old not found";
}
