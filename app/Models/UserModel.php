<?php
// app/Models/UserModel.php
namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'username', 'email', 'password', 'first_name', 'last_name',
        'phone', 'role', 'status'
    ];

    protected bool $allowEmptyInserts = false;
    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username,id,{id}]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'permit_empty|min_length[6]',
        'first_name' => 'required|min_length[2]|max_length[100]',
        'role' => 'required|in_list[admin,technician,customer]'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data): array
    {
        if (!empty($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    public function getTechnicians(): array
    {
        return $this->select('id, first_name, last_name, username, email')
            ->where('role', 'technician')
            ->orWhere('role', 'admin') // Admin can also be technician
            ->where('status', 'active')
            ->orderBy('full_name', 'ASC')
            ->findAll();
    }

    public function getUserWithProfile($id): array|object|null
    {
        // For now, just return the user data
        // In future, this could join with profile or other related tables
        return $this->find($id);
    }

}