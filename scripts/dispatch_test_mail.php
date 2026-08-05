<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\SendProspectionMailJob;
use App\Mail\PreviewableProspectionMail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

$activeConfig = null;

if (Auth::check()) {
    $activeConfig = App\Models\EmailConfiguration::forUser(Auth::id())
        ->active()
        ->first();
}

$to = Config::get('mail.from.address', 'admin@ns-conseil.com');
$subject = 'Test SendProspectionMailJob';
$body = '<p>Ceci est un test HTML de <strong>SendProspectionMailJob</strong>.</p>';

$mailable = new PreviewableProspectionMail($subject, $body);

// If there is a logged in user context, Auth::id() may be null; pass null for notifyUserId.
$job = new SendProspectionMailJob(
    mailable: $mailable,
    to: $to,
    emailLabel: 'Test envoi',
    prospectId: null,
    notifyUserId: Auth::id(),
    sourceEmail: $activeConfig?->email ?: $to,
    emailConfigurationId: $activeConfig?->id,
);

dispatch($job);

echo "Dispatched SendProspectionMailJob to {$to}\n";
