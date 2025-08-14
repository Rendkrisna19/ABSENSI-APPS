<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'role' => 'admin',
            'password' => Hash::make('admin123'), 
        ]);

       
        User::create([
            'name' => 'Budi Karyawan',
            'email' => 'karyawan@gmail.com',
            'role' => 'karyawan',
            'password' => Hash::make('karyawan123'), 
        ]);
    }
}