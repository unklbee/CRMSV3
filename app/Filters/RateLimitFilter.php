<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate Limit Filter
 */
class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $rateLimiter = service('rateLimiter');
        $clientIP = $request->getIPAddress();
        $key = 'general_' . $clientIP;

        // General rate limiting: 100 requests per minute
        if (!$rateLimiter->isAllowed($key, 100, 60)) {
            log_message('warning', "Rate limit exceeded for IP: {$clientIP}");

            if ($request->isAJAX()) {
                return response()->setJSON([
                    'success' => false,
                    'message' => 'Rate limit exceeded. Please slow down.'
                ])->setStatusCode(429);
            }

            return response()->setStatusCode(429, 'Too Many Requests');
        }

        $rateLimiter->attempt($key, 100, 60);
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}