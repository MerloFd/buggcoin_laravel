<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class OwnerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_twice_creates_a_single_owner()
    {
        config()->set('app.owner', [
            'name' => 'Owner',
            'email' => 'Owner@Example.com',
            'password' => 'secret-password',
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->count());

        $owner = User::query()->firstOrFail();
        $this->assertSame('owner@example.com', $owner->email);
        $this->assertTrue(Hash::check('secret-password', $owner->password));
        $this->assertNotNull($owner->email_verified_at);
    }

    public function test_seeding_without_owner_env_fails()
    {
        config()->set('app.owner', ['name' => null, 'email' => null, 'password' => null]);

        $this->expectException(RuntimeException::class);

        $this->seed(DatabaseSeeder::class);
    }
}
