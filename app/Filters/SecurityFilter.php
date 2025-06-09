<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Security Filter - Additional security checks
 */
class SecurityFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check for suspicious patterns
        $userAgent = $request->getUserAgent()->getAgentString();
        $suspiciousAgents = ['bot', 'crawler', 'spider', 'scraper'];

        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                log_message('warning', "Suspicious user agent detected: {$userAgent}");
                // Optionally block or rate limit
            }
        }

        // Check for SQL injection patterns in request data
        $this->checkForSQLInjection($request);

        // Check for XSS patterns
        $this->checkForXSS($request);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add additional security headers
        return $response->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }

    private function checkForSQLInjection(RequestInterface $request): void
    {
        $suspicious_patterns = [
            '/union.*select/i',
            '/select.*from/i',
            '/insert.*into/i',
            '/delete.*from/i',
            '/update.*set/i',
            '/drop.*table/i',
            '/exec.*sp_/i',
            '/script.*alert/i'
        ];

        $data = array_merge($request->getGet(), $request->getPost());

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                foreach ($suspicious_patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        log_message('critical', "SQL Injection attempt detected: {$key} = {$value}");
                        throw new \CodeIgniter\Security\Exceptions\SecurityException('Malicious input detected');
                    }
                }
            }
        }
    }

    private function checkForXSS(RequestInterface $request): void
    {
        $xss_patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i'
        ];

        $data = array_merge($request->getGet(), $request->getPost());

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                foreach ($xss_patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        log_message('critical', "XSS attempt detected: {$key} = {$value}");
                        throw new \CodeIgniter\Security\Exceptions\SecurityException('Malicious script detected');
                    }
                }
            }
        }
    }
}