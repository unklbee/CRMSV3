<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table            = 'password_resets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'email', 'token', 'expires_at', 'used_at', 'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Create a new password reset token
     */
    public function createToken(string $email): string|false
    {
        // Delete any existing tokens for this email
        $this->where('email', $email)->delete();

        // Generate new token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now

        $data = [
            'email' => $email,
            'token' => hash('sha256', $token),
            'expires_at' => $expiresAt
        ];

        if ($this->insert($data)) {
            return $token; // Return plain token for email
        }

        return false;
    }

    /**
     * Verify and get token data
     */
    public function verifyToken(string $token): array|false
    {
        $hashedToken = hash('sha256', $token);

        $resetData = $this->where('token', $hashedToken)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->where('used_at', null)
            ->first();

        return $resetData ?: false;
    }

    /**
     * Mark token as used
     */
    public function markTokenAsUsed(string $token): bool
    {
        $hashedToken = hash('sha256', $token);

        return $this->set('used_at', date('Y-m-d H:i:s'))
            ->where('token', $hashedToken)
            ->update();
    }

    /**
     * Clean expired tokens
     */
    public function cleanExpiredTokens(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
            ->delete();
    }

    /**
     * Check if email has recent reset request (rate limiting)
     */
    public function hasRecentRequest(string $email, int $minutes = 5): bool
    {
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));

        return $this->where('email', $email)
                ->where('created_at >', $since)
                ->countAllResults() > 0;
    }
}