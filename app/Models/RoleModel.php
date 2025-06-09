<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name', 'slug', 'description', 'level', 'permissions',
        'is_active', 'is_default'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[50]|is_unique[roles.name,id,{id}]',
        'slug' => 'required|min_length[3]|max_length[50]|is_unique[roles.slug,id,{id}]|alpha_dash',
        'level' => 'required|integer|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Role name is required',
            'is_unique' => 'Role name already exists'
        ],
        'slug' => [
            'required' => 'Role slug is required',
            'is_unique' => 'Role slug already exists',
            'alpha_dash' => 'Role slug can only contain letters, numbers, dashes and underscores'
        ]
    ];

    protected $skipValidation = false;

    // Cache for role data
    private static $roleCache = [];

    /**
     * Get role by slug with caching
     */
    public function findBySlug(string $slug): ?array
    {
        if (isset(self::$roleCache[$slug])) {
            return self::$roleCache[$slug];
        }

        $role = $this->where('slug', $slug)
            ->where('is_active', 1)
            ->first();

        if ($role) {
            // Load permissions for this role
            $role['permissions_list'] = $this->getRolePermissions($role['id']);
            self::$roleCache[$slug] = $role;
        }

        return $role;
    }

    /**
     * Get role with permissions
     */
    public function findWithPermissions(int $roleId): ?array
    {
        $role = $this->find($roleId);
        if (!$role) {
            return null;
        }

        $role['permissions_list'] = $this->getRolePermissions($roleId);
        return $role;
    }

    /**
     * Get all active roles
     */
    public function getActiveRoles(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('level', 'DESC')
            ->findAll();
    }

    /**
     * Get default role for new users
     */
    public function getDefaultRole(): ?array
    {
        return $this->where('is_default', 1)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get roles by level (for hierarchy checks)
     */
    public function getRolesByLevel(int $minLevel = 0): array
    {
        return $this->where('level >=', $minLevel)
            ->where('is_active', 1)
            ->orderBy('level', 'DESC')
            ->findAll();
    }

    /**
     * Get role permissions from pivot table
     */
    public function getRolePermissions(int $roleId): array
    {
        $db = \Config\Database::connect();

        return $db->table('role_permissions rp')
            ->select('p.slug, p.name, p.module, p.action, p.resource, rp.granted')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->where('p.is_active', 1)
            ->get()
            ->getResultArray();
    }

    /**
     * Check if role has specific permission
     */
    public function hasPermission(int $roleId, string $permissionSlug): bool
    {
        $db = \Config\Database::connect();

        $result = $db->table('role_permissions rp')
            ->select('rp.granted')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->where('p.slug', $permissionSlug)
            ->where('p.is_active', 1)
            ->get()
            ->getRow();

        return $result ? (bool)$result->granted : false;
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(int $roleId, int $permissionId, bool $granted = true): bool
    {
        $db = \Config\Database::connect();

        $data = [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'granted' => $granted ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Use replace to handle duplicates
        return $db->table('role_permissions')->replace($data);
    }

    /**
     * Remove permission from role
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        $db = \Config\Database::connect();

        return $db->table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    /**
     * Sync role permissions (replace all)
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $db = \Config\Database::connect();

        // Start transaction
        $db->transStart();

        // Remove existing permissions
        $db->table('role_permissions')
            ->where('role_id', $roleId)
            ->delete();

        // Add new permissions
        if (!empty($permissionIds)) {
            $data = [];
            foreach ($permissionIds as $permissionId) {
                $data[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'granted' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            $db->table('role_permissions')->insertBatch($data);
        }

        // Complete transaction
        $db->transComplete();

        // Clear cache
        self::$roleCache = [];

        return $db->transStatus();
    }

    /**
     * Create role with permissions
     */
    public function createWithPermissions(array $roleData, array $permissionIds = []): int|false
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // Create role
        $roleId = $this->insert($roleData);

        if ($roleId && !empty($permissionIds)) {
            // Assign permissions
            $permissionData = [];
            foreach ($permissionIds as $permissionId) {
                $permissionData[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'granted' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            $db->table('role_permissions')->insertBatch($permissionData);
        }

        $db->transComplete();

        if ($db->transStatus()) {
            // Clear cache
            self::$roleCache = [];
            return $roleId;
        }

        return false;
    }

    /**
     * Get role hierarchy (for UI dropdowns)
     */
    public function getRoleHierarchy(): array
    {
        $roles = $this->getActiveRoles();
        $hierarchy = [];

        foreach ($roles as $role) {
            $hierarchy[] = [
                'id' => $role['id'],
                'name' => $role['name'],
                'level' => $role['level'],
                'description' => $role['description'],
                'permissions_count' => $this->countRolePermissions($role['id'])
            ];
        }

        return $hierarchy;
    }

    /**
     * Count permissions for a role
     */
    private function countRolePermissions(int $roleId): int
    {
        $db = \Config\Database::connect();

        return $db->table('role_permissions rp')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->where('rp.granted', 1)
            ->where('p.is_active', 1)
            ->countAllResults();
    }

    /**
     * Clear role cache
     */
    public static function clearCache(): void
    {
        self::$roleCache = [];
    }

    /**
     * Check if role can be deleted
     */
    public function canDelete(int $roleId): array
    {
        $userCount = model('UserModel')->where('role_id', $roleId)->countAllResults();

        return [
            'can_delete' => $userCount === 0,
            'user_count' => $userCount,
            'message' => $userCount > 0
                ? "Cannot delete role. {$userCount} users are assigned to this role."
                : 'Role can be safely deleted.'
        ];
    }
}