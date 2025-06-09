<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name', 'slug', 'description', 'level', 'permissions',
        'is_active', 'is_default'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[50]|is_unique[roles.name,id,{id}]',
        'slug' => 'required|min_length[3]|max_length[50]|is_unique[roles.slug,id,{id}]|alpha_dash',
        'level' => 'required|integer|greater_than_equal_to[0]'
    ];

    protected $validationMessages = [
        'name' => [
            'is_unique' => 'Role name already exists'
        ],
        'slug' => [
            'is_unique' => 'Role slug already exists',
            'alpha_dash' => 'Role slug can only contain letters, numbers, dashes and underscores'
        ]
    ];

    // Cache for role data
    private static $roleCache = [];

    /**
     * Get default role for new users
     */
    public function getDefaultRole(): ?array
    {
        if (isset(self::$roleCache['default'])) {
            return self::$roleCache['default'];
        }

        $role = $this->where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        self::$roleCache['default'] = $role;
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
     * Get roles for dropdown/select options
     */
    public function getRoleOptions(): array
    {
        $roles = $this->select('id, name, slug, level')
            ->where('is_active', 1)
            ->orderBy('level', 'DESC')
            ->findAll();

        $options = [];
        foreach ($roles as $role) {
            $options[$role['id']] = $role['name'] . ' (Level ' . $role['level'] . ')';
        }

        return $options;
    }

    /**
     * Get role with permissions
     */
    public function getRoleWithPermissions(int $roleId): ?array
    {
        $cacheKey = 'role_permissions_' . $roleId;

        if (isset(self::$roleCache[$cacheKey])) {
            return self::$roleCache[$cacheKey];
        }

        $role = $this->find($roleId);
        if (!$role) {
            return null;
        }

        // Get role permissions
        $db = \Config\Database::connect();
        $permissions = $db->table('role_permissions rp')
            ->select('p.id, p.slug, p.name, p.module, p.action, p.resource, rp.granted')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->where('p.is_active', 1)
            ->orderBy('p.module, p.action')
            ->get()
            ->getResultArray();

        $role['permissions'] = $permissions;
        self::$roleCache[$cacheKey] = $role;

        return $role;
    }

    /**
     * Set default role (only one can be default)
     */
    public function setAsDefault(int $roleId): bool
    {
        // First, remove default from all roles
        $this->set('is_default', 0)->update();

        // Then set the specified role as default
        $result = $this->update($roleId, ['is_default' => 1]);

        if ($result) {
            // Clear cache
            self::$roleCache = [];
        }

        return $result;
    }

    /**
     * Check if role can be deleted
     */
    public function canDelete(int $roleId): array
    {
        $role = $this->find($roleId);
        if (!$role) {
            return [
                'can_delete' => false,
                'reason' => 'Role not found'
            ];
        }

        // Check if it's the default role
        if ($role['is_default']) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete default role'
            ];
        }

        // Check if any users have this role
        $userModel = model('UserModel');
        $userCount = $userModel->where('role_id', $roleId)->countAllResults();

        if ($userCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Cannot delete role. {$userCount} users have this role.",
                'user_count' => $userCount
            ];
        }

        return [
            'can_delete' => true,
            'reason' => 'Role can be safely deleted'
        ];
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(int $roleId, array $permissionIds): bool
    {
        $db = \Config\Database::connect();

        try {
            $db->transStart();

            // Delete existing permissions for this role
            $db->table('role_permissions')
                ->where('role_id', $roleId)
                ->delete();

            // Insert new permissions
            if (!empty($permissionIds)) {
                $insertData = [];
                foreach ($permissionIds as $permissionId) {
                    $insertData[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'granted' => 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }

                $db->table('role_permissions')->insertBatch($insertData);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return false;
            }

            // Clear cache
            $cacheKey = 'role_permissions_' . $roleId;
            unset(self::$roleCache[$cacheKey]);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error assigning permissions to role: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get role hierarchy (roles with higher level)
     */
    public function getRoleHierarchy(int $currentLevel): array
    {
        return $this->where('level >', $currentLevel)
            ->where('is_active', 1)
            ->orderBy('level', 'DESC')
            ->findAll();
    }

    /**
     * Check if role slug exists
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $builder = $this->where('slug', $slug);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Generate unique slug from name
     */
    public function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get paginated roles with filters
     */
    public function getPaginatedRoles(array $filters = [], int $perPage = 10): array
    {
        $builder = $this->select('roles.*, 
                    (SELECT COUNT(*) FROM users WHERE users.role_id = roles.id AND users.deleted_at IS NULL) as user_count')
            ->orderBy('level', 'DESC');

        // Apply filters
        if (!empty($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('name', $filters['search'])
                ->orLike('slug', $filters['search'])
                ->orLike('description', $filters['search'])
                ->groupEnd();
        }

        if (!empty($filters['level_min'])) {
            $builder->where('level >=', $filters['level_min']);
        }

        if (!empty($filters['level_max'])) {
            $builder->where('level <=', $filters['level_max']);
        }

        return [
            'data' => $builder->paginate($perPage),
            'pager' => $this->pager
        ];
    }

    /**
     * Clear role cache
     */
    public function clearCache(int $roleId = null): void
    {
        if ($roleId) {
            $cacheKey = 'role_permissions_' . $roleId;
            unset(self::$roleCache[$cacheKey]);
        } else {
            self::$roleCache = [];
        }
    }

    /**
     * Create role with automatic slug generation
     */
    public function createRole(array $data): int|false
    {
        // Generate slug if not provided
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        $roleId = $this->insert($data);

        if ($roleId) {
            // Clear cache
            self::$roleCache = [];
        }

        return $roleId;
    }

    /**
     * Update role with slug validation
     */
    public function updateRole(int $roleId, array $data): bool
    {
        // Generate new slug if name changed and slug not provided
        if (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name'], $roleId);
        }

        $result = $this->update($roleId, $data);

        if ($result) {
            // Clear cache
            self::$roleCache = [];
        }

        return $result;
    }
}