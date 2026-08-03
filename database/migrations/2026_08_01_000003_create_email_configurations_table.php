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
        Schema::create('email_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_global')->default(false)->comment('Configuration globale pour tous les utilisateurs');
            
            // Configuration IMAP
            $table->string('imap_host')->default('imap.gmail.com');
            $table->integer('imap_port')->default(993);
            $table->string('imap_encryption')->default('ssl')->comment('ssl, tls, starttls, none');
            $table->string('imap_protocol')->default('imap')->comment('imap, pop3');
            
            // Configuration SMTP
            $table->string('smtp_host')->default('smtp.gmail.com');
            $table->integer('smtp_port')->default(587);
            $table->string('smtp_encryption')->default('tls')->comment('ssl, tls, starttls, none');
            
            // Identifiants
            $table->string('email');
            $table->string('password');
            $table->string('from_name')->nullable();
            
            // Synchronisation
            $table->boolean('sync_enabled')->default(true);
            $table->integer('sync_interval')->default(5)->comment('Intervalle en minutes');
            $table->integer('sync_limit')->default(50)->comment('Nombre d\'emails à synchroniser');
            $table->timestamp('last_sync_at')->nullable();
            
            // Statut
            $table->boolean('active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'active']);
            $table->index('is_global');
            $table->index('sync_enabled');
            
            // Unique: un utilisateur ne peut avoir qu'une configuration active non globale
            $table->unique(['user_id', 'is_global'], 'unique_user_global_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_configurations');
    }
};
