<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('seeding twice creates a single owner', function () {
    config()->set('app.owner', [
        'name' => 'Owner',
        'email' => 'Owner@Example.com',
        'password' => 'secret-password',
    ]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(1);

    $owner = User::query()->firstOrFail();
    expect($owner->email)->toBe('owner@example.com');
    expect(Hash::check('secret-password', $owner->password))->toBeTrue();
    expect($owner->email_verified_at)->not->toBeNull();
});

test('seeding without owner env fails', function () {
    config()->set('app.owner', ['name' => null, 'email' => null, 'password' => null]);

    $this->expectException(RuntimeException::class);

    $this->seed(DatabaseSeeder::class);
});
