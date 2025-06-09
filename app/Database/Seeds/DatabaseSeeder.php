<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('RolesSeeder');
        $this->call('PermissionsSeeder');
        $this->call('RolePermissionsSeeder');
        $this->call('UsersSeeder');
    }
}