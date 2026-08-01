<?php

namespace Tests\Unit;

use App\Filament\NsConseil\Pages\PhoningWorkflow;
use App\Models\StatutPhoning;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhoningWorkflowStatusesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('crm_settings');
        Schema::dropIfExists('statut_phonings');

        Schema::create('crm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('groupe')->default('')->nullable();
            $table->string('cle')->default('')->nullable();
            $table->string('valeur')->nullable();
            $table->string('type')->default('string');
            $table->string('label')->nullable();
            $table->string('description')->nullable();
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('statut_phonings', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('code');
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('couleur')->default('gray');
            $table->string('icone')->default('📞');
            $table->integer('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function test_it_builds_validation_codes_from_configured_statuses(): void
    {
        $workflow = new PhoningWorkflow();
        $workflow->contactType = 'prospect';

        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'std_nr',
            'label' => 'Standard non répondu',
            'actif' => true,
        ]);

        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'rp',
            'label' => 'Rappel planifié',
            'actif' => true,
        ]);

        $this->assertSame(['std_nr', 'rp'], $workflow->getStatusValidationCodes());
    }

    public function test_it_returns_no_validation_codes_when_no_statuses_are_configured(): void
    {
        $workflow = new PhoningWorkflow();
        $workflow->contactType = 'prospect';

        $this->assertSame([], $workflow->getStatusValidationCodes());
    }
}
