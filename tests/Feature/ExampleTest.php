<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home redirects to dashboard', function () {
    $this->get(route('home'))->assertRedirect('/dashboard');
});
