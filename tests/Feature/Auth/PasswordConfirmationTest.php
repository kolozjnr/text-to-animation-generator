<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/user/confirm-password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ConfirmPassword'));
    }

    public function test_password_can_be_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/user/confirm-password', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    public function test_password_confirmation_fails_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/user/confirm-password')
            ->post('/user/confirm-password', ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');
    }
}
