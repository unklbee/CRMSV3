<?php

namespace App\Controllers\Adminsss;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class UserController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $roleModel;
    protected $session;
    protected $validation;
    protected $db;

    public function __construct()
    {
        $this->userModel = model('UserModel');
        $this->roleModel = model('RoleModel');
        $this->session = session();
        $this->validation = \Config\Services::validation();
        $this->db = \Config\Database::connect();
        helper(['url', 'form', 'text', 'filesystem']);
    }

    /**
     * Display user management page
     */
    public function index(): string
    {
        // Check admin permission
        if (!$this->hasPermission('users', 'read')) {
            return redirect()->to('/admin')->with('error', 'Access denied');
        }

        $data = [
            'title' => 'User Management',
            'breadcrumb' => [
                'Admin' => '/admin',
                'User Management' => ''
            ],
            'roles' => $this->roleModel->findAll(),
            'user_stats' => $this->getUserStats(),
        ];

        return view('admin/users/index', $data);
    }

    /**
     * DataTables server-side processing for user list
     */
    public function datatables()
    {
        if (!$this->request->isAJAX()) {
            return $this->failUnauthorized('Invalid request');
        }

        try {
            $draw = intval($this->request->getPost('draw'));
            $start = intval($this->request->getPost('start'));
            $length = intval($this->request->getPost('length'));
            $searchValue = $this->request->getPost('search')['value'] ?? '';
            $orderColumn = $this->request->getPost('order')[0]['column'] ?? 0;
            $orderDir = $this->request->getPost('order')[0]['dir'] ?? 'asc';

            // Column mapping
            $columns = [
                0 => 'users.id',
                1 => 'users.username',
                2 => 'users.email',
                3 => 'users.first_name',
                4 => 'users.last_name',
                5 => 'roles.name',
                6 => 'users.is_active',
                7 => 'users.created_at',
                8 => 'users.last_login'
            ];

            $orderBy = $columns[$orderColumn] ?? 'users.id';

            // Build query - Modified to handle missing columns gracefully
            $builder = $this->db->table('users')
                ->select('users.id, users.username, users.email, users.first_name, users.last_name, users.is_active, users.created_at, users.last_login, users.avatar, COALESCE(roles.name, "No Role") as role_name, COALESCE(roles.slug, "") as role_slug')
                ->join('roles', 'roles.id = users.role_id', 'left');

            // Add WHERE clause only if deleted_at column exists
            if ($this->db->fieldExists('deleted_at', 'users')) {
                $builder->where('users.deleted_at', null);
            }

            // Search functionality
            if (!empty($searchValue)) {
                $builder->groupStart()
                    ->like('users.username', $searchValue)
                    ->orLike('users.email', $searchValue)
                    ->orLike('users.first_name', $searchValue)
                    ->orLike('users.last_name', $searchValue);

                // Only add role search if roles table exists
                if ($this->db->tableExists('roles')) {
                    $builder->orLike('roles.name', $searchValue);
                }
                $builder->groupEnd();
            }

            // Apply filters if provided
            $statusFilter = $this->request->getPost('status_filter');
            $roleFilter = $this->request->getPost('role_filter');

            if (!empty($statusFilter)) {
                $builder->where('users.is_active', $statusFilter);
            }

            if (!empty($roleFilter)) {
                $builder->where('users.role_id', $roleFilter);
            }

            // Get total records
            $totalBuilder = $this->db->table('users');
            if ($this->db->fieldExists('deleted_at', 'users')) {
                $totalBuilder->where('deleted_at', null);
            }
            $totalRecords = $totalBuilder->countAllResults();

            // Get filtered records count
            $filteredRecords = $builder->countAllResults(false);

            // Get actual data
            $users = $builder->orderBy($orderBy, $orderDir)
                ->limit($length, $start)
                ->get()
                ->getResultArray();

            // Format data for DataTables
            $data = [];
            foreach ($users as $user) {
                $data[] = [
                    'id' => $user['id'],
                    'username' => esc($user['username']),
                    'email' => esc($user['email']),
                    'full_name' => esc(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                    'role' => [
                        'name' => esc($user['role_name'] ?? 'No Role'),
                        'slug' => esc($user['role_slug'] ?? '')
                    ],
                    'status' => [
                        'is_active' => (bool)$user['is_active'],
                        'last_login' => $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : null
                    ],
                    'avatar' => $user['avatar'] ? base_url('uploads/avatars/' . $user['avatar']) : null,
                    'created_at' => $user['created_at'],
                    'actions' => $this->generateActionButtons($user)
                ];
            }

            return $this->respond([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            log_message('error', 'DataTables error: ' . $e->getMessage());
            return $this->fail('Error loading data: ' . $e->getMessage());
        }
    }

    /**
     * Show create user form
     */
    public function create(): string
    {
        if (!$this->hasPermission('users', 'create')) {
            return redirect()->to('/admin/users')->with('error', 'Access denied');
        }

        $data = [
            'title' => 'Create User',
            'breadcrumb' => [
                'Admin' => '/admin',
                'User Management' => '/admin/users',
                'Create User' => ''
            ],
            'roles' => $this->roleModel->where('is_active', 1)->findAll()
        ];

        return view('admin/users/create', $data);
    }

    /**
     * Store new user
     */
    public function store()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('/admin/users/create');
        }

        if (!$this->hasPermission('users', 'create')) {
            return $this->failUnauthorized('Access denied');
        }

        // Validation rules
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'first_name' => 'required|max_length[50]',
            'last_name' => 'required|max_length[50]',
            'password' => 'required|min_length[8]',
            'role_id' => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $userData = [
                'username' => $this->request->getPost('username'),
                'email' => $this->request->getPost('email'),
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                'role_id' => $this->request->getPost('role_id'),
                'is_active' => $this->request->getPost('is_active') ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $userId = $this->userModel->insert($userData);

            if ($userId) {
                return $this->respondCreated([
                    'status' => 'success',
                    'message' => 'User created successfully',
                    'user_id' => $userId
                ]);
            } else {
                return $this->fail('Failed to create user');
            }

        } catch (\Exception $e) {
            log_message('error', 'User creation failed: ' . $e->getMessage());
            return $this->fail('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Show user details
     */
    public function show($id): string
    {
        if (!$this->hasPermission('users', 'read')) {
            return redirect()->to('/admin/users')->with('error', 'Access denied');
        }

        $user = $this->userModel
            ->select('users.*, roles.name as role_name, roles.slug as role_slug')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->find($id);

        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User not found');
        }

        $data = [
            'title' => 'User Details',
            'breadcrumb' => [
                'Admin' => '/admin',
                'User Management' => '/admin/users',
                'User Details' => ''
            ],
            'user' => $user
        ];

        return view('admin/users/show', $data);
    }

    /**
     * Show edit user form
     */
    public function edit($id): string
    {
        if (!$this->hasPermission('users', 'update')) {
            return redirect()->to('/admin/users')->with('error', 'Access denied');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User not found');
        }

        $data = [
            'title' => 'Edit User',
            'breadcrumb' => [
                'Admin' => '/admin',
                'User Management' => '/admin/users',
                'Edit User' => ''
            ],
            'user' => $user,
            'roles' => $this->roleModel->where('is_active', 1)->findAll()
        ];

        return view('admin/users/edit', $data);
    }

    /**
     * Update user
     */
    public function update($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('/admin/users/' . $id . '/edit');
        }

        if (!$this->hasPermission('users', 'update')) {
            return $this->failUnauthorized('Access denied');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        // Validation rules
        $rules = [
            'username' => "required|min_length[3]|max_length[50]|is_unique[users.username,id,{$id}]",
            'email' => "required|valid_email|is_unique[users.email,id,{$id}]",
            'first_name' => 'required|max_length[50]',
            'last_name' => 'required|max_length[50]',
            'role_id' => 'required|numeric'
        ];

        // Add password validation only if provided
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $rules['password'] = 'min_length[8]';
        }

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $userData = [
                'username' => $this->request->getPost('username'),
                'email' => $this->request->getPost('email'),
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'role_id' => $this->request->getPost('role_id'),
                'is_active' => $this->request->getPost('is_active') ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Add password if provided
            if (!empty($password)) {
                $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $updated = $this->userModel->update($id, $userData);

            if ($updated) {
                return $this->respond([
                    'status' => 'success',
                    'message' => 'User updated successfully'
                ]);
            } else {
                return $this->fail('Failed to update user');
            }

        } catch (\Exception $e) {
            log_message('error', 'User update failed: ' . $e->getMessage());
            return $this->fail('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->failBadRequest('Invalid request');
        }

        if (!$this->hasPermission('users', 'delete')) {
            return $this->failUnauthorized('Access denied');
        }

        $currentUserId = $this->session->get('user_id');
        if ($id == $currentUserId) {
            return $this->fail('Cannot delete your own account');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        try {
            $deleted = $this->userModel->delete($id);

            if ($deleted) {
                return $this->respond([
                    'status' => 'success',
                    'message' => 'User deleted successfully'
                ]);
            } else {
                return $this->fail('Failed to delete user');
            }

        } catch (\Exception $e) {
            log_message('error', 'User deletion failed: ' . $e->getMessage());
            return $this->fail('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions for users
     */
    public function bulkAction()
    {
        if (!$this->request->isAJAX()) {
            return $this->failBadRequest('Invalid request');
        }

        $action = $this->request->getPost('action');
        $userIds = $this->request->getPost('user_ids');

        if (!$action || !$userIds || !is_array($userIds)) {
            return $this->failBadRequest('Invalid parameters');
        }

        $currentUserId = $this->session->get('user_id');

        // Remove current user from the list
        $userIds = array_filter($userIds, function ($id) use ($currentUserId) {
            return $id != $currentUserId;
        });

        if (empty($userIds)) {
            return $this->fail('No valid users selected');
        }

        try {
            switch ($action) {
                case 'delete':
                    if (!$this->hasPermission('users', 'delete')) {
                        return $this->failUnauthorized('Access denied');
                    }

                    $deleted = $this->userModel->whereIn('id', $userIds)->delete();
                    if ($deleted) {
                        return $this->respond([
                            'status' => 'success',
                            'message' => 'Selected users deleted successfully'
                        ]);
                    }
                    break;

                case 'activate':
                    if (!$this->hasPermission('users', 'update')) {
                        return $this->failUnauthorized('Access denied');
                    }

                    $updated = $this->userModel->whereIn('id', $userIds)->set('is_active', 1)->update();
                    if ($updated) {
                        return $this->respond([
                            'status' => 'success',
                            'message' => 'Selected users activated successfully'
                        ]);
                    }
                    break;

                case 'deactivate':
                    if (!$this->hasPermission('users', 'update')) {
                        return $this->failUnauthorized('Access denied');
                    }

                    $updated = $this->userModel->whereIn('id', $userIds)->set('is_active', 0)->update();
                    if ($updated) {
                        return $this->respond([
                            'status' => 'success',
                            'message' => 'Selected users deactivated successfully'
                        ]);
                    }
                    break;

                default:
                    return $this->failBadRequest('Invalid action');
            }

            return $this->fail('Failed to perform bulk action');

        } catch (\Exception $e) {
            log_message('error', 'Bulk action failed: ' . $e->getMessage());
            return $this->fail('Failed to perform bulk action: ' . $e->getMessage());
        }
    }

    /**
     * Export users data
     */
    public function export()
    {
        if (!$this->hasPermission('users', 'read')) {
            return redirect()->to('/admin/users')->with('error', 'Access denied');
        }

        try {
            $users = $this->userModel
                ->select('users.*, roles.name as role_name')
                ->join('roles', 'roles.id = users.role_id', 'left')
                ->findAll();

            // Set CSV headers
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($output, [
                'ID', 'Username', 'Email', 'First Name', 'Last Name',
                'Role', 'Status', 'Created At', 'Last Login'
            ]);

            // CSV Data
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $user['first_name'],
                    $user['last_name'],
                    $user['role_name'] ?? 'No Role',
                    $user['is_active'] ? 'Active' : 'Inactive',
                    $user['created_at'],
                    $user['last_login'] ?? 'Never'
                ]);
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export failed: ' . $e->getMessage());
            return redirect()->to('/admin/users')->with('error', 'Export failed');
        }
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Check if current user has permission
     */
    private function hasPermission(string $module, string $action): bool
    {
        $userPermissions = $this->session->get('permissions') ?? [];
        $requiredPermission = $module . '.' . $action;

        return in_array($requiredPermission, $userPermissions) ||
            $this->session->get('role_slug') === 'admin';
    }

    /**
     * Generate action buttons for DataTables
     */
    private function generateActionButtons(array $user): array
    {
        $buttons = [];
        $currentUserId = $this->session->get('user_id');

        if ($this->hasPermission('users', 'read')) {
            $buttons[] = [
                'type' => 'view',
                'url' => '/admin/users/' . $user['id'],
                'title' => 'View Details'
            ];
        }

        if ($this->hasPermission('users', 'update')) {
            $buttons[] = [
                'type' => 'edit',
                'url' => '/admin/users/' . $user['id'] . '/edit',
                'title' => 'Edit User'
            ];
        }

        if ($this->hasPermission('users', 'delete') && $user['id'] != $currentUserId) {
            $buttons[] = [
                'type' => 'delete',
                'url' => '/admin/users/' . $user['id'],
                'title' => 'Delete User'
            ];
        }

        return $buttons;
    }

    /**
     * Get user statistics
     */
    private function getUserStats(): array
    {
        try {
            // Use direct database queries for better compatibility
            $totalUsers = $this->db->table('users')->countAllResults();

            $activeUsers = $this->db->table('users')
                ->where('is_active', 1)
                ->countAllResults();

            $inactiveUsers = $this->db->table('users')
                ->where('is_active', 0)
                ->countAllResults();

            // New users this month
            $newThisMonth = $this->db->table('users')
                ->where('created_at >=', date('Y-m-01 00:00:00'))
                ->countAllResults();

            return [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'inactive' => $inactiveUsers,
                'new_this_month' => $newThisMonth
            ];

        } catch (\Exception $e) {
            log_message('error', 'getUserStats error: ' . $e->getMessage());

            // Return default values if there's an error
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'new_this_month' => 0
            ];
        }
    }
}