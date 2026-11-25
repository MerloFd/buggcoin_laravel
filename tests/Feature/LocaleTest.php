<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('app speaks pt br', function () {
    expect(app()->getLocale())->toBe('pt_BR');
});

test('wrong password message is in pt br', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
});

test('validation message is in pt br', function () {
    $this->post(route('login.store'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors(['email' => 'O campo e-mail é obrigatório.']);
});
