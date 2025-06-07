<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends Controller
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper(['form', 'url']);
    }

    /**
     * Tampilkan halaman login
     */
    public function signin()
    {
        // Jika sudah login, redirect ke dashboard
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/signin');
    }

    /**
     * Proses login
     */
    public function processLogin()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'identifier' => [
                'label' => 'Username/Email',
                'rules' => 'required'
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required'
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validation->getErrors()
            ]);
        }

        $identifier = $this->request->getPost('identifier');
        $password = $this->request->getPost('password');

        // Verifikasi login
        $user = $this->userModel->verifyLogin($identifier, $password);

        if ($user) {
            // Set session data
            $sessionData = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'isLoggedIn' => true
            ];

            session()->set($sessionData);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => '/dashboard'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid username/email or password'
            ]);
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/signin')->with('message', 'You have been logged out');
    }
}