<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_speaks_pt_br()
    {
        $this->assertSame('pt_BR', app()->getLocale());
    }

    public function test_wrong_password_message_is_in_pt_br()
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
    }

    public function test_validation_message_is_in_pt_br()
    {
        $this->post(route('login.store'), ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'O campo e-mail é obrigatório.']);
    }
}
