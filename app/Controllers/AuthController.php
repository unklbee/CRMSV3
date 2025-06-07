<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Validation\UserValidation;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use ReflectionException;

class AuthController extends Controller
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper(['form', 'url']);
    }

    public function signin(): string|RedirectResponse
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $data = ['title' => 'Sign In'];
        return view('auth/signin', $data);
    }

    /**
     * @throws ReflectionException
     */
    public function processLogin(): ResponseInterface
    {
        // Use validation class
        $validation = Services::validation();
        $rules = UserValidation::getLoginRules();

        // Base response with fresh CSRF
        $responseData = [
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ];

        if (!$this->validate($rules)) {
            $responseData['success'] = false;
            $responseData['message'] = 'Validation failed';
            $responseData['errors'] = $validation->getErrors();

            return $this->response->setJSON($responseData);
        }

        $identifier = $this->request->getPost('identifier');
        $password = $this->request->getPost('password');

        $user = $this->userModel->verifyLogin($identifier, $password);

        if ($user) {
            $sessionData = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
                'isLoggedIn' => true
            ];

            session()->set($sessionData);

            $responseData['success'] = true;
            $responseData['message'] = 'Login successful';
            $responseData['redirect'] = '/dashboard';
        } else {
            $responseData['success'] = false;
            $responseData['message'] = 'Invalid username/email or password';
        }

        return $this->response->setJSON($responseData);
    }

    public function signup(): string|RedirectResponse
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $data = ['title' => 'Sign Up'];
        return view('auth/signup', $data);
    }

    public function processRegister(): ResponseInterface
    {
        // Use validation class
        $validation = Services::validation();
        $rules = UserValidation::getRegistrationRules();

        $responseData = [
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ];

        if (!$this->validate($rules)) {
            $responseData['success'] = false;
            $responseData['message'] = 'Validation failed';
            $responseData['errors'] = $validation->getErrors();

            return $this->response->setJSON($responseData);
        }

        $userData = [
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'first_name' => $this->request->getPost('first_name'),
            'last_name' => $this->request->getPost('last_name')
        ];

        $userId = $this->userModel->createUser($userData);

        if ($userId) {
            $responseData['success'] = true;
            $responseData['message'] = 'Registration successful! Please login.';
            $responseData['redirect'] = '/auth/signin';
        } else {
            $responseData['success'] = false;
            $responseData['message'] = 'Registration failed. Please try again.';
        }

        return $this->response->setJSON($responseData);
    }

    public function getCsrfToken(): ResponseInterface
    {
        return $this->response->setJSON([
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();
        return redirect()->to('/auth/signin')->with('message', 'You have been logged out');
    }
}