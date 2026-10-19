<?php

namespace Tests\Feature;

use Tests\TestCase;

class InertiaWelcomeTest extends TestCase
{
    public function test_the_welcome_page_renders_via_inertia(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Welcome'));
    }
}
