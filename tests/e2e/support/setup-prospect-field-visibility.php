<?php

use App\Enums\ProspectStatut;
use App\Models\CrmProfile;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Crm\CrmProfileService;
use App\Support\AccessRightsCatalog;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$roleName = 'e2e_prospect_field_show';
$email = 'e2e-prospect-field-show@example.test';
$password = 'changeme123';
$prospectEmail = 'hidden-field-e2e@example.test';
$prospectName = 'E2E Prospect Champs';
$prospectPhone = '0102030405';
$prospectSiret = '12345678901234';

AccessRightsCatalog::ensurePermissionsExist();

$role = Role::firstOrCreate([
    'name' => $roleName,
    'guard_name' => 'web',
]);

AccessRightsCatalog::syncSelectiveAccess($role, [
    'prospects.view_any',
    'prospects.view',
    'prospects.create',
    'prospects.update',
    'fields.prospects.nom.all',
    'fields.prospects.telephone.all',
    'fields.prospects.siret.show',
]);

CrmProfile::updateOrCreate(
    ['role_name' => $roleName],
    [
        'label' => 'E2E droits champs prospects',
        'description' => 'Profil dedie au test E2E de visibilite des champs prospects.',
        'panels' => ['ns-conseil'],
        'landing_path' => '/ns-conseil/prospects',
        'couleur' => 'gray',
        'icone' => 'heroicon-o-eye',
        'ordre' => 90,
        'can_validate_qf' => false,
        'can_import' => false,
        'is_supervisor' => false,
        'is_system' => false,
        'actif' => true,
    ],
);

$user = User::withTrashed()->updateOrCreate(
    ['email' => $email],
    [
        'nom' => 'Field',
        'prenom' => 'Visibility',
        'password' => Hash::make($password),
        'secteur' => 'national',
        'actif' => true,
        'role_cache' => $roleName,
    ],
);

if (method_exists($user, 'restore') && $user->trashed()) {
    $user->restore();
}

$user->syncRoles([$role]);

$prospect = Prospect::withTrashed()->updateOrCreate(
    ['email' => $prospectEmail],
    [
        'nom' => $prospectName,
        'telephone' => $prospectPhone,
        'siret' => $prospectSiret,
        'ville' => 'Paris',
        'departement' => '75',
        'statut' => ProspectStatut::AC->value,
        'teleprospecteur_id' => $user->id,
    ],
);

if (method_exists($prospect, 'restore') && $prospect->trashed()) {
    $prospect->restore();
}

echo json_encode([
    'user' => [
        'email' => $email,
        'password' => $password,
    ],
    'prospect' => [
        'id' => $prospect->id,
        'name' => $prospectName,
        'phone' => $prospectPhone,
        'email' => $prospectEmail,
        'siret' => $prospectSiret,
    ],
    'checks' => [
        'password_ok' => Hash::check($password, $user->password),
        'panel_ok' => app(CrmProfileService::class)->userCanAccessPanel($user, 'ns-conseil'),
    ],
], JSON_THROW_ON_ERROR);
