<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username', 'email', 'password', 'first_name', 'last_name',
        'is_active', 'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
        'email'    => 'required|valid_email|is_unique[users.email]',
        'password' => 'required|min_length[8]'
    ];

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
            ->first();
    }

    /**
     * Verifikasi login dengan username/email dan password
     *
     * @param string $identifier Username atau email
     * @param string $password Password plain text
     * @return array|false
     */
    public function verifyLogin(string $identifier, string $password)
    {
        $user = $this->findByUsernameOrEmail($identifier);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    /**
     * Hash password sebelum disimpan
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }

        return $data;
    }

    // Event callbacks
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];
}