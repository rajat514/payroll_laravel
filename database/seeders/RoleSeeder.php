<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // public function run(): void
    // {
    //     $roles = [
    //         ['name' => 'Admin'],
    //         ['name' => 'Administrative Officer'],
    //         ['name' => 'Account Officer'],
    //         ['name' => 'Coordinator(NIOH)'],
    //         ['name' => 'Coordinator(ROHC)'],
    //         ['name' => 'Pensioner Operator'],
    //         ['name' => 'User'],
    //     ];

    //     foreach ($roles as $role)
    //         \Spatie\Permission\Models\Role::create($role);


    //     \App\Models\User::find(1)->assignRole('Admin');
    // }

    public function run()
    {
        $roles = [
            ['name' => 'IT Admin', 'guard_name' => 'web'],
            ['name' => 'Director', 'guard_name' => 'web'], // Same for both NIOH ROHC
            ['name' => 'Senior AO', 'guard_name' => 'web'],
            ['name' => 'Administrative Officer', 'guard_name' => 'web'], // Same for both NIOH ROHC
            ['name' => 'Drawing and Disbursing Officer (NIOH)', 'guard_name' => 'web'],
            ['name' => 'Drawing and Disbursing Officer (ROHC)', 'guard_name' => 'web'],
            ['name' => 'Section Officer (Accounts)', 'guard_name' => 'web'], // for regular salary and pension
            ['name' => 'Accounts Officer', 'guard_name' => 'web'], // Same for both NIOH ROHC
            ['name' => 'Salary Processing Coordinator (NIOH)', 'guard_name' => 'web'],
            ['name' => 'Salary Processing Coordinator (ROHC)', 'guard_name' => 'web'],
            ['name' => 'Pensioners Operator', 'guard_name' => 'web'],
            ['name' => 'End Users', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => $role['guard_name']]
            );
        }

        // Assign role to first user if it exists
        $user = \App\Models\User::find(1);
        if ($user) {
            $user->assignRole('IT Admin');
        }
    }
}
