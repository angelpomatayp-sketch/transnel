<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@logistica.test');
        $password = env('ADMIN_PASSWORD');

        if (app()->environment('production') && ! $password) {
            throw new RuntimeException('Define ADMIN_PASSWORD en el .env antes de ejecutar seeders en produccion.');
        }

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador',
                'password' => Hash::make($password ?: 'password'),
                'dni' => '00000000',
                'telefono' => '+51 999 000 000',
                'activo' => true,
            ],
        );

        $admin->assignRole('administrador');
    }
}
