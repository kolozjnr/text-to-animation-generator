<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_redirects_to_login_without_a_challenged_user(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect('/login');
    }

    public function test_challenge_screen_can_be_rendered_after_valid_credentials(): void
    {
        $user = $this->twoFactorUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/two-factor-challenge');

        $this->get('/two-factor-challenge')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/TwoFactorChallenge'));
    }

    public function test_an_invalid_code_is_rejected(): void
    {
        $user = $this->twoFactorUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/two-factor-challenge');

        $this->post('/two-factor-challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    private function twoFactorUser(): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }
}
