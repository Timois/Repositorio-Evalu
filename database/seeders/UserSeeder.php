<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

use function Laravel\Prompts\password;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Lista de usuarios
        $users = [
            [
                'name' => 'Administrador',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin'
            ],
            [
                'name' => 'Super Administrador',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'super-admin'
            ],
            [
                'name' => 'Juan Perez',
                'email' => 'usuario@example.com',
                'password' => Hash::make('password123'),
                'role' => ['docente']
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password']
                ]
            );

            // Asignar rol al usuario
            $user->assignRole($userData['role']);
        }

        $this->command->info('✅ Usuarios creados correctamente.');
    }
    
}
