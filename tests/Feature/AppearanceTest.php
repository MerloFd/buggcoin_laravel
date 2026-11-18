<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dark_theme_is_the_default()
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="pt-BR"  class="dark">', false);
    }

    public function test_light_theme_is_kept_when_chosen()
    {
        $this->withUnencryptedCookie('appearance', 'light')
            ->get(route('login'))
            ->assertOk()
            ->assertDontSee('class="dark"', false);
    }
}
