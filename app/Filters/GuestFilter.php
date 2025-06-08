<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        try {
            // Try to check if user is already logged in
            $session = session();

            if ($session && $session->get('isLoggedIn')) {
                return redirect()->to('/dashboard');
            }
        } catch (\Exception $e) {
            // Log session error but don't block request
            log_message('error', 'Session error in GuestFilter: ' . $e->getMessage());

            // Continue without session check - better than blocking access
            return null;
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}