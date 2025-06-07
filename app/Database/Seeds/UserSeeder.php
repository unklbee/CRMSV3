<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'username' => 'admin',
                'email' => 'admin@repairshop.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'phone' => '+62-21-1234567',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'username' => 'hasbi',
                'email' => 'ashshiddiqihasbi@gmail.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'first_name' => 'Hasbi',
                'last_name' => 'Ash Shiddiqi',
                'phone' => '+62-21-1234567',
                'role' => 'technician',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}