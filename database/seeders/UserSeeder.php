<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'admin',
            'role' => 'admin' ,
            'login' => 'admin',
            'password' => app('hash')->make('1234567'),
        ]);
    }
}
