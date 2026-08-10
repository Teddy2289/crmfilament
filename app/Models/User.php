<?php

namespace App\Models;

use App\Services\Crm\CrmProfileService;
use App\Traits\HasInputSanitization;
use App\Traits\HasModelValidation;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, HasModelValidation, HasInputSanitization;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'secteur',
        'actif',
        'role_cache',
        'google_token',
        'ringover_user_id',
        'ringover_email',
        'email_password',
        'email_last_sync',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'email_password',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_token' => 'encrypted:array',
            'actif' => 'boolean',
            'email_last_sync' => 'datetime',
            'email_password' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's name for Filament display.
     */
    public function getFilamentName(): string
    {
        return trim($this->prenom.' '.$this->nom) ?: $this->email;
    }

    /**
     * Get the user's name attribute (compatibilité Filament).
     */
    public function getNameAttribute(): string
    {
        return $this->getFilamentName();
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return app(CrmProfileService::class)->userCanAccessPanel($this, $panel->getId());
    }

    public function hasRoleCache(string $role): bool
    {
        return $this->role_cache === $role;
    }

    public function hasAllRolesCache(array $roles): bool
    {
        return in_array($this->role_cache, $roles);
    }

    // ── Constantes de rôles ─────────────────────────────────────────
    const ROLE_SUPER_ADMIN = 'super_admin';

    const ROLE_ADMIN = 'administrateur';

    const ROLE_COMMERCIAL = 'commercial';

    const ROLE_TELEPROSPECTEUR = 'teleprospecteur';

    const ROLE_OPERATEUR = 'operateur_n1';

    const ROLE_BACK_OFFICE = 'back_office';

    const ROLE_SUPERVISEUR = 'responsable_plateau';

    const ROLES = [
        self::ROLE_SUPER_ADMIN => 'Super Administrateur',
        self::ROLE_ADMIN => 'Administrateur',
        self::ROLE_COMMERCIAL => 'Commercial',
        self::ROLE_TELEPROSPECTEUR => 'Téléprospecteur',
        self::ROLE_OPERATEUR => 'Opérateur N1',
        self::ROLE_BACK_OFFICE => 'Back-Office',
        self::ROLE_SUPERVISEUR => 'Responsable Plateau',
    ];

    const SECTEURS = [
        'nord' => 'Nord',
        'sud' => 'Sud',
        'est' => 'Est',
        'ouest' => 'Ouest',
        'idf' => 'Île-de-France',
        'national' => 'National',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function getInitialesAttribute(): string
    {
        return strtoupper(
            substr($this->prenom, 0, 1).
                substr($this->nom, 0, 1)
        );
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role_cache] ?? $this->roles->first()?->name ?? 'Non défini';
    }

    public function getRoleColorAttribute(): string
    {
        return match ($this->role_cache) {
            self::ROLE_SUPER_ADMIN => 'danger',
            self::ROLE_ADMIN => 'warning',
            self::ROLE_COMMERCIAL => 'success',
            self::ROLE_TELEPROSPECTEUR => 'info',
            self::ROLE_OPERATEUR => 'primary',
            self::ROLE_BACK_OFFICE => 'gray',
            self::ROLE_SUPERVISEUR => 'warning',
            default => 'gray',
        };
    }

    public function getSecteurLabelAttribute(): string
    {
        return self::SECTEURS[$this->secteur] ?? $this->secteur ?? 'Non défini';
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->actif ? 'Actif' : 'Inactif';
    }

    public function getGoogleConnecteAttribute(): bool
    {
        return ! empty($this->google_token);
    }

    // ── Helpers rôles — délèguent à Spatie HasRoles ────────────────
    // Note : hasRole() est déjà fourni par Spatie\Permission\Traits\HasRoles
    // On surcharge uniquement pour supporter le tableau en plus de la string

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles->pluck('name')
            ->intersect(['super_admin', 'administrateur'])
            ->isNotEmpty();
    }

    public function isAdmin(): bool
    {
        return $this->hasRoleCache(self::ROLE_ADMIN) || $this->isSuperAdmin();
    }

    public function isCommercial(): bool
    {
        return $this->hasRoleCache(self::ROLE_COMMERCIAL);
    }

    public function isTeleprospecteur(): bool
    {
        return $this->hasRoleCache(self::ROLE_TELEPROSPECTEUR);
    }

    public function isOperateur(): bool
    {
        return $this->hasRoleCache(self::ROLE_OPERATEUR);
    }

    public function isBackOffice(): bool
    {
        return $this->hasRoleCache(self::ROLE_BACK_OFFICE);
    }

    public function isSuperviseur(): bool
    {
        return $this->hasRoleCache(self::ROLE_SUPERVISEUR);
    }

    // Assigner un rôle Spatie ET mettre à jour role_cache en même temps
    public function assignRoleWithCache(string $role): void
    {
        $this->syncRoles([$role]);
        $this->update(['role_cache' => $role]);
    }

    // Réaligne role_cache sur les rôles Spatie réellement assignés (à appeler après toute modification des rôles)
    public function syncRoleCacheFromRoles(): void
    {
        $role = $this->roles()->pluck('name')->first();

        if ($this->role_cache !== $role) {
            $this->update(['role_cache' => $role]);
        }
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function activer(): void
    {
        $this->update(['actif' => true]);
    }

    public function desactiver(): void
    {
        $this->update(['actif' => false]);
    }

    public function changerRole(string $role): void
    {
        if (! array_key_exists($role, self::ROLES)) {
            throw new \InvalidArgumentException("Rôle invalide : {$role}");
        }
        $this->assignRoleWithCache($role);
    }

    public function connecterGoogle(array $token): void
    {
        $this->update(['google_token' => $token]);
    }

    public function deconnecterGoogle(): void
    {
        $this->update(['google_token' => null]);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeInactifs(Builder $query): Builder
    {
        return $query->where('actif', false);
    }

    public function scopeParRole(Builder $query, string $role): Builder
    {
        return $query->where('role_cache', $role);
    }

    public function scopeCommerciaux(Builder $query): Builder
    {
        return $query->where('role_cache', self::ROLE_COMMERCIAL);
    }

    public function scopeTeleprospecteurs(Builder $query): Builder
    {
        return $query->where('role_cache', self::ROLE_TELEPROSPECTEUR);
    }

    public function scopeOperateurs(Builder $query): Builder
    {
        return $query->where('role_cache', self::ROLE_OPERATEUR);
    }

    public function scopeGoogleConnectes(Builder $query): Builder
    {
        return $query->whereNotNull('google_token');
    }

    public function scopeRecherche(Builder $query, string $terme): Builder
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
                ->orWhere('prenom', 'like', "%{$terme}%")
                ->orWhere('email', 'like', "%{$terme}%");
        });
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! isset($user->actif)) {
                $user->actif = true;
            }
        });

        // Définir les règles de validation
        static::bootHasModelValidation();
        
        // Définir les champs à nettoyer
        static::bootHasInputSanitization();
    }

    // ── Validation Rules ─────────────────────────────────────────────
    public function getValidationRules(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,'.($this->id ?? 'NULL'),
            'secteur' => 'nullable|string|in:nord,sud,est,ouest,idf,national',
            'ringover_email' => 'nullable|email',
        ];
    }

    // ── Sanitization Fields ───────────────────────────────────────────
    public function getSanitizableFields(): array
    {
        return [
            'nom' => 'sanitizeName',
            'prenom' => 'sanitizeName',
            'email' => 'sanitizeEmail',
            'secteur' => 'sanitizeString',
            'ringover_email' => 'sanitizeEmail',
        ];
    }

    // ── Relations ────────────────────────────────────────────────────
    public function ticketsOperateur()
    {
        return $this->hasMany(Ticket::class, 'operateur_id');
    }

    public function groupesTelepro()
    {
        return $this->belongsToMany(GroupeTelepro::class, 'groupe_telepro_user')->withTimestamps();
    }

    public function prospectsTeleprospecteur()
    {
        return $this->hasMany(Prospect::class, 'teleprospecteur_id');
    }

    public function prospectsCommercial()
    {
        return $this->hasMany(Prospect::class, 'commercial_id');
    }

    public function partenaires()
    {
        return $this->hasMany(Partenaire::class, 'commercial_id');
    }

    // Alias utilisé dans certaines Resources
    public function partenairesAssignes()
    {
        return $this->hasMany(Partenaire::class, 'commercial_id');
    }

    public function rendezVousCommercial()
    {
        return $this->hasMany(RendezVous::class, 'commercial_id');
    }

    public function rendezVousTeleprospecteur()
    {
        return $this->hasMany(RendezVous::class, 'teleprospecteur_id');
    }

    public function appels()
    {
        return $this->hasMany(Appel::class, 'user_id');
    }

    public function artisanProspections()
    {
        return $this->hasMany(ArtisanProspection::class, 'teleprospecteur_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function reclamationsSupervisees()
    {
        return $this->hasMany(ReclamationP8::class, 'superviseur_id');
    }

    public function rapportsSatisfaction()
    {
        return $this->hasMany(RapportSatisfactionP6::class, 'operateur_id');
    }

    public function prospectsValides()
    {
        return $this->hasMany(Prospect::class, 'valide_par');
    }

    public function opportunites()
    {
        return $this->hasMany(Opportunite::class, 'assigne_a');
    }

    // roles() est fourni par le trait HasRoles (Spatie Laravel Permission)
    // — ne pas le redéfinir ici, sinon PHP résout App\Models\Role (inexistant).

    public function dashboards()
    {
        return $this->hasMany(UserDashboard::class);
    }

    public function dashboardParDefaut()
    {
        return $this->hasOne(UserDashboard::class)->where('par_defaut', true);
    }

    public function importMappings()
    {
        return $this->hasMany(ImportMapping::class);
    }

    public function customNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function customNotificationsNonLues()
    {
        return $this->hasMany(Notification::class)->where('lu', false);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function evenementsCalendrier()
    {
        return $this->hasMany(EvenementCalendrier::class);
    }

    public function chatRooms()
    {
        return $this->hasMany(ChatRoom::class, 'created_by');
    }

    public function chatRoomParticipants()
    {
        return $this->hasMany(ChatRoomParticipant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getActiveChatRoomsAttribute()
    {
        return ChatRoom::whereHas('participants', function ($q) {
            $q->where('user_id', $this->id)->where('actif', true);
        })->active()->get();
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'created_by');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function runningTimeEntry()
    {
        return $this->hasOne(TimeEntry::class)->running();
    }

    public function kpis()
    {
        return $this->hasMany(Kpi::class);
    }

    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    public function ganttTasks()
    {
        return $this->hasMany(GanttTask::class, 'assigned_to');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function dataDeletionRequests()
    {
        return $this->hasMany(DataDeletionRequest::class);
    }

    public function backups()
    {
        return $this->hasMany(Backup::class, 'created_by');
    }

    public function analyticDashboards()
    {
        return $this->hasMany(AnalyticDashboard::class, 'created_by');
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class, 'assigned_to');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function assignedCampaigns()
    {
        return $this->hasMany(Campaign::class, 'assigned_to');
    }

    // Two Factor Authentication methods
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    public function enableTwoFactor(string $secret): void
    {
        $this->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function disableTwoFactor(): void
    {
        $this->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->two_factor_secret ? decrypt($this->two_factor_secret) : null;
    }

    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        
        $this->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);
        
        return $codes;
    }

    public function getRecoveryCodes(): array
    {
        return $this->two_factor_recovery_codes ? json_decode(decrypt($this->two_factor_recovery_codes), true) : [];
    }

    public function verifyRecoveryCode(string $code): bool
    {
        return in_array(strtoupper($code), $this->getRecoveryCodes());
    }

    public function consumeRecoveryCode(string $code): bool
    {
        $codes = $this->getRecoveryCodes();
        $code = strtoupper($code);
        
        if (!in_array($code, $codes)) {
            return false;
        }
        
        $codes = array_diff($codes, [$code]);
        $this->update([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
        ]);
        
        return true;
    }

    public function crmProfile()
    {
        return $this->belongsTo(CrmProfile::class, 'role_cache', 'role_name');
    }
}
