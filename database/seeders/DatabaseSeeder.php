<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Role::insert([
            ['name' => 'Admin'],
            ['name' => 'Account Officer'],
            ['name' => 'Coordinator'],
            ['name' => 'Pensioner Operator'],
            ['name' => 'User'],
        ]);

        \App\Models\User::create([
            'role_id' => 1,
            'name' => 'Admin',
            'email' => 'admin@nioh.com',
            'password' => \Illuminate\Support\Facades\Hash::make('123456')
        ]);
    }
}
