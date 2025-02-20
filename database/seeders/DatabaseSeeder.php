<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['admin', 'docente', 'director', 'decano'];
        
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@localhost',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ],
        ];

        for ($i = 1; $i <= 9; $i++) {
            $users[] = [
                'name' => 'Usuario' . $i,
                'email' => 'usuario' . $i . '@localhost',
                'password' => Hash::make('password123'),
                'role' => $roles[array_rand($roles)],
            ];
        }

        DB::table('users')->insert($users);
    }
}
