<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => 'Admin1234',
                'role' => 'admin',
            ],
            [
                'name' => 'coordinador',
                'email' => 'coordinador@gmail.com',
                'password' => 'Coordinador1234',
                'role' => 'coordinador',
            ],
            [
                'name' => 'teacher',
                'email' => 'teacher@gmail.com',
                'password' => 'Teacher1234',
                'role' => 'teacher',
            ],
            [
                'name' => 'student',
                'email' => 'student@gmail.com',
                'password' => 'Student1234',
                'role' => 'student',
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
