<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticatedNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{path: string, component: string, route: string}>
     */
    public static function authenticatedPages(): array
    {
        return [
            'dashboard' => ['path' => '/dashboard', 'component' => 'Dashboard', 'route' => 'dashboard'],
            'convert' => ['path' => '/convert', 'component' => 'Convert', 'route' => 'convert'],
            'lessons' => ['path' => '/lessons', 'component' => 'Lessons/Index', 'route' => 'lessons.index'],
            'profile' => ['path' => '/profile', 'component' => 'Profile', 'route' => 'profile'],
        ];
    }

    /**
     * @return array<string, array{path: string}>
     */
    public static function protectedPaths(): array
    {
        return [
            'dashboard' => ['path' => '/dashboard'],
            'convert' => ['path' => '/convert'],
            'lessons' => ['path' => '/lessons'],
            'profile' => ['path' => '/profile'],
        ];
    }

    #[DataProvider('protectedPaths')]
    public function test_guests_are_redirected_from_protected_pages(string $path): void
    {
        $this->get($path)->assertRedirect('/login');
    }

    #[DataProvider('authenticatedPages')]
    public function test_authenticated_users_can_view_each_page(string $path, string $component, string $route): void
    {
        $user = User::factory()->create();

        $this->assertSame(url($path), route($route));

        $this->actingAs($user)
            ->get($path)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
            );
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
