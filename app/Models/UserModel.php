<?php

namespace App\Models;

use CodeIgniter\Model;
use ReflectionException;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username', 'email', 'password', 'first_name', 'last_name', 'phone','last_login',
        'role', 'is_active', 'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Cache untuk hasil query yang sering diakses
    private $userCache = [];

    /**
     * Optimized: Cari user berdasarkan username atau email dengan caching
     *
     * @param string $identifier Username atau email
     * @return array|null
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        // Check cache first
        $cacheKey = 'user_' . md5($identifier);

        if (isset($this->userCache[$cacheKey])) {
            return $this->userCache[$cacheKey];
        }

        // Query dengan index yang optimal (pastikan username & email ter-index)
        $user = $this->select('id, username, email, password, first_name, last_name, role, is_active, last_login')
            ->where('is_active', 1)
            ->groupStart()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->groupEnd()
            ->first();

        // Cache result untuk request berikutnya
        if ($user) {
            $this->userCache[$cacheKey] = $user;
        }

        return $user;
    }

    /**
     * Simplified: Verifikasi password tanpa update last_login
     * Last login akan di-update secara async di controller
     *
     * @param string $identifier Username atau email
     * @param string $password Password plain text
     * @return array|false
     */
    public function verifyPassword(string $identifier, string $password): bool|array
    {
        $user = $this->findByUsernameOrEmail($identifier);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    /**
     * Legacy method untuk backward compatibility
     * @deprecated Use verifyPassword instead
     */
    public function verifyLogin(string $identifier, string $password): bool|array
    {
        return $this->verifyPassword($identifier, $password);
    }

    /**
     * Optimized: Create new user dengan transaction
     */
    public function createUser(array $data): int|false
    {
        $this->db->transStart();

        try {
            // Validate required fields
            $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("Field {$field} is required");
                }
            }

            // Hash password before saving
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
            }

            // Set default values
            $data['is_active'] = $data['is_active'] ?? 1;
            $data['role'] = $data['role'] ?? 'customer';

            $userId = $this->insert($data);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return false;
            }

            // Clear cache setelah insert
            $this->clearUserCache();

            return $userId;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error creating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimized: Update user dengan validation dan transaction
     */
    public function updateUser(int $userId, array $data): bool
    {
        $this->db->transStart();

        try {
            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
            } else {
                // Remove password field if empty
                unset($data['password']);
            }

            $result = $this->update($userId, $data);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return false;
            }

            // Clear cache setelah update
            $this->clearUserCache();

            return $result;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error updating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch update last login untuk multiple users (untuk optimization)
     */
    public function updateLastLogin(int $userId): bool
    {
        try {
            return $this->set('last_login', 'NOW()', false)
                ->where('id', $userId)
                ->update();
        } catch (\Exception $e) {
            log_message('error', 'Error updating last login: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Change user password dengan validation
     * @throws ReflectionException
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        // Verify current password first
        $user = $this->find($userId);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return false;
        }

        return $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_ARGON2ID)
        ]);
    }

    /**
     * Optimized: Get user by email dengan caching
     */
    public function findByEmail(string $email): ?array
    {
        $cacheKey = 'user_email_' . md5($email);

        if (isset($this->userCache[$cacheKey])) {
            return $this->userCache[$cacheKey];
        }

        $user = $this->where('email', $email)
            ->where('is_active', 1)
            ->first();

        if ($user) {
            $this->userCache[$cacheKey] = $user;
        }

        return $user;
    }

    /**
     * Optimized: Get users dengan pagination, search, dan proper indexing
     */
    public function getUsersPaginated(int $perPage = 10, string $search = '', array $filters = []): array
    {
        $builder = $this->select('id, username, email, first_name, last_name, role, is_active, last_login, created_at');

        // Search functionality
        if (!empty($search)) {
            $builder->groupStart()
                ->like('username', $search)
                ->orLike('email', $search)
                ->orLike('CONCAT(first_name, " ", last_name)', $search)
                ->groupEnd();
        }

        // Additional filters
        foreach ($filters as $field => $value) {
            if (in_array($field, $this->allowedFields) && !empty($value)) {
                $builder->where($field, $value);
            }
        }

        return [
            'data' => $builder->paginate($perPage),
            'pager' => $this->pager
        ];
    }

    /**
     * Get user statistics untuk dashboard
     */
    public function getUserStats(): array
    {
        return [
            'total' => $this->countAll(),
            'active' => $this->where('is_active', 1)->countAllResults(),
            'inactive' => $this->where('is_active', 0)->countAllResults(),
            'admins' => $this->where('role', 'admin')->countAllResults(),
            'technicians' => $this->where('role', 'technician')->countAllResults(),
            'customers' => $this->where('role', 'customer')->countAllResults(),
        ];
    }

    /**
     * Soft delete user (deactivate) dengan logging
     * @throws ReflectionException
     */
    public function deactivateUser(int $userId): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        $result = $this->update($userId, ['is_active' => 0]);

        if ($result) {
            log_message('info', "User deactivated: {$user['username']} (ID: {$userId})");
            $this->clearUserCache();
        }

        return $result;
    }

    /**
     * Activate user dengan logging
     * @throws ReflectionException
     */
    public function activateUser(int $userId): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        $result = $this->update($userId, ['is_active' => 1]);

        if ($result) {
            log_message('info', "User activated: {$user['username']} (ID: {$userId})");
            $this->clearUserCache();
        }

        return $result;
    }

    /**
     * Clear user cache
     */
    private function clearUserCache(): void
    {
        $this->userCache = [];
    }

    /**
     * Check jika username sudah ada (untuk validation)
     */
    public function isUsernameExists(string $username, int $excludeId = null): bool
    {
        $builder = $this->where('username', $username);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Check jika email sudah ada (untuk validation)
     */
    public function isEmailExists(string $email, int $excludeId = null): bool
    {
        $builder = $this->where('email', $email);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }
}