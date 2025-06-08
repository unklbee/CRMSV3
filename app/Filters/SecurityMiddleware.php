<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Security Middleware untuk proteksi tambahan
 */
class SecurityMiddleware implements FilterInterface
{
    private const MAX_REQUEST_SIZE = 1048576; // 1MB
    private const SUSPICIOUS_PATTERNS = [
        '/\b(union\s+select|insert\s+into|drop\s+table|delete\s+from)\b/i',
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', // Fixed: removed 'g' flag
        '/javascript:/i',
        '/vbscript:/i',
        '/onload\s*=/i',
        '/onerror\s*=/i',
        '/onclick\s*=/i',
        '/onmouseover\s*=/i',
        '/expression\s*\(/i',
        '/eval\s*\(/i'
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Check request size
        if ($this->getRequestSize($request) > self::MAX_REQUEST_SIZE) {
            log_message('warning', 'Large request detected from IP: ' . $request->getIPAddress());
            return Services::response()->setStatusCode(413)->setBody('Request too large');
        }

        // 2. Check for suspicious patterns
        if ($this->containsSuspiciousContent($request)) {
            log_message('critical', 'Suspicious request detected from IP: ' . $request->getIPAddress());
            return Services::response()->setStatusCode(400)->setBody('Bad request');
        }

        // 3. Rate limiting per IP
        if (!$this->checkRateLimit($request)) {
            return Services::response()->setStatusCode(429)->setBody('Too many requests');
        }

        // 4. Check user agent (block empty atau suspicious)
        if (!$this->isValidUserAgent($request)) {
            log_message('info', 'Invalid user agent from IP: ' . $request->getIPAddress());
            return Services::response()->setStatusCode(400)->setBody('Invalid request');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add comprehensive security headers
        $response->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
            ->setHeader('X-Permitted-Cross-Domain-Policies', 'none');

        // Add cache control hanya untuk halaman sensitif
        $sensitivePages = ['/admin', '/auth/processLogin', '/profile'];
        $currentPath = $request->getUri()->getPath();

        foreach ($sensitivePages as $page) {
            if (str_starts_with($currentPath, $page)) {
                $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                    ->setHeader('Pragma', 'no-cache')
                    ->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
                break;
            }
        }

        // Content Security Policy untuk halaman admin
        if (str_starts_with($currentPath, '/admin')) {
            $csp = "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
                "img-src 'self' data: https:; " .
                "font-src 'self' https://fonts.gstatic.com; " .
                "connect-src 'self'; " .
                "frame-ancestors 'none';";

            $response->setHeader('Content-Security-Policy', $csp);
        }

        return $response;
    }

    /**
     * Calculate request size
     */
    private function getRequestSize(RequestInterface $request): int
    {
        $size = strlen($request->getBody());

        // Add headers size
        foreach ($request->headers() as $header) {
            $size += strlen($header->getName()) + strlen($header->getValueLine());
        }

        return $size;
    }

    /**
     * Check for suspicious content in request
     */
    private function containsSuspiciousContent(RequestInterface $request): bool
    {
        $content = $request->getBody();
        $uri = $request->getUri()->getPath();

        // Check all input data
        $allData = array_merge(
            $request->getGet() ?? [],
            $request->getPost() ?? [],
            [$uri, $content]
        );

        foreach ($allData as $value) {
            if (is_string($value) && $this->checkSuspiciousString($value)) {
                return true;
            } elseif (is_array($value)) {
                foreach ($value as $subValue) {
                    if (is_string($subValue) && $this->checkSuspiciousString($subValue)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check individual string for suspicious patterns
     */
    private function checkSuspiciousString(string $value): bool
    {
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Rate limiting check
     */
    private function checkRateLimit(RequestInterface $request): bool
    {
        try {
            $throttler = Services::throttler();
            $ip = $request->getIPAddress();

            // Global rate limit: 100 requests per minute
            return $throttler->check($ip, 100, MINUTE) !== false;
        } catch (\Exception $e) {
            // If throttler fails, allow request but log error
            log_message('error', 'Throttler error: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Validate user agent
     */
    private function isValidUserAgent(RequestInterface $request): bool
    {
        $userAgent = $request->getUserAgent()->getAgentString();

        // Allow empty user agents for now (some legitimate tools don't send UA)
        if (empty($userAgent)) {
            return true; // Changed from false to true
        }

        // Block known malicious user agents
        $maliciousAgents = [
            'sqlmap',
            'nikto',
            'nessus',
            'openvas',
            'w3af',
            'skipfish',
            'burp',
            'acunetix'
        ];

        $userAgentLower = strtolower($userAgent);
        foreach ($maliciousAgents as $malicious) {
            if (str_contains($userAgentLower, $malicious)) {
                return false;
            }
        }

        return true;
    }
}