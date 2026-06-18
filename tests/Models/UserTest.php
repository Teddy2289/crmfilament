<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    #[Test]
    public function get_filament_name_returns_full_name(): void
    {
        $user = new User;
        $user->prenom = 'Jean';
        $user->nom = 'Dupont';

        $this->assertEquals('Jean Dupont', $user->getFilamentName());
    }

    #[Test]
    public function get_filament_name_falls_back_to_email(): void
    {
        $user = new User;
        $user->prenom = '';
        $user->nom = '';
        $user->email = 'test@example.com';

        $this->assertEquals('test@example.com', $user->getFilamentName());
    }

    #[Test]
    public function name_attribute_returns_filament_name(): void
    {
        $user = new User;
        $user->prenom = 'Marie';
        $user->nom = 'Martin';

        $this->assertEquals('Marie Martin', $user->name);
    }

    #[Test]
    public function nom_complet_attribute(): void
    {
        $user = new User;
        $user->prenom = 'Pierre';
        $user->nom = 'Durand';

        $this->assertEquals('Pierre Durand', $user->nom_complet);
    }

    #[Test]
    public function initiales_attribute(): void
    {
        $user = new User;
        $user->prenom = 'Jean';
        $user->nom = 'Dupont';

        $this->assertEquals('JD', $user->initiales);
    }

    #[Test]
    public function role_label_attribute_with_known_role(): void
    {
        $user = new User;
        $user->role_cache = User::ROLE_COMMERCIAL;

        $this->assertEquals('Commercial', $user->role_label);
    }

    #[Test]
    public function role_color_attribute(): void
    {
        $user = new User;

        $user->role_cache = User::ROLE_SUPER_ADMIN;
        $this->assertEquals('danger', $user->role_color);

        $user->role_cache = User::ROLE_COMMERCIAL;
        $this->assertEquals('success', $user->role_color);

        $user->role_cache = User::ROLE_TELEPROSPECTEUR;
        $this->assertEquals('info', $user->role_color);
    }

    #[Test]
    public function role_color_default_is_gray(): void
    {
        $user = new User;
        $user->role_cache = 'unknown_role';

        $this->assertEquals('gray', $user->role_color);
    }

    #[Test]
    public function secteur_label_attribute(): void
    {
        $user = new User;
        $user->secteur = 'nord';
        $this->assertEquals('Nord', $user->secteur_label);

        $user->secteur = 'idf';
        $this->assertEquals('Île-de-France', $user->secteur_label);
    }

    #[Test]
    public function secteur_label_with_unknown_secteur(): void
    {
        $user = new User;
        $user->secteur = 'centre';
        $this->assertEquals('centre', $user->secteur_label);
    }

    #[Test]
    public function statut_label_attribute(): void
    {
        $user = new User;

        $user->actif = true;
        $this->assertEquals('Actif', $user->statut_label);

        $user->actif = false;
        $this->assertEquals('Inactif', $user->statut_label);
    }

    #[Test]
    public function google_connecte_attribute(): void
    {
        $user = new User;

        $user->google_token = null;
        $this->assertFalse($user->google_connecte);

        $user->google_token = ['access_token' => 'abc'];
        $this->assertTrue($user->google_connecte);
    }

    #[Test]
    public function has_role_cache(): void
    {
        $user = new User;
        $user->role_cache = User::ROLE_COMMERCIAL;

        $this->assertTrue($user->hasRoleCache(User::ROLE_COMMERCIAL));
        $this->assertFalse($user->hasRoleCache(User::ROLE_ADMIN));
    }

    #[Test]
    public function has_all_roles_cache(): void
    {
        $user = new User;
        $user->role_cache = User::ROLE_COMMERCIAL;

        $this->assertTrue($user->hasAllRolesCache([User::ROLE_COMMERCIAL, User::ROLE_ADMIN]));
        $this->assertFalse($user->hasAllRolesCache([User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]));
    }

    #[Test]
    public function role_helper_methods(): void
    {
        $user = new User;

        $user->role_cache = User::ROLE_COMMERCIAL;
        $this->assertTrue($user->isCommercial());
        $this->assertFalse($user->isTeleprospecteur());

        $user->role_cache = User::ROLE_TELEPROSPECTEUR;
        $this->assertTrue($user->isTeleprospecteur());
        $this->assertFalse($user->isCommercial());

        $user->role_cache = User::ROLE_OPERATEUR;
        $this->assertTrue($user->isOperateur());

        $user->role_cache = User::ROLE_BACK_OFFICE;
        $this->assertTrue($user->isBackOffice());

        $user->role_cache = User::ROLE_SUPERVISEUR;
        $this->assertTrue($user->isSuperviseur());
    }

    #[Test]
    public function roles_constant_contains_all_expected_roles(): void
    {
        $this->assertArrayHasKey(User::ROLE_SUPER_ADMIN, User::ROLES);
        $this->assertArrayHasKey(User::ROLE_ADMIN, User::ROLES);
        $this->assertArrayHasKey(User::ROLE_COMMERCIAL, User::ROLES);
        $this->assertArrayHasKey(User::ROLE_TELEPROSPECTEUR, User::ROLES);
        $this->assertArrayHasKey(User::ROLE_OPERATEUR, User::ROLES);
        $this->assertArrayHasKey(User::ROLE_BACK_OFFICE, User::ROLES);
        $this->assertArrayHasKey(User::ROLE_SUPERVISEUR, User::ROLES);
    }

    #[Test]
    public function secteurs_constant_contains_expected_values(): void
    {
        $this->assertArrayHasKey('nord', User::SECTEURS);
        $this->assertArrayHasKey('sud', User::SECTEURS);
        $this->assertArrayHasKey('est', User::SECTEURS);
        $this->assertArrayHasKey('ouest', User::SECTEURS);
        $this->assertArrayHasKey('idf', User::SECTEURS);
        $this->assertArrayHasKey('national', User::SECTEURS);
    }
}
