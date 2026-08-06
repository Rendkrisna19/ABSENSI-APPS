<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Kost',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '08123456789',
            ]
        );

        // User biasa
        User::updateOrCreate(
            ['email' => 'muhammadrendykrisna@gmail.com'],
            [
                'name' => 'User Kost',
                'password' => Hash::make('12345678'),
                'role' => 'user',
                'phone' => '081222333444',
            ]
        );
    }
}

