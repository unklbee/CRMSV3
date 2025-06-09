<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name'        => 'Super Administrator',
                'slug'        => 'admin',
                'description' => 'Full system access with all permissions',
                'level'       => 100,
                'permissions' => null,
                'is_active'   => 1,
                'is_default'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Technician',
                'slug'        => 'technician',
                'description' => 'Technical staff with work management access',
                'level'       => 50,
                'permissions' => null,
                'is_active'   => 1,
                'is_default'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Customer',
                'slug'        => 'customer',
                'description' => 'Customer with limited access to own data',
                'level'       => 10,
                'permissions' => null,
                'is_active'   => 1,
                'is_default'  => 1, // Default role for new users
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Manager',
                'slug'        => 'manager',
                'description' => 'Management level with reporting access',
                'level'       => 75,
                'permissions' => null,
                'is_active'   => 1,
                'is_default'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Support Staff',
                'slug'        => 'support',
                'description' => 'Support staff with ticket management access',
                'level'       => 30,
                'permissions' => null,
                'is_active'   => 1,
                'is_default'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        // Insert batch ke tabel 'roles'
        $this->db->table('roles')->insertBatch($data);
    }
}
