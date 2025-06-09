<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username', 'email', 'password', 'first_name', 'last_name', 'phone', 'avatar',
        'role_id', 'additional_permissions', 'is_active', 'email_verified_at',
        'last_login', 'last_activity', 'login_attempts', 'locked_until', 'settings'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username,id,{id}]|alpha_numeric',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[8]',
        'first_name' => 'required|min_length[2]|max_length[100]',
        'last_name' => 'required|min_length[2]|max_length[100]',
        'role_id' => 'required|integer|is_not_unique[roles.id]'
    ];

    protected $validationMessages = [
        'username' => [
            'is_unique' => 'Username already exists',
            'alpha_numeric' => 'Username can only contain letters and numbers'
        ],
        'email' => [
            'is_unique' => 'Email already exists'
        ],
        'role_id' => [
            'is_not_unique' => 'Selected role does not exist'
        ]
    ];

    // Cache for user data
    private static $userCache = [];
    private static $permissionCache = [];

    /**
     * Find user by username or email with role information
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $cacheKey = 'user_' . md5($identifier);

        if (isset(self::$userCache[$cacheKey])) {
            return self::$userCache[$cacheKey];
        }

        $user = $this->select('users.*, roles.slug as role_slug, roles.name as role_name, roles.level as role_level')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->groupStart()
            ->where('users.username', $identifier)
            ->orWhere('users.email', $identifier)
            ->groupEnd()
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->first();

        if ($user) {
            // Load user permissions
            $user['permissions'] = $this->getUserPermissions($user['id']);
            self::$userCache[$cacheKey] = $user;
        }

        return $user;
    }

    /**
     * Find user with complete role and permission data
     */
    public function findWithRole(int $userId): ?array
    {
        $user = $this->select('users.*, roles.slug as role_slug, roles.name as role_name, roles.level as role_level, roles.description as role_description')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.id', $userId)
            ->where('users.deleted_at', null)
            ->first();

        if ($user) {
            $user['permissions'] = $this->getUserPermissions($userId);
            $user['role_permissions'] = $this->getRolePermissions($user['role_id']);
        }

        return $user;
    }

    /**
     * Get user permissions (role permissions + additional permissions)
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = 'user_permissions_' . $userId;

        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }

        $user = $this->find($userId);
        if (!$user) {
            return [];
        }

        // Get role permissions
        $rolePermissions = $this->getRolePermissions($user['role_id']);

        // Get additional user-specific permissions
        $additionalPermissions = [];
        if (!empty($user['additional_permissions'])) {
            $additionalPermissions = json_decode($user['additional_permissions'], true) ?? [];
        }

        // Merge permissions (additional permissions can override role permissions)
        $allPermissions = [];

        // Add role permissions
        foreach ($rolePermissions as $permission) {
            $allPermissions[$permission['slug']] = [
                'slug' => $permission['slug'],
                'name' => $permission['name'],
                'module' => $permission['module'],
                'action' => $permission['action'],
                'resource' => $permission['resource'],
                'granted' => (bool)$permission['granted'],
                'source' => 'role'
            ];
        }

        // Override with additional permissions
        foreach ($additionalPermissions as $slug => $granted) {
            if (isset($allPermissions[$slug])) {
                $allPermissions[$slug]['granted'] = (bool)$granted;
                $allPermissions[$slug]['source'] = 'user';
            }
        }

        $permissions = array_values($allPermissions);
        self::$permissionCache[$cacheKey] = $permissions;

        return $permissions;
    }

    /**
     * Get role permissions
     */
    private function getRolePermissions(int $roleId): array
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
     * Check if user has specific permission
     */
    public function hasPermission(int $userId, string $permissionSlug): bool
    {
        $permissions = $this->getUserPermissions($userId);

        foreach ($permissions as $permission) {
            if ($permission['slug'] === $permissionSlug) {
                return $permission['granted'];
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(int $userId, array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if ($this->hasPermission($userId, $slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all given permissions
     */
    public function hasAllPermissions(int $userId, array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if (!$this->hasPermission($userId, $slug)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get users with role information for admin panel
     */
    public function getUsersWithRoles(array $filters = [], int $perPage = 10): array
    {
        $builder = $this->select('users.*, roles.name as role_name, roles.slug as role_slug, roles.level as role_level')
            ->join('roles', 'roles.id = users.role_id', 'left');

        // Apply filters
        if (!empty($filters['role_id'])) {
            $builder->where('users.role_id', $filters['role_id']);
        }

        if (!empty($filters['is_active'])) {
            $builder->where('users.is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('users.username', $filters['search'])
                ->orLike('users.email', $filters['search'])
                ->orLike('CONCAT(users.first_name, " ", users.last_name)', $filters['search'])
                ->groupEnd();
        }

        if (!empty($filters['role_level'])) {
            $builder->where('roles.level >=', $filters['role_level']);
        }

        return [
            'data' => $builder->orderBy('users.created_at', 'DESC')
                ->paginate($perPage),
            'pager' => $this->pager
        ];
    }

    /**
     * Create user with role assignment
     */
    public function createWithRole(array $userData, ?array $additionalPermissions = null): int|false
    {
        // Hash password if provided
        if (!empty($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }

        // Set default role if not provided
        if (empty($userData['role_id'])) {
            $roleModel = model('RoleModel');
            $defaultRole = $roleModel->getDefaultRole();
            $userData['role_id'] = $defaultRole ? $defaultRole['id'] : null;
        }

        // Add additional permissions if provided
        if ($additionalPermissions) {
            $userData['additional_permissions'] = json_encode($additionalPermissions);
        }

        $userId = $this->insert($userData);

        if ($userId) {
            // Clear cache
            self::clearUserCache();
        }

        return $userId;
    }

    /**
     * Update user role and permissions
     */
    public function updateUserRole(int $userId, int $roleId, ?array $additionalPermissions = null): bool
    {
        $updateData = ['role_id' => $roleId];

        if ($additionalPermissions !== null) {
            $updateData['additional_permissions'] = json_encode($additionalPermissions);
        }

        $result = $this->update($userId, $updateData);

        if ($result) {
            // Clear cache
            self::clearUserCache();
        }

        return $result;
    }

    /**
     * Grant additional permission to user
     */
    public function grantPermission(int $userId, string $permissionSlug, bool $granted = true): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        $additionalPermissions = [];
        if (!empty($user['additional_permissions'])) {
            $additionalPermissions = json_decode($user['additional_permissions'], true) ?? [];
        }

        $additionalPermissions[$permissionSlug] = $granted;

        $result = $this->update($userId, [
            'additional_permissions' => json_encode($additionalPermissions)
        ]);

        if ($result) {
            // Clear cache
            self::clearUserCache();
        }

        return $result;
    }

    /**
     * Remove additional permission from user
     */
    public function revokePermission(int $userId, string $permissionSlug): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        $additionalPermissions = [];
        if (!empty($user['additional_permissions'])) {
            $additionalPermissions = json_decode($user['additional_permissions'], true) ?? [];
        }

        unset($additionalPermissions[$permissionSlug]);

        $result = $this->update($userId, [
            'additional_permissions' => json_encode($additionalPermissions)
        ]);

        if ($result) {
            // Clear cache
            self::clearUserCache();
        }

        return $result;
    }

    /**
     * Get user statistics with role breakdown
     */
    public function getUserStats(): array
    {
        $db = \Config\Database::connect();

        $roleStats = $db->table('users u')
            ->select('r.name as role_name, r.slug as role_slug, COUNT(*) as count')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->where('u.deleted_at', null)
            ->groupBy('u.role_id')
            ->orderBy('count', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'total' => $this->where('deleted_at', null)->countAllResults(),
            'active' => $this->where('is_active', 1)->where('deleted_at', null)->countAllResults(),
            'inactive' => $this->where('is_active', 0)->where('deleted_at', null)->countAllResults(),
            'verified' => $this->where('email_verified_at !=', null)->where('deleted_at', null)->countAllResults(),
            'locked' => $this->where('locked_until >', date('Y-m-d H:i:s'))->where('deleted_at', null)->countAllResults(),
            'by_role' => $roleStats,
            'new_today' => $this->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
            'new_week' => $this->where('created_at >=', date('Y-m-d', strtotime('-7 days')))->countAllResults(),
            'new_month' => $this->where('created_at >=', date('Y-m-d', strtotime('-30 days')))->countAllResults()
        ];
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s'),
            'login_attempts' => 0,
            'locked_until' => null
        ]);
    }

    /**
     * Increment login attempts and lock if necessary
     */
    public function incrementLoginAttempts(string $identifier, int $maxAttempts = 5, int $lockoutMinutes = 30): bool
    {
        $user = $this->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user) {
            return false;
        }

        $attempts = $user['login_attempts'] + 1;
        $updateData = ['login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $updateData['locked_until'] = date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"));
        }

        return $this->update($user['id'], $updateData);
    }

    /**
     * Check if user is locked
     */
    public function isLocked(string $identifier): bool
    {
        $user = $this->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user || !$user['locked_until']) {
            return false;
        }

        return strtotime($user['locked_until']) > time();
    }

    /**
     * Clear user cache
     */
    public static function clearUserCache(): void
    {
        self::$userCache = [];
        self::$permissionCache = [];
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $roleSlug): array
    {
        return $this->select('users.*')
            ->join('roles', 'roles.id = users.role_id')
            ->where('roles.slug', $roleSlug)
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->findAll();
    }

    /**
     * Bulk update users
     */
    public function bulkUpdate(array $userIds, array $updateData): bool
    {
        $builder = $this->builder();
        $result = $builder->whereIn('id', $userIds)->update($updateData);

        if ($result) {
            self::clearUserCache();
        }

        return $result;
    }
}