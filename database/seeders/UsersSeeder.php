<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['login' => 'admin'],
            [
                'name' => 'admin',
                'role' => User::ROLE_ADMIN,
                'password' => '1234567',
            ],
        );
    }
}
