<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name', 'slug', 'description', 'module', 'action', 'resource', 'is_active'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[100]|is_unique[permissions.name,id,{id}]',
        'slug' => 'required|min_length[3]|max_length[100]|is_unique[permissions.slug,id,{id}]|alpha_dash',
        'module' => 'required|min_length[2]|max_length[50]|alpha_dash',
        'action' => 'required|in_list[create,read,update,delete,manage,view,edit,assign,approve,export]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Permission name is required',
            'is_unique' => 'Permission name already exists'
        ],
        'slug' => [
            'required' => 'Permission slug is required',
            'is_unique' => 'Permission slug already exists'
        ],
        'action' => [
            'in_list' => 'Invalid action type'
        ]
    ];

    // Available modules in the system
    const MODULES = [
        'users' => 'User Management',
        'roles' => 'Role Management',
        'orders' => 'Work Orders',
        'services' => 'Services',
        'tickets' => 'Support Tickets',
        'reports' => 'Reports & Analytics',
        'settings' => 'System Settings',
        'audit' => 'Audit Logs',
        'maintenance' => 'System Maintenance',
        'dashboard' => 'Dashboard',
        'profile' => 'Profile Management',
        'notifications' => 'Notifications',
        'schedule' => 'Schedule Management',
        'tasks' => 'Task Management',
        'billing' => 'Billing & Payments',
        'documents' => 'Document Management'
    ];

    // Available actions
    const ACTIONS = [
        'create' => 'Create new records',
        'read' => 'View/read records',
        'update' => 'Update existing records',
        'delete' => 'Delete records',
        'manage' => 'Full management access',
        'view' => 'View only (alias for read)',
        'edit' => 'Edit only (alias for update)',
        'assign' => 'Assign to others',
        'approve' => 'Approve/reject actions',
        'export' => 'Export data'
    ];

    // Resource types
    const RESOURCES = [
        'own' => 'Own records only',
        'all' => 'All records',
        'assigned' => 'Assigned records only',
        'department' => 'Department records only'
    ];

    /**
     * Get permissions grouped by module
     */
    public function getGroupedPermissions(): array
    {
        $permissions = $this->where('is_active', 1)
            ->orderBy('module', 'ASC')
            ->orderBy('action', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [
                    'name' => self::MODULES[$module] ?? ucfirst($module),
                    'permissions' => []
                ];
            }
            $grouped[$module]['permissions'][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get permissions by module
     */
    public function getByModule(string $module): array
    {
        return $this->where('module', $module)
            ->where('is_active', 1)
            ->orderBy('action', 'ASC')
            ->findAll();
    }

    /**
     * Find permission by slug
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Create permission with auto-generated slug
     */
    public function createPermission(array $data): int|false
    {
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['module'], $data['action'], $data['resource'] ?? null);
        }

        return $this->insert($data);
    }

    /**
     * Generate permission slug
     */
    private function generateSlug(string $module, string $action, ?string $resource = null): string
    {
        $slug = "{$module}.{$action}";
        if ($resource) {
            $slug .= ".{$resource}";
        }
        return strtolower($slug);
    }

    /**
     * Bulk create permissions for a module
     */
    public function createModulePermissions(string $module, array $actions, ?string $resource = null): bool
    {
        $permissions = [];
        $moduleName = self::MODULES[$module] ?? ucfirst($module);

        foreach ($actions as $action) {
            $actionName = self::ACTIONS[$action] ?? ucfirst($action);
            $name = "{$actionName} {$moduleName}";
            if ($resource) {
                $name .= " ({$resource})";
            }

            $permissions[] = [
                'name' => $name,
                'slug' => $this->generateSlug($module, $action, $resource),
                'description' => "Permission to {$actionName} in {$moduleName}",
                'module' => $module,
                'action' => $action,
                'resource' => $resource,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        return $this->insertBatch($permissions) !== false;
    }

    /**
     * Get permissions for dropdown/select
     */
    public function getForSelect(): array
    {
        $permissions = $this->select('id, name, module')
            ->where('is_active', 1)
            ->orderBy('module', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $options = [];
        $currentModule = '';

        foreach ($permissions as $permission) {
            if ($permission['module'] !== $currentModule) {
                $currentModule = $permission['module'];
                $moduleName = self::MODULES[$currentModule] ?? ucfirst($currentModule);
                $options[$moduleName] = [];
            }

            $moduleName = self::MODULES[$currentModule] ?? ucfirst($currentModule);
            $options[$moduleName][$permission['id']] = $permission['name'];
        }

        return $options;
    }

    /**
     * Get roles that have specific permission
     */
    public function getRolesWithPermission(int $permissionId): array
    {
        $db = \Config\Database::connect();

        return $db->table('role_permissions rp')
            ->select('r.id, r.name, r.slug, rp.granted')
            ->join('roles r', 'r.id = rp.role_id')
            ->where('r.is_active', 1)
            ->orderBy('r.level', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Check if permission can be deleted
     */
    public function canDelete(int $permissionId): array
    {
        $roleCount = \Config\Database::connect()
            ->table('role_permissions')
            ->where('permission_id', $permissionId)
            ->countAllResults();

        return [
            'can_delete' => $roleCount === 0,
            'role_count' => $roleCount,
            'message' => $roleCount > 0
                ? "Cannot delete permission. {$roleCount} roles have this permission."
                : 'Permission can be safely deleted.'
        ];
    }

    /**
     * Get permission statistics
     */
    public function getStats(): array
    {
        $db = \Config\Database::connect();

        return [
            'total' => $this->countAll(),
            'active' => $this->where('is_active', 1)->countAllResults(),
            'by_module' => $this->getPermissionsByModule(),
            'assigned' => $db->table('role_permissions')
                ->select('permission_id')
                ->distinct()
                ->countAllResults(),
            'unassigned' => $this->getUnassignedCount()
        ];
    }

    /**
     * Get permissions count by module
     */
    private function getPermissionsByModule(): array
    {
        return $this->select('module, COUNT(*) as count')
            ->where('is_active', 1)
            ->groupBy('module')
            ->orderBy('count', 'DESC')
            ->findAll();
    }

    /**
     * Get count of unassigned permissions
     */
    private function getUnassignedCount(): int
    {
        $db = \Config\Database::connect();

        $assignedIds = $db->table('role_permissions')
            ->select('permission_id')
            ->distinct()
            ->get()
            ->getResultArray();

        $assignedIdsList = array_column($assignedIds, 'permission_id');

        $query = $this->where('is_active', 1);
        if (!empty($assignedIdsList)) {
            $query->whereNotIn('id', $assignedIdsList);
        }

        return $query->countAllResults();
    }

    /**
     * Search permissions
     */
    public function search(string $term, array $filters = []): array
    {
        $builder = $this->select('*');

        // Search in name, slug, description
        if (!empty($term)) {
            $builder->groupStart()
                ->like('name', $term)
                ->orLike('slug', $term)
                ->orLike('description', $term)
                ->groupEnd();
        }

        // Apply filters
        if (!empty($filters['module'])) {
            $builder->where('module', $filters['module']);
        }

        if (!empty($filters['action'])) {
            $builder->where('action', $filters['action']);
        }

        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        return $builder->orderBy('module', 'ASC')
            ->orderBy('action', 'ASC')
            ->findAll();
    }

    /**
     * Initialize default permissions for the system
     */
    public function initializeDefaultPermissions(): bool
    {
        $defaultPermissions = [
            // User Management
            [
                'name' => 'Manage Users',
                'slug' => 'users.manage',
                'description' => 'Full user management access',
                'module' => 'users',
                'action' => 'manage',
                'resource' => 'all'
            ],
            [
                'name' => 'View Users',
                'slug' => 'users.read',
                'description' => 'View user information',
                'module' => 'users',
                'action' => 'read',
                'resource' => 'all'
            ],
            [
                'name' => 'Create Users',
                'slug' => 'users.create',
                'description' => 'Create new users',
                'module' => 'users',
                'action' => 'create',
                'resource' => 'all'
            ],
            [
                'name' => 'Edit Users',
                'slug' => 'users.update',
                'description' => 'Update user information',
                'module' => 'users',
                'action' => 'update',
                'resource' => 'all'
            ],
            [
                'name' => 'Delete Users',
                'slug' => 'users.delete',
                'description' => 'Delete users',
                'module' => 'users',
                'action' => 'delete',
                'resource' => 'all'
            ],

            // Role Management
            [
                'name' => 'Manage Roles',
                'slug' => 'roles.manage',
                'description' => 'Full role management access',
                'module' => 'roles',
                'action' => 'manage',
                'resource' => 'all'
            ],

            // Work Orders
            [
                'name' => 'Manage All Orders',
                'slug' => 'orders.manage.all',
                'description' => 'Manage all work orders',
                'module' => 'orders',
                'action' => 'manage',
                'resource' => 'all'
            ],
            [
                'name' => 'Manage Assigned Orders',
                'slug' => 'orders.manage.assigned',
                'description' => 'Manage assigned work orders only',
                'module' => 'orders',
                'action' => 'manage',
                'resource' => 'assigned'
            ],
            [
                'name' => 'View Own Orders',
                'slug' => 'orders.read.own',
                'description' => 'View own orders only',
                'module' => 'orders',
                'action' => 'read',
                'resource' => 'own'
            ],

            // Services
            [
                'name' => 'Request Services',
                'slug' => 'services.create',
                'description' => 'Request new services',
                'module' => 'services',
                'action' => 'create',
                'resource' => 'own'
            ],
            [
                'name' => 'Manage All Services',
                'slug' => 'services.manage.all',
                'description' => 'Manage all services',
                'module' => 'services',
                'action' => 'manage',
                'resource' => 'all'
            ],

            // Support Tickets
            [
                'name' => 'Create Support Tickets',
                'slug' => 'tickets.create',
                'description' => 'Create support tickets',
                'module' => 'tickets',
                'action' => 'create',
                'resource' => 'own'
            ],
            [
                'name' => 'Manage All Tickets',
                'slug' => 'tickets.manage.all',
                'description' => 'Manage all support tickets',
                'module' => 'tickets',
                'action' => 'manage',
                'resource' => 'all'
            ],

            // Reports
            [
                'name' => 'View Reports',
                'slug' => 'reports.read',
                'description' => 'View system reports',
                'module' => 'reports',
                'action' => 'read',
                'resource' => 'all'
            ],
            [
                'name' => 'Export Reports',
                'slug' => 'reports.export',
                'description' => 'Export reports data',
                'module' => 'reports',
                'action' => 'export',
                'resource' => 'all'
            ],

            // System Settings
            [
                'name' => 'Manage Settings',
                'slug' => 'settings.manage',
                'description' => 'Manage system settings',
                'module' => 'settings',
                'action' => 'manage',
                'resource' => 'all'
            ],

            // Audit Logs
            [
                'name' => 'View Audit Logs',
                'slug' => 'audit.read',
                'description' => 'View audit logs',
                'module' => 'audit',
                'action' => 'read',
                'resource' => 'all'
            ],

            // Dashboard
            [
                'name' => 'Access Admin Dashboard',
                'slug' => 'dashboard.admin',
                'description' => 'Access admin dashboard',
                'module' => 'dashboard',
                'action' => 'view',
                'resource' => 'all'
            ],
            [
                'name' => 'Access Technician Dashboard',
                'slug' => 'dashboard.technician',
                'description' => 'Access technician dashboard',
                'module' => 'dashboard',
                'action' => 'view',
                'resource' => 'assigned'
            ],
            [
                'name' => 'Access Customer Dashboard',
                'slug' => 'dashboard.customer',
                'description' => 'Access customer dashboard',
                'module' => 'dashboard',
                'action' => 'view',
                'resource' => 'own'
            ],

            // Profile Management
            [
                'name' => 'Manage Own Profile',
                'slug' => 'profile.manage.own',
                'description' => 'Manage own profile',
                'module' => 'profile',
                'action' => 'manage',
                'resource' => 'own'
            ]
        ];

        // Add timestamps
        $now = date('Y-m-d H:i:s');
        foreach ($defaultPermissions as &$permission) {
            $permission['is_active'] = 1;
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
        }

        return $this->insertBatch($defaultPermissions) !== false;
    }
}