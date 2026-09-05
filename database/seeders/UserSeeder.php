<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@ippt.test',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Pemohon Demo',
                'email' => 'pemohon@ippt.test',
                'role' => UserRole::PEMOHON,
            ],
            [
                'name' => 'Staff IPPT',
                'email' => 'staff@ippt.test',
                'role' => UserRole::STAFF,
            ],
            [
                'name' => 'Tim Teknis IPPT',
                'email' => 'teknis@ippt.test',
                'role' => UserRole::TIM_TEKNIS,
            ],
            [
                'name' => 'Kepala Bidang',
                'email' => 'kabid@ippt.test',
                'role' => UserRole::KABID,
            ],
            [
                'name' => 'Kepala Dinas',
                'email' => 'kadis@ippt.test',
                'role' => UserRole::KADIS,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                ],
            );
        }
    }
}
