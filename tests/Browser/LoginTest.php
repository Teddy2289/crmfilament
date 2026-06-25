<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test de connexion utilisateur Filament
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/admin/login')
                ->type('email', $user->email)
                ->type('password', 'password')
                ->press('Se connecter')
                ->assertPathIs('/admin')
                ->assertSee('Tableau de bord');
        });
    }

    /**
     * Test d'échec de connexion avec mauvais mot de passe
     */
    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/admin/login')
                ->type('email', $user->email)
                ->type('password', 'wrong-password')
                ->press('Se connecter')
                ->assertPathIs('/admin/login')
                ->assertSee('Ces identifiants ne correspondent pas');
        });
    }

    /**
     * Test de déconnexion
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin')
                ->click('button[aria-label="User menu"]')
                ->click('button:contains("Se déconnecter")')
                ->assertPathIs('/admin/login');
        });
    }
}
