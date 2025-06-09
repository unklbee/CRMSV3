<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionsSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Define role-permission assignments
        $assignments = [
            'admin'      => [
                'users.manage', 'users.read', 'users.create', 'users.update', 'users.delete',
                'roles.manage',
                'orders.manage.all', 'orders.manage.assigned', 'orders.read.own',
                'services.manage.all', 'services.create',
                'tickets.manage.all', 'tickets.create',
                'reports.read', 'reports.export',
                'settings.manage',
                'audit.read',
                'dashboard.admin', 'dashboard.technician', 'dashboard.customer',
                'profile.manage.own',
            ],
            'manager'    => [
                'users.read',
                'orders.manage.all', 'orders.read.own',
                'services.manage.all',
                'tickets.manage.all',
                'reports.read', 'reports.export',
                'dashboard.admin', 'dashboard.technician',
                'profile.manage.own',
            ],
            'technician' => [
                'orders.manage.assigned', 'orders.read.own',
                'services.create',
                'tickets.create',
                'dashboard.technician',
                'profile.manage.own',
            ],
            'support'    => [
                'tickets.manage.all', 'tickets.create',
                'services.create',
                'orders.read.own',
                'dashboard.technician',
                'profile.manage.own',
            ],
            'customer'   => [
                'services.create',
                'tickets.create',
                'orders.read.own',
                'dashboard.customer',
                'profile.manage.own',
            ],
        ];

        // Ambil semua roles (slug → id)
        $roleRows = $this->db->table('roles')
            ->select('id, slug')
            ->get()
            ->getResult();
        $roles = [];
        foreach ($roleRows as $r) {
            $roles[$r->slug] = $r->id;
        }

        // Ambil semua permissions (slug → id)
        $permRows = $this->db->table('permissions')
            ->select('id, slug')
            ->get()
            ->getResult();
        $perms = [];
        foreach ($permRows as $p) {
            $perms[$p->slug] = $p->id;
        }

        // Siapkan batch data untuk insert
        $batch = [];
        foreach ($assignments as $roleSlug => $permSlugs) {
            if (! isset($roles[$roleSlug])) {
                // Kalau role belum ada, skip
                continue;
            }
            $roleId = $roles[$roleSlug];
            foreach ($permSlugs as $slug) {
                if (! isset($perms[$slug])) {
                    // Kalau permission belum ada, skip
                    continue;
                }
                $batch[] = [
                    'role_id'       => $roleId,
                    'permission_id' => $perms[$slug],
                    'granted'       => 1,
                    'conditions'    => null,
                    'created_at'    => $now,
                ];
            }
        }

        if (! empty($batch)) {
            // Batch insert ke tabel 'role_permissions'
            $this->db->table('role_permissions')->insertBatch($batch);
        }
    }
}
