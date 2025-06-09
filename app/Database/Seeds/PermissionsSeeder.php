<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Daftar slug => deskripsi singkat
        $permissions = [
            'users.manage'           => 'Allow managing all users',
            'users.read'             => 'Allow reading user data',
            'users.create'           => 'Allow creating new users',
            'users.update'           => 'Allow updating existing users',
            'users.delete'           => 'Allow deleting users',
            'roles.manage'           => 'Allow managing roles',
            'orders.manage.all'      => 'Allow managing all orders',
            'orders.manage.assigned' => 'Allow managing only assigned orders',
            'orders.read.own'        => 'Allow reading own orders',
            'services.manage.all'    => 'Allow managing all services',
            'services.create'        => 'Allow creating new services',
            'tickets.manage.all'     => 'Allow managing all tickets',
            'tickets.create'         => 'Allow creating new tickets',
            'reports.read'           => 'Allow reading reports',
            'reports.export'         => 'Allow exporting reports',
            'settings.manage'        => 'Allow managing system settings',
            'audit.read'             => 'Allow reading audit logs',
            'dashboard.admin'        => 'Access admin dashboard',
            'dashboard.technician'   => 'Access technician dashboard',
            'dashboard.customer'     => 'Access customer dashboard',
            'profile.manage.own'     => 'Allow managing own profile',
        ];

        $batch = [];
        foreach ($permissions as $slug => $desc) {
            $parts  = explode('.', $slug);
            $module = $parts[0];
            $action = $parts[1];
            $resource = isset($parts[2]) ? $parts[2] : null;

            $batch[] = [
                'name'        => ucfirst($module) . ' ' . ucfirst($action) . ($resource ? ' ' . ucfirst($resource) : ''),
                'slug'        => $slug,
                'description' => $desc,
                'module'      => $module,
                'action'      => $action,
                'resource'    => $resource,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        // Insert batch ke tabel 'permissions'
        $this->db->table('permissions')->insertBatch($batch);
    }
}
