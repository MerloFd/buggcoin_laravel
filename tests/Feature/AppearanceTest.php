<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dark theme is the default', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<html lang="pt-BR"  class="dark">', false);
});

test('light theme is kept when chosen', function () {
    $this->withUnencryptedCookie('appearance', 'light')
        ->get(route('login'))
        ->assertOk()
        ->assertDontSee('class="dark"', false);
});
