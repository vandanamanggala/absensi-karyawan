<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun Owner
        User::create([
            'name' => 'Owner Perusahaan',
            'email' => 'owner@perusahaan.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'company' => 'Eight Five Customs',
        ]);

        // Akun Karyawan Contoh
        User::create([
            'name' => 'Karyawan 1',
            'email' => 'karyawan@perusahaan.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'position' => 'Staff IT',
            'company' => 'Eight Five Customs',
            'join_date' => '2026-01-01',
        ]);
    }
}