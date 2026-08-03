<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Chiffrer les email_password existants
        User::whereNotNull('email_password')
            ->where('email_password', '!=', '')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    try {
                        // Vérifier si déjà chiffré
                        $decrypted = @Crypt::decryptString($user->email_password);
                        // Si le déchiffrement réussit, c'est déjà chiffré
                        if ($decrypted === $user->email_password) {
                            // Pas chiffré, on le chiffre
                            $user->email_password = Crypt::encryptString($user->email_password);
                            $user->saveQuietly();
                        }
                    } catch (\Exception $e) {
                        // Si le déchiffrement échoue, c'est probablement déjà chiffré
                        // On ne fait rien
                    }
                }
            });

        // Chiffrer les google_token existants
        User::whereNotNull('google_token')
            ->where('google_token', '!=', '')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    try {
                        $decrypted = @Crypt::decrypt($user->google_token);
                        if ($decrypted === $user->google_token) {
                            $user->google_token = Crypt::encrypt($user->google_token);
                            $user->saveQuietly();
                        }
                    } catch (\Exception $e) {
                        // Déjà chiffré
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Déchiffrer les email_password (rollback)
        User::whereNotNull('email_password')
            ->where('email_password', '!=', '')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    try {
                        $decrypted = Crypt::decryptString($user->email_password);
                        $user->email_password = $decrypted;
                        $user->saveQuietly();
                    } catch (\Exception $e) {
                        // Déjà déchiffré ou erreur
                    }
                }
            });

        // Déchiffrer les google_token (rollback)
        User::whereNotNull('google_token')
            ->where('google_token', '!=', '')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    try {
                        $decrypted = Crypt::decrypt($user->google_token);
                        $user->google_token = $decrypted;
                        $user->saveQuietly();
                    } catch (\Exception $e) {
                        // Déjà déchiffré ou erreur
                    }
                }
            });
    }
};
