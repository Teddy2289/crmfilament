<?php

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
        Schema::create('sent_emails', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('emailable');
            $table->string('template_cle')->nullable();
            $table->string('sujet');
            $table->text('destinataire');
            $table->text('cc')->nullable();
            $table->longText('corps')->nullable();
            $table->foreignId('envoye_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('envoye_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_emails');
    }
};
