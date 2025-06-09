<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API Authentication Filter
 */
class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check for API token in header
        $token = $request->getHeaderLine('Authorization');

        if (empty($token)) {
            return response()->setJSON([
                'success' => false,
                'message' => 'Authorization token required',
                'error_code' => 'MISSING_TOKEN'
            ])->setStatusCode(401);
        }

        // Remove 'Bearer ' prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Validate token (implement your token validation logic)
        if (!$this->validateApiToken($token)) {
            return response()->setJSON([
                'success' => false,
                'message' => 'Invalid or expired token',
                'error_code' => 'INVALID_TOKEN'
            ])->setStatusCode(401);
        }

        // Set user context from token
        $user = $this->getUserFromToken($token);
        if ($user) {
            // Set user data in request for controllers to use
            $request->setGlobal('api_user', $user);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add API-specific headers
        return $response->setHeader('X-API-Version', '1.0')
            ->setHeader('X-Rate-Limit-Remaining', '100'); // Example
    }

    private function validateApiToken(string $token): bool
    {
        // Implement your token validation logic here
        // This could be JWT validation, database lookup, etc.

        // For now, return true for demo purposes
        // In real implementation, validate against your token system
        return !empty($token) && strlen($token) > 10;
    }

    private function getUserFromToken(string $token): ?array
    {
        // Implement user retrieval from token
        // This would typically decode JWT or lookup token in database

        // For demo purposes, return null
        // In real implementation:
        // 1. Decode JWT token
        // 2. Get user_id from token
        // 3. Load user from database
        // 4. Return user array

        return null;
    }
}