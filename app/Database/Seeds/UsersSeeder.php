<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run()
    {
        // Waktu sekarang untuk created_at, updated_at, dan email_verified_at
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'username'             => 'admin',
                'email'                => 'admin@example.com',
                'password'             => password_hash('admin123', PASSWORD_DEFAULT),
                'first_name'           => 'System',
                'last_name'            => 'Administrator',
                'phone'                => null,
                'avatar'               => null,
                'role_id'              => 1, // pastikan role_id 1 adalah Admin
                'additional_permissions' => null,
                'is_active'            => 1,
                'email_verified_at'    => $now,
                'last_login'           => $now,
                'last_activity'        => $now,
                'login_attempts'       => 0,
                'locked_until'         => null,
                'reset_token'          => null,
                'reset_expires'        => null,
                'settings'             => json_encode([
                    'language' => 'en',
                    'timezone' => 'Asia/Jakarta',
                ]),
                'created_at'           => $now,
                'updated_at'           => $now,
                'deleted_at'           => null,
            ],
            [
                'username'             => 'tech',
                'email'                => 'tech@example.com',
                'password'             => password_hash('tech123', PASSWORD_DEFAULT),
                'first_name'           => 'Jane',
                'last_name'            => 'Technician',
                'phone'                => '081234567890',
                'avatar'               => null,
                'role_id'              => 2, // pastikan role_id 2 adalah Technician
                'additional_permissions' => null,
                'is_active'            => 1,
                'email_verified_at'    => $now,
                'last_login'           => null,
                'last_activity'        => null,
                'login_attempts'       => 0,
                'locked_until'         => null,
                'reset_token'          => null,
                'reset_expires'        => null,
                'settings'             => json_encode([
                    'language' => 'en',
                    'timezone' => 'Asia/Jakarta',
                ]),
                'created_at'           => $now,
                'updated_at'           => $now,
                'deleted_at'           => null,
            ],
            [
                'username'             => 'johndoe',
                'email'                => 'john.doe@example.com',
                'password'             => password_hash('password123', PASSWORD_DEFAULT),
                'first_name'           => 'John',
                'last_name'            => 'Doe',
                'phone'                => '081298765432',
                'avatar'               => null,
                'role_id'              => 3, // pastikan role_id 3 adalah Customer
                'additional_permissions' => null,
                'is_active'            => 1,
                'email_verified_at'    => $now,
                'last_login'           => null,
                'last_activity'        => null,
                'login_attempts'       => 0,
                'locked_until'         => null,
                'reset_token'          => null,
                'reset_expires'        => null,
                'settings'             => json_encode([
                    'language' => 'en',
                    'timezone' => 'Asia/Jakarta',
                ]),
                'created_at'           => $now,
                'updated_at'           => $now,
                'deleted_at'           => null,
            ],
        ];

        // Insert batch
        $this->db->table('users')->insertBatch($data);
    }
}
