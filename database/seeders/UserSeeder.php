<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'administador',
            'email' => 'correofalso123@gmail.com',
            'password' => Hash::make('123456'),
            'email_verified_at' => now(),
        ]);
    }
}
