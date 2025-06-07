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

    // No validation rules here - moved to UserValidation class

    /**
     * Cari user berdasarkan username atau email
     *
     * @param string $identifier Username atau email
     * @return array|null
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        return $this->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Verifikasi login dengan username/email dan password
     *
     * @param string $identifier Username atau email
     * @param string $password Password plain text
     * @return array|false
     * @throws ReflectionException
     */
    public function verifyLogin(string $identifier, string $password): bool|array
    {
        $user = $this->findByUsernameOrEmail($identifier);

        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $this->update($user['id'], [
                'last_login' => date('Y-m-d H:i:s')
            ]);

            return $user;
        }

        return false;
    }

    /**
     * Create new user dengan validation
     * @throws ReflectionException
     */
    public function createUser(array $data): int|false
    {
        // Hash password before saving
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Set default values
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['role'] = $data['role'] ?? 'user';

        return $this->insert($data);
    }

    /**
     * Update user dengan validation
     * @throws ReflectionException
     */
    public function updateUser(int $userId, array $data): bool
    {
        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Remove password field if empty
            unset($data['password']);
        }

        return $this->update($userId, $data);
    }

    /**
     * Change user password
     * @throws ReflectionException
     */
    public function changePassword(int $userId, string $newPassword): bool
    {
        return $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Get user by email for password reset
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get users with pagination and search
     */
    public function getUsersPaginated(int $perPage = 10, string $search = ''): array
    {
        $builder = $this->builder();

        if (!empty($search)) {
            $builder->groupStart()
                ->like('username', $search)
                ->orLike('email', $search)
                ->orLike('first_name', $search)
                ->orLike('last_name', $search)
                ->groupEnd();
        }

        return [
            'data' => $builder->paginate($perPage),
            'pager' => $this->pager
        ];
    }

    /**
     * Soft delete user (deactivate)
     * @throws ReflectionException
     */
    public function deactivateUser(int $userId): bool
    {
        return $this->update($userId, ['is_active' => 0]);
    }

    /**
     * Activate user
     * @throws ReflectionException
     */
    public function activateUser(int $userId): bool
    {
        return $this->update($userId, ['is_active' => 1]);
    }
}