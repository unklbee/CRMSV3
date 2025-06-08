<?php

namespace App\Libraries;

use Config\Services;
use CodeIgniter\Email\Email;
use Config\Email as EmailConfig;

/**
 * Email Service Library
 * Wrapper untuk CodeIgniter Email class dengan template system
 */
class EmailService
{
    protected Email $email;
    protected EmailConfig $config;

    public function __construct()
    {
        $this->email = Services::email();
        $this->config = config('Email');
        $this->initializeEmail();
    }

    /**
     * Initialize email configuration
     */
    private function initializeEmail(): void
    {
        // Basic email configuration
        $config = [
            'protocol' => $this->config->protocol ?? 'mail',
            'mailPath' => $this->config->mailPath ?? '/usr/sbin/sendmail',
            'SMTPHost' => $this->config->SMTPHost ?? '',
            'SMTPUser' => $this->config->SMTPUser ?? '',
            'SMTPPass' => $this->config->SMTPPass ?? '',
            'SMTPPort' => $this->config->SMTPPort ?? 25,
            'SMTPTimeout' => $this->config->SMTPTimeout ?? 5,
            'SMTPKeepAlive' => $this->config->SMTPKeepAlive ?? false,
            'SMTPCrypto' => $this->config->SMTPCrypto ?? 'tls',
            'wordWrap' => $this->config->wordWrap ?? true,
            'wrapChars' => $this->config->wrapChars ?? 76,
            'mailType' => $this->config->mailType ?? 'html',
            'charset' => $this->config->charset ?? 'UTF-8',
            'validate' => $this->config->validate ?? false,
            'priority' => $this->config->priority ?? 3,
            'CRLF' => $this->config->CRLF ?? "\r\n",
            'newline' => $this->config->newline ?? "\r\n",
            'BCCBatchMode' => $this->config->BCCBatchMode ?? false,
            'BCCBatchSize' => $this->config->BCCBatchSize ?? 200,
            'DSN' => $this->config->DSN ?? false,
        ];

        $this->email->initialize($config);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(string $email, string $token, string $firstName): bool
    {
        try {
            $resetUrl = site_url("auth/reset-password/{$token}");

            $this->email->setFrom($this->getFromEmail(), $this->getFromName());
            $this->email->setTo($email);
            $this->email->setSubject('Password Reset Request - ' . $this->getAppName());

            $message = $this->getPasswordResetTemplate($firstName, $resetUrl);
            $this->email->setMessage($message);

            $result = $this->email->send();

            if (!$result) {
                log_message('error', 'Email send failed: ' . $this->email->printDebugger());
                return false;
            }

            log_message('info', "Password reset email sent to: {$email}");
            return true;

        } catch (\Exception $e) {
            log_message('error', 'EmailService error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome email for new users
     */
    public function sendWelcomeEmail(string $email, string $firstName, string $username): bool
    {
        try {
            $this->email->setFrom($this->getFromEmail(), $this->getFromName());
            $this->email->setTo($email);
            $this->email->setSubject('Welcome to ' . $this->getAppName());

            $message = $this->getWelcomeTemplate($firstName, $username);
            $this->email->setMessage($message);

            $result = $this->email->send();

            if (!$result) {
                log_message('error', 'Welcome email send failed: ' . $this->email->printDebugger());
                return false;
            }

            log_message('info', "Welcome email sent to: {$email}");
            return true;

        } catch (\Exception $e) {
            log_message('error', 'Welcome email error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send custom email with template
     */
    public function sendCustomEmail(string $to, string $subject, string $template, array $data = []): bool
    {
        try {
            $this->email->setFrom($this->getFromEmail(), $this->getFromName());
            $this->email->setTo($to);
            $this->email->setSubject($subject);

            $message = $this->parseTemplate($template, $data);
            $this->email->setMessage($message);

            $result = $this->email->send();

            if (!$result) {
                log_message('error', 'Custom email send failed: ' . $this->email->printDebugger());
                return false;
            }

            return true;

        } catch (\Exception $e) {
            log_message('error', 'Custom email error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Password reset email template
     */
    private function getPasswordResetTemplate(string $firstName, string $resetUrl): string
    {
        $appName = $this->getAppName();
        $supportEmail = $this->getFromEmail();

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset - {$appName}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #fff; padding: 30px; border: 1px solid #dee2e6; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #6c757d; }
                .btn { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .btn:hover { background: #0056b3; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$appName}</h1>
                    <h2>Password Reset Request</h2>
                </div>
                
                <div class='content'>
                    <p>Hello <strong>{$firstName}</strong>,</p>
                    
                    <p>You have requested to reset your password for your {$appName} account. Click the button below to set a new password:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$resetUrl}' class='btn'>Reset My Password</a>
                    </div>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 3px;'>{$resetUrl}</p>
                    
                    <div class='warning'>
                        <strong>Important:</strong>
                        <ul>
                            <li>This link will expire in <strong>1 hour</strong></li>
                            <li>The link can only be used <strong>once</strong></li>
                            <li>If you did not request this password reset, please ignore this email</li>
                        </ul>
                    </div>
                    
                    <p>If you have any questions, please contact our support team at <a href='mailto:{$supportEmail}'>{$supportEmail}</a></p>
                </div>
                
                <div class='footer'>
                    <p>&copy; " . date('Y') . " {$appName}. All rights reserved.</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Welcome email template
     */
    private function getWelcomeTemplate(string $firstName, string $username): string
    {
        $appName = $this->getAppName();
        $loginUrl = site_url('auth/signin');
        $supportEmail = $this->getFromEmail();

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to {$appName}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #fff; padding: 30px; border: 1px solid #dee2e6; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #6c757d; }
                .btn { display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .btn:hover { background: #218838; }
                .info-box { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to {$appName}!</h1>
                </div>
                
                <div class='content'>
                    <p>Hello <strong>{$firstName}</strong>,</p>
                    
                    <p>Welcome to {$appName}! Your account has been successfully created.</p>
                    
                    <div class='info-box'>
                        <strong>Your Account Details:</strong><br>
                        <strong>Username:</strong> {$username}<br>
                        <strong>Login URL:</strong> <a href='{$loginUrl}'>{$loginUrl}</a>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='{$loginUrl}' class='btn'>Login to Your Account</a>
                    </div>
                    
                    <p>If you have any questions or need assistance, please don't hesitate to contact our support team at <a href='mailto:{$supportEmail}'>{$supportEmail}</a></p>
                    
                    <p>Thank you for joining us!</p>
                </div>
                
                <div class='footer'>
                    <p>&copy; " . date('Y') . " {$appName}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Parse template with data
     */
    private function parseTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    /**
     * Get from email address
     */
    private function getFromEmail(): string
    {
        return $this->config->fromEmail ?? env('EMAIL_FROM_ADDRESS', 'noreply@yourapp.com');
    }

    /**
     * Get from name
     */
    private function getFromName(): string
    {
        return $this->config->fromName ?? env('EMAIL_FROM_NAME', 'Your App');
    }

    /**
     * Get application name
     */
    private function getAppName(): string
    {
        return env('APP_NAME', 'Your Application');
    }

    /**
     * Test email configuration
     */
    public function testConnection(): array
    {
        try {
            $this->email->setFrom($this->getFromEmail(), $this->getFromName());
            $this->email->setTo($this->getFromEmail());
            $this->email->setSubject('Email Test - ' . $this->getAppName());
            $this->email->setMessage('This is a test email to verify email configuration.');

            $result = $this->email->send();

            return [
                'success' => $result,
                'message' => $result ? 'Email sent successfully!' : 'Failed to send email',
                'debug' => $result ? '' : $this->email->printDebugger()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage(),
                'debug' => ''
            ];
        }
    }
}