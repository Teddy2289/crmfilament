<?php

use App\Filament\NsConseil\Pages\PhoningBackOffice;
use App\Filament\NsConseil\Pages\PhoningWorkflow;

/**
 * Tests de rétrocompatibilité après le refactoring phoning.
 * Req 13.1, 13.2, 13.3, 13.4, 15.4
 */

test('PhoningWorkflow expose les méthodes publiques critiques', function () {
    expect(method_exists(PhoningWorkflow::class, 'getStatusValidationCodes'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'getEmailPreviewPayload'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'loadQueue'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'skipCall'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'getCampagnesDisponibles'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'getCampagneInfo'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'getContactsRestantsCount'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'getCallHistory'))->toBeTrue();
});

test('HasStatusResult expose les méthodes requises dans PhoningWorkflow', function () {
    $methods = [
        'saveInterlocuteur',
        'getSelectedStatus',
        'getStatutsPhoning',
        'getStatutsPhoningGroupes',
        'getPipelineTransitionPreview',
        'compterTentativesNonAbouties',
        'getTentativesAppel',
        'getRappelStatusCodes',
    ];

    foreach ($methods as $method) {
        expect(method_exists(PhoningWorkflow::class, $method))
            ->toBeTrue("{$method}() doit être exposée via HasStatusResult");
    }
});

test('HasEmailPreview expose les méthodes requises dans PhoningWorkflow', function () {
    expect(method_exists(PhoningWorkflow::class, 'confirmEmailPreview'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'cancelEmailPreview'))->toBeTrue();
    expect(method_exists(PhoningWorkflow::class, 'syncEmailPreviewContent'))->toBeTrue();
});

test('PhoningBackOffice::loadProspects retourne [] sans utilisateur sélectionné', function () {
    $backOffice = new PhoningBackOffice();
    $backOffice->loadProspects();
    expect($backOffice->prospectList)->toBeEmpty();
});

test('PhoningBackOffice expose les méthodes HasQueueManagement', function () {
    $methods = [
        'loadProspects', 'reorderFromDrag', 'moveToTop',
        'moveSelectedToTop', 'removeSelected', 'resetOrder',
    ];

    foreach ($methods as $method) {
        expect(method_exists(PhoningBackOffice::class, $method))
            ->toBeTrue("{$method}() doit exister via HasQueueManagement");
    }
});

test('PhoningWorkflow respecte la limite de lignes ≤ 250', function () {
    $file  = dirname(__DIR__, 2) . '/app/Filament/NsConseil/Pages/PhoningWorkflow.php';
    $lines = count(file($file));
    expect($lines)->toBeLessThanOrEqual(250);
});

test('PhoningBackOffice respecte la limite de lignes ≤ 200', function () {
    $file  = dirname(__DIR__, 2) . '/app/Filament/NsConseil/Pages/PhoningBackOffice.php';
    $lines = count(file($file));
    expect($lines)->toBeLessThanOrEqual(200);
});

test('composant contact-panel existe et respecte la limite de lignes ≤ 300', function () {
    $file = dirname(__DIR__, 2) . '/resources/views/components/phoning/contact-panel.blade.php';
    expect(file_exists($file))->toBeTrue('contact-panel.blade.php doit exister');
    $lines = count(file($file));
    expect($lines)->toBeLessThanOrEqual(300);
});

test('composant contact-panel déclare les bons @props', function () {
    $file    = dirname(__DIR__, 2) . '/resources/views/components/phoning/contact-panel.blade.php';
    $content = file_get_contents($file);
    expect($content)->toContain('@props');
    foreach (['contact', 'contactType', 'queueCount', 'progress', 'isSupervisorMode'] as $prop) {
        expect($content)->toContain("'{$prop}'");
    }
});

test('composant contact-panel se compile sans exception Blade', function () {
    $file   = dirname(__DIR__, 2) . '/resources/views/components/phoning/contact-panel.blade.php';
    $source = file_get_contents($file);

    // BladeCompiler::compileString() est accessible statiquement
    expect(fn () => \Illuminate\Support\Facades\Blade::compileString($source))
        ->not->toThrow(\Throwable::class);
});
