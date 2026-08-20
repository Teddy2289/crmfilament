<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table): void {
            $table->string('fiche_docx_path')->nullable()->after('fiche_word_path');
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table): void {
            $table->dropColumn('fiche_docx_path');
        });
    }
};
