<?php

namespace Tests\Feature;

use App\Http\Livewire\AppShell;
use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppShellTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedListener(): User
    {
        $user = User::create([
            'name' => 'Ouvinte Shell',
            'email' => 'shell@example.com',
        ]);

        SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => 'spotify-shell-user',
            'display_name' => 'Ouvinte Shell',
            'email' => 'shell@example.com',
            'product_type' => 'premium',
            'access_token' => 'valid-token',
            'refresh_token' => 'valid-refresh',
            'token_expires_at' => now()->addHour(),
            'scopes' => 'streaming',
        ]);

        return $user;
    }

    public function test_player_route_renders_app_shell_with_wire_ignore_footer_and_default_preferences(): void
    {
        $user = $this->createAuthenticatedListener();

        $response = $this->actingAs($user)
            ->get('http://127.0.0.1:8000/player');

        $response->assertOk();
        $response->assertSeeLivewire('app-shell');
        $response->assertSee('id="global-player-bar"', false);
        $response->assertSee('wire:ignore', false);
        $response->assertSee('Ouvinte Shell');
    }

    public function test_app_shell_switches_views_and_syncs_context_without_page_reload(): void
    {
        $user = $this->createAuthenticatedListener();

        $this->actingAs($user);

        Livewire::test(AppShell::class)
            ->assertSet('activeView', 'home')
            ->assertSee('Início')
            ->call('navigateTo', 'search')
            ->assertSet('activeView', 'search')
            ->assertSee('Pesquisar no catálogo')
            ->call('navigateTo', 'favorites')
            ->assertSet('activeView', 'favorites')
            ->assertSee('Faixas Favoritas')
            ->call('navigateTo', 'playlist', '42')
            ->assertSet('activeView', 'playlist')
            ->assertSet('contextId', '42')
            ->call('navigateTo', 'invalid-section')
            ->assertSet('activeView', 'home');
    }
}

