<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groupe_telepro_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_telepro_id')->constrained('groupes_telepro')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['groupe_telepro_id', 'user_id']);
        });

        // Reprend l'affectation unique existante (users.groupe_telepro_id) dans le pivot,
        // pour ne pas perdre les affectations déjà en place au moment du passage en multi-groupe.
        if (Schema::hasColumn('users', 'groupe_telepro_id')) {
            $now = now();
            $rows = DB::table('users')
                ->whereNotNull('groupe_telepro_id')
                ->get(['id', 'groupe_telepro_id']);

            foreach ($rows as $row) {
                DB::table('groupe_telepro_user')->insert([
                    'groupe_telepro_id' => $row->groupe_telepro_id,
                    'user_id' => $row->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['groupe_telepro_id']);
                $table->dropColumn('groupe_telepro_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'groupe_telepro_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('groupe_telepro_id')
                    ->nullable()
                    ->after('role_cache')
                    ->constrained('groupes_telepro')
                    ->nullOnDelete();
            });

            // Ne restaure qu'une seule affectation par utilisateur (limite du modèle single-FK).
            DB::table('groupe_telepro_user')
                ->orderBy('id')
                ->get(['groupe_telepro_id', 'user_id'])
                ->unique('user_id')
                ->each(function ($row) {
                    DB::table('users')
                        ->where('id', $row->user_id)
                        ->update(['groupe_telepro_id' => $row->groupe_telepro_id]);
                });
        }

        Schema::dropIfExists('groupe_telepro_user');
    }
};
