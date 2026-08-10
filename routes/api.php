<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
|
| Toutes les routes API sont préfixées par /api (géré par bootstrap/app.php)
| puis par /v1 ci-dessous.
|
| Les contrôleurs sont référencés en FQCN ; les fichiers sont créés dans
| les tâches suivantes du plan d'implémentation.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ── Authentification (non protégée, rate limiting strict) ──────
    Route::post('auth/login',
        [\App\Http\Controllers\Api\V1\AuthController::class, 'login']
    )->middleware('throttle:login')->name('auth.login');

    Route::post('auth/refresh',
        [\App\Http\Controllers\Api\V1\AuthController::class, 'refresh']
    )->middleware('throttle:login')->name('auth.refresh');

    // ── Routes protégées (Sanctum + rate limiting API) ─────────────
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // Auth
        Route::post('auth/logout',
            [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']
        )->name('auth.logout');

        Route::get('auth/me',
            [\App\Http\Controllers\Api\V1\AuthController::class, 'me']
        )->name('auth.me');

        // Prospects
        Route::apiResource('prospects', \App\Http\Controllers\Api\V1\ProspectController::class);
        Route::post('prospects/{prospect}/appel',
            [\App\Http\Controllers\Api\V1\ProspectController::class, 'enregistrerAppel']
        )->name('prospects.appel');

        // Partenaires (sans destroy)
        Route::apiResource('partenaires', \App\Http\Controllers\Api\V1\PartenaireController::class)
            ->except(['destroy']);
        Route::get('partenaires/{partenaire}/contacts',
            [\App\Http\Controllers\Api\V1\PartenaireController::class, 'contacts']
        )->name('partenaires.contacts');
        Route::get('partenaires/{partenaire}/rendez-vous',
            [\App\Http\Controllers\Api\V1\PartenaireController::class, 'rendezVous']
        )->name('partenaires.rendez-vous');

        // Clients (lecture seule)
        Route::get('clients',
            [\App\Http\Controllers\Api\V1\ClientController::class, 'index']
        )->name('clients.index');
        Route::get('clients/{client}',
            [\App\Http\Controllers\Api\V1\ClientController::class, 'show']
        )->name('clients.show');
        Route::get('clients/{client}/dossiers-formation',
            [\App\Http\Controllers\Api\V1\ClientController::class, 'dossiersFormation']
        )->name('clients.dossiers-formation');

        // Tickets
        Route::apiResource('tickets', \App\Http\Controllers\Api\V1\TicketController::class);

        // Réclamations (sans destroy)
        Route::apiResource('reclamations', \App\Http\Controllers\Api\V1\ReclamationController::class)
            ->except(['destroy']);

        // Devis (lecture seule)
        Route::get('devis',
            [\App\Http\Controllers\Api\V1\DevisController::class, 'index']
        )->name('devis.index');
        Route::get('devis/{devis}',
            [\App\Http\Controllers\Api\V1\DevisController::class, 'show']
        )->name('devis.show');

        // Bons de commande (lecture seule)
        Route::get('bons-de-commande',
            [\App\Http\Controllers\Api\V1\BonDeCommandeController::class, 'index']
        )->name('bons-de-commande.index');
        Route::get('bons-de-commande/{bdc}',
            [\App\Http\Controllers\Api\V1\BonDeCommandeController::class, 'show']
        )->name('bons-de-commande.show');

        // Rendez-vous (sans destroy)
        Route::apiResource('rendez-vous', \App\Http\Controllers\Api\V1\RendezVousController::class)
            ->except(['destroy']);

        // Campagnes phoning (lecture)
        Route::get('campagnes-phoning',
            [\App\Http\Controllers\Api\V1\CampagnePhoningController::class, 'index']
        )->name('campagnes-phoning.index');
        Route::get('campagnes-phoning/{campagne}',
            [\App\Http\Controllers\Api\V1\CampagnePhoningController::class, 'show']
        )->name('campagnes-phoning.show');
        Route::get('campagnes-phoning/{campagne}/prospects',
            [\App\Http\Controllers\Api\V1\CampagnePhoningController::class, 'prospects']
        )->name('campagnes-phoning.prospects');

        // Administration — révocation des tokens
        Route::middleware('role:super_admin|administrateur')->group(function () {
            Route::delete('admin/users/{user}/tokens',
                [\App\Http\Controllers\Api\V1\Admin\UserTokenController::class, 'revokeAll']
            )->name('admin.users.tokens.revoke-all');
        });
    });
});
