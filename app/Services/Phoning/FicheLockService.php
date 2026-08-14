<?php

namespace App\Services\Phoning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FicheLockService
{
    /**
     * Délai d'inactivité avant libération automatique d'un verrou (en minutes).
     */
    private const LOCK_TIMEOUT_MINUTES = 15;

    /**
     * Acquiert un verrou sur une fiche pour l'utilisateur courant.
     * Libère automatiquement les verrous expirés d'autres utilisateurs.
     *
     * @return array ['success' => bool, 'locked_by' => ?User, 'message' => string]
     */
    public function acquireLock(Model $model): array
    {
        $user = Auth::user();
        if (!$user) {
            return [
                'success' => false,
                'locked_by' => null,
                'message' => 'Utilisateur non authentifié',
            ];
        }

        // Nettoyer les verrous expirés
        $this->releaseExpiredLocks();

        // Chercher un verrou existant
        $existingLock = DB::table('phoning_fiche_locks')
            ->where('lockable_type', $model::class)
            ->where('lockable_id', $model->id)
            ->first();

        if ($existingLock) {
            $lockedByUser = User::find($existingLock->locked_by_user_id);

            // Si c'est l'utilisateur courant, mettre à jour le heartbeat
            if ($existingLock->locked_by_user_id === $user->id) {
                $this->updateHeartbeat($model, $user->id);
                return [
                    'success' => true,
                    'locked_by' => null,
                    'message' => 'Verrou renouvelé',
                ];
            }

            // C'est un autre utilisateur
            return [
                'success' => false,
                'locked_by' => $lockedByUser,
                'message' => "Cette fiche est actuellement en cours de traitement par {$lockedByUser->prenom} {$lockedByUser->nom}",
            ];
        }

        // Créer un nouveau verrou
        DB::table('phoning_fiche_locks')->insert([
            'lockable_type' => $model::class,
            'lockable_id' => $model->id,
            'locked_by_user_id' => $user->id,
            'locked_at' => now(),
            'heartbeat_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'locked_by' => null,
            'message' => 'Fiche verrouillée avec succès',
        ];
    }

    /**
     * Libère le verrou d'une fiche.
     */
    public function releaseLock(Model $model): void
    {
        DB::table('phoning_fiche_locks')
            ->where('lockable_type', $model::class)
            ->where('lockable_id', $model->id)
            ->delete();
    }

    /**
     * Libère tous les verrous de l'utilisateur courant.
     */
    public function releaseAllUserLocks(?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return;
        }

        DB::table('phoning_fiche_locks')
            ->where('locked_by_user_id', $userId)
            ->delete();
    }

    /**
     * Libère les verrous expirés (inactifs depuis plus de LOCK_TIMEOUT_MINUTES).
     */
    public function releaseExpiredLocks(): void
    {
        DB::table('phoning_fiche_locks')
            ->where('heartbeat_at', '<', now()->subMinutes(self::LOCK_TIMEOUT_MINUTES))
            ->delete();
    }

    /**
     * Met à jour le heartbeat d'un verrou (indique que l'utilisateur est encore actif).
     */
    public function updateHeartbeat(Model $model, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return;
        }

        DB::table('phoning_fiche_locks')
            ->where('lockable_type', $model::class)
            ->where('lockable_id', $model->id)
            ->where('locked_by_user_id', $userId)
            ->update(['heartbeat_at' => now()]);
    }

    /**
     * Obtient les informations de verrou d'une fiche.
     *
     * @return array ['is_locked' => bool, 'locked_by' => ?User, 'is_own_lock' => bool]
     */
    public function getLockInfo(Model $model): array
    {
        $user = Auth::user();

        $lock = DB::table('phoning_fiche_locks')
            ->where('lockable_type', $model::class)
            ->where('lockable_id', $model->id)
            ->first();

        if (!$lock) {
            return [
                'is_locked' => false,
                'locked_by' => null,
                'is_own_lock' => false,
            ];
        }

        $lockedByUser = User::find($lock->locked_by_user_id);
        $isOwnLock = $user && $lock->locked_by_user_id === $user->id;

        return [
            'is_locked' => true,
            'locked_by' => $lockedByUser,
            'is_own_lock' => $isOwnLock,
        ];
    }

    /**
     * Obtient le timeout en minutes.
     */
    public static function getLockTimeoutMinutes(): int
    {
        return self::LOCK_TIMEOUT_MINUTES;
    }
}
