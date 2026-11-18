<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Create (or update) the single owner account from OWNER_* in .env.
     */
    public function run(): void
    {
        $name = config('app.owner.name');
        $email = config('app.owner.email');
        $password = config('app.owner.password');

        if (! is_string($name) || $name === '' || ! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException('Set OWNER_NAME, OWNER_EMAIL and OWNER_PASSWORD in .env before seeding.');
        }

        User::query()->updateOrCreate(
            ['email' => strtolower($email)],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
