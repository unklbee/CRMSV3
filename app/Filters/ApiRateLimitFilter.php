<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API Rate Limit Filter - More strict rate limiting for API endpoints
 */
class ApiRateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $rateLimiter = service('rateLimiter');
        $clientIP = $request->getIPAddress();

        // Different rate limits for different API endpoints
        $endpoint = $request->getUri()->getPath();
        $limits = $this->getApiLimits($endpoint);

        $key = 'api_' . $clientIP . '_' . $limits['type'];

        if (!$rateLimiter->isAllowed($key, $limits['max_requests'], $limits['time_window'])) {
            log_message('warning', "API rate limit exceeded for IP: {$clientIP} on endpoint: {$endpoint}");

            return response()->setJSON([
                'success' => false,
                'message' => 'API rate limit exceeded',
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $limits['time_window'],
                'limit' => $limits['max_requests']
            ])->setStatusCode(429)
                ->setHeader('X-RateLimit-Limit', $limits['max_requests'])
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setHeader('Retry-After', $limits['time_window']);
        }

        $rateLimiter->attempt($key, $limits['max_requests'], $limits['time_window']);

        // Add rate limit headers
        $remaining = $rateLimiter->getRemainingAttempts($key, $limits['max_requests']);
        $request->setGlobal('rate_limit_remaining', $remaining);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add rate limit headers to response
        $remaining = $request->getGlobal('rate_limit_remaining') ?? 0;

        return $response->setHeader('X-RateLimit-Remaining', $remaining);
    }

    private function getApiLimits(string $endpoint): array
    {
        // Define different rate limits for different API endpoints
        $limits = [
            // Authentication endpoints (more restrictive)
            'auth' => [
                'type' => 'auth',
                'max_requests' => 5,
                'time_window' => 300 // 5 requests per 5 minutes
            ],

            // General API endpoints
            'general' => [
                'type' => 'general',
                'max_requests' => 60,
                'time_window' => 60 // 60 requests per minute
            ],

            // Heavy operations (like reports)
            'heavy' => [
                'type' => 'heavy',
                'max_requests' => 10,
                'time_window' => 60 // 10 requests per minute
            ]
        ];

        // Determine limit type based on endpoint
        if (strpos($endpoint, '/api/v1/auth/') !== false) {
            return $limits['auth'];
        }

        if (strpos($endpoint, '/api/v1/reports/') !== false ||
            strpos($endpoint, '/api/v1/admin/export/') !== false) {
            return $limits['heavy'];
        }

        return $limits['general'];
    }
}