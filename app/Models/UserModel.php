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
        'last_login', 'last_activity', 'login_attempts', 'locked_until', 'reset_token', 'reset_expires', 'settings'
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
        $user = $this->select('users.*, roles.slug as role_slug, roles.name as role_name, roles.level as role_level')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.id', $userId)
            ->where('users.is_active', 1)
            ->where('users.deleted_at', null)
            ->first();

        if ($user) {
            $user['permissions'] = $this->getUserPermissions($user['id']);
        }

        return $user;
    }

    /**
     * Get user permissions (from role + additional permissions)
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = 'user_permissions_' . $userId;

        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }

        $db = \Config\Database::connect();

        // Get permissions from role
        $rolePermissions = $db->table('users u')
            ->select('p.id, p.slug, p.name, p.module, p.action, p.resource')
            ->join('roles r', 'r.id = u.role_id')
            ->join('role_permissions rp', 'rp.role_id = r.id')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('u.id', $userId)
            ->where('r.is_active', 1)
            ->where('p.is_active', 1)
            ->where('rp.granted', 1)
            ->get()
            ->getResultArray();

        // Get user's additional permissions (if any)
        $user = $this->find($userId);
        $additionalPermissions = [];

        if (!empty($user['additional_permissions'])) {
            $additionalPerms = json_decode($user['additional_permissions'], true);
            if (is_array($additionalPerms)) {
                $additionalPermissions = $db->table('permissions')
                    ->whereIn('slug', $additionalPerms)
                    ->where('is_active', 1)
                    ->get()
                    ->getResultArray();
            }
        }

        // Merge permissions (remove duplicates)
        $allPermissions = array_merge($rolePermissions, $additionalPermissions);
        $uniquePermissions = [];
        foreach ($allPermissions as $perm) {
            $uniquePermissions[$perm['slug']] = $perm;
        }

        $permissions = array_values($uniquePermissions);
        self::$permissionCache[$cacheKey] = $permissions;

        return $permissions;
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(int $userId, string $permissionSlug): bool
    {
        $permissions = $this->getUserPermissions($userId);

        foreach ($permissions as $permission) {
            if ($permission['slug'] === $permissionSlug) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user account is locked
     */
    public function isLocked(string $identifier): bool
    {
        $user = $this->groupStart()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->groupEnd()
            ->first();

        if (!$user) {
            return false;
        }

        // Check if locked_until is set and still in the future
        if (!empty($user['locked_until'])) {
            return strtotime($user['locked_until']) > time();
        }

        // Check max login attempts (lock after 5 failed attempts)
        return $user['login_attempts'] >= 5;
    }

    /**
     * Increment login attempts and potentially lock account
     */
    public function incrementLoginAttempts(string $identifier): bool
    {
        $user = $this->groupStart()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->groupEnd()
            ->first();

        if (!$user) {
            return false;
        }

        $newAttempts = $user['login_attempts'] + 1;
        $updateData = ['login_attempts' => $newAttempts];

        // Lock account after 5 failed attempts for 30 minutes
        if ($newAttempts >= 5) {
            $updateData['locked_until'] = date('Y-m-d H:i:s', time() + (30 * 60));
        }

        return $this->update($user['id'], $updateData);
    }

    /**
     * Clear login attempts (on successful login)
     */
    public function clearLoginAttempts(int $userId): bool
    {
        return $this->update($userId, [
            'login_attempts' => 0,
            'locked_until' => null
        ]);
    }

    /**
     * Update last login time
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update last activity
     */
    public function updateLastActivity(int $userId): bool
    {
        return $this->update($userId, [
            'last_activity' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get paginated users with filters
     */
    public function getPaginatedUsers(array $filters = [], int $perPage = 10): array
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

        // Set additional permissions if provided
        if ($additionalPermissions) {
            $userData['additional_permissions'] = json_encode($additionalPermissions);
        }

        $userId = $this->insert($userData);

        if ($userId) {
            // Clear cache
            self::$userCache = [];
            self::$permissionCache = [];
        }

        return $userId;
    }

    /**
     * Update user with role
     */
    public function updateWithRole(int $userId, array $userData, ?array $additionalPermissions = null): bool
    {
        // Hash password if provided
        if (!empty($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }

        // Set additional permissions if provided
        if ($additionalPermissions !== null) {
            $userData['additional_permissions'] = json_encode($additionalPermissions);
        }

        $result = $this->update($userId, $userData);

        if ($result) {
            // Clear cache
            self::$userCache = [];
            self::$permissionCache = [];
        }

        return $result;
    }

    /**
     * Clear user cache
     */
    public function clearCache(int $userId = null): void
    {
        if ($userId) {
            $cacheKey = 'user_permissions_' . $userId;
            unset(self::$permissionCache[$cacheKey]);
        } else {
            self::$userCache = [];
            self::$permissionCache = [];
        }
    }

    /**
     * Set password reset token
     */
    public function setResetToken(int $userId, string $token): bool
    {
        // Debug log
        log_message('debug', "setResetToken called with userId: {$userId}, token length: " . strlen($token));

        $data = [
            'reset_token' => password_hash($token, PASSWORD_DEFAULT),
            'reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Debug log
        log_message('debug', 'Update data: ' . json_encode($data));

        // Check if user exists first
        $user = $this->find($userId);
        if (!$user) {
            log_message('error', "User not found with ID: {$userId}");
            return false;
        }

        log_message('debug', "User found: {$user['username']}");

        try {
            $result = $this->update($userId, $data);
            log_message('debug', "Update result: " . ($result ? 'success' : 'failed'));
            return $result;
        } catch (\Exception $e) {
            log_message('error', "setResetToken error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find user by reset token
     */
    public function findByResetToken(string $token): ?array
    {
        $users = $this->where('reset_expires >', date('Y-m-d H:i:s'))
            ->where('reset_token IS NOT NULL')
            ->where('is_active', 1)
            ->findAll();

        foreach ($users as $user) {
            if (password_verify($token, $user['reset_token'])) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Clear reset token
     */
    public function clearResetToken(int $userId): bool
    {
        return $this->update($userId, [
            'reset_token' => null,
            'reset_expires' => null
        ]);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->findByResetToken($token);

        if (!$user) {
            return false;
        }

        // Update password and clear reset token
        $result = $this->update($user['id'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_expires' => null,
            'login_attempts' => 0, // Reset failed login attempts
            'locked_until' => null // Unlock account if locked
        ]);

        if ($result) {
            // Clear user cache
            $this->clearCache($user['id']);
        }

        return $result;
    }
}