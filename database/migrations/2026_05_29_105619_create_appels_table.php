<?php

use App\Enums\EventResult;
use App\Enums\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appels', function (Blueprint $table) {
            $table->id();
            $table->morphs('appelable');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', array_column(EventType::cases(), 'value'))
                ->default(EventType::Appel->value);
            $table->enum('resultat', array_column(EventResult::cases(), 'value'))
                ->nullable()
                ->default(EventResult::Realise->value);
            $table->dateTime('date_heure');
            $table->integer('duree_secondes')->nullable();
            $table->text('commentaire')->nullable();
            $table->string('enregistrement_audio')->nullable();
            $table->string('ringover_call_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appels');
    }
};
