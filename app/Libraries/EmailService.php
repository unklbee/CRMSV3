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
    /**
     * Password reset email template - Modern Metronic Style
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
                html,body { padding:0; margin:0; font-family: Inter, Helvetica, 'sans-serif'; } 
                a:hover { color: #009ef7; }
            </style>
        </head>
        <body>
            <div id='kt_app_body_content' style='background-color:#D5D9E2; font-family:Arial,Helvetica,sans-serif; line-height: 1.5; min-height: 100%; font-weight: normal; font-size: 15px; color: #2F3044; margin:0; padding:0; width:100%;'>
                <div style='background-color:#ffffff; padding: 45px 0 34px 0; border-radius: 24px; margin:40px auto; max-width: 600px;'>
                    <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' height='auto' style='border-collapse:collapse'>
                        <tbody>
                            <tr>
                                <td align='center' valign='center' style='text-align:center; padding-bottom: 10px'>
                                    <div style='text-align:center; margin:0 60px 34px 60px'>
                                        <!-- Logo -->
                                        <div style='margin-bottom: 20px'>
                                            <h2 style='color:#181C32; font-size: 28px; font-weight:700; margin:0; font-family:Arial,Helvetica,sans-serif;'>{$appName}</h2>
                                        </div>
                                        
                                        <!-- Icon -->
                                        <div style='margin-bottom: 15px'>
                                            <div style='background-color:#f1f3f6; border-radius:50%; width:80px; height:80px; display:inline-flex; align-items:center; justify-content:center;'>
                                                <span style='font-size:32px; color:#009ef7;'>🔒</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Title and Message -->
                                        <div style='font-size: 14px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;'>
                                            <p style='margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700'>Password Reset Request</p>
                                            <p style='margin-bottom:2px; color:#7E8299'>Hello <strong>{$firstName}</strong>,</p>
                                            <p style='margin-bottom:2px; color:#7E8299'>You have requested to reset your password for your</p>
                                            <p style='margin-bottom:2px; color:#7E8299'>{$appName} account. Click the button below to proceed.</p>
                                        </div>
                                        
                                        <!-- Action Button -->
                                        <a href='{$resetUrl}' target='_blank' style='background-color:#009ef7; border-radius:6px; display:inline-block; padding:11px 19px; color: #FFFFFF; font-size: 14px; font-weight:500; font-family:Arial,Helvetica,sans-serif; text-decoration:none;'>Reset My Password</a>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Information Section -->
                            <tr style='display: flex; justify-content: center; margin:0 60px 35px 60px'>
                                <td align='start' valign='start' style='padding-bottom: 10px;'>
                                    <p style='color:#181C32; font-size: 18px; font-weight: 600; margin-bottom:13px'>Important Security Information</p>
                                    <div style='background: #F9F9F9; border-radius: 12px; padding:35px 30px'>
                                        
                                        <!-- Security Item 1 -->
                                        <div style='display:flex; margin-bottom:20px;'>
                                            <div style='display: flex; justify-content: center; align-items: center; width:40px; height:40px; margin-right:13px; background:#e8f4f8; border-radius:8px;'>
                                                <span style='font-size:18px; color:#009ef7;'>⏰</span>
                                            </div>
                                            <div>
                                                <div>
                                                    <p style='color:#181C32; font-size: 14px; font-weight: 600; margin:0; font-family:Arial,Helvetica,sans-serif'>Link Expires in 1 Hour</p>
                                                    <p style='color:#5E6278; font-size: 13px; font-weight: 500; padding-top:3px; margin:0; font-family:Arial,Helvetica,sans-serif'>This reset link will expire in 1 hour for your security. Please use it as soon as possible.</p>
                                                </div>
                                                <div style='border-bottom:1px dashed #E4E6EF; margin:17px 0 15px 0;'></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Security Item 2 -->
                                        <div style='display:flex; margin-bottom:20px;'>
                                            <div style='display: flex; justify-content: center; align-items: center; width:40px; height:40px; margin-right:13px; background:#e8f4f8; border-radius:8px;'>
                                                <span style='font-size:18px; color:#009ef7;'>🔐</span>
                                            </div>
                                            <div>
                                                <div>
                                                    <p style='color:#181C32; font-size: 14px; font-weight: 600; margin:0; font-family:Arial,Helvetica,sans-serif'>One-Time Use Only</p>
                                                    <p style='color:#5E6278; font-size: 13px; font-weight: 500; padding-top:3px; margin:0; font-family:Arial,Helvetica,sans-serif'>This link can only be used once. After resetting your password, the link will become invalid.</p>
                                                </div>
                                                <div style='border-bottom:1px dashed #E4E6EF; margin:17px 0 15px 0;'></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Security Item 3 -->
                                        <div style='display:flex;'>
                                            <div style='display: flex; justify-content: center; align-items: center; width:40px; height:40px; margin-right:13px; background:#e8f4f8; border-radius:8px;'>
                                                <span style='font-size:18px; color:#009ef7;'>🛡️</span>
                                            </div>
                                            <div>
                                                <div>
                                                    <p style='color:#181C32; font-size: 14px; font-weight: 600; margin:0; font-family:Arial,Helvetica,sans-serif'>Didn't Request This?</p>
                                                    <p style='color:#5E6278; font-size: 13px; font-weight: 500; padding-top:3px; margin:0; font-family:Arial,Helvetica,sans-serif'>If you didn't request this password reset, please ignore this email and your password will remain unchanged.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Alternative Link -->
                            <tr>
                                <td align='center' valign='center' style='font-size: 13px; text-align:center; padding: 0 10px 20px 10px; font-weight: 500; color: #A1A5B7; font-family:Arial,Helvetica,sans-serif'>
                                    <p style='color:#181C32; font-size: 16px; font-weight: 600; margin-bottom:9px'>Can't click the button?</p>
                                    <p style='margin-bottom:2px'>Copy and paste this link into your browser:</p>
                                    <p style='margin-bottom:4px; word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace;'>
                                        <a href='{$resetUrl}' style='color:#009ef7; text-decoration:none;'>{$resetUrl}</a>
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Support Info -->
                            <tr>
                                <td align='center' valign='center' style='font-size: 13px; text-align:center; padding: 0 10px 10px 10px; font-weight: 500; color: #A1A5B7; font-family:Arial,Helvetica,sans-serif'>
                                    <p style='color:#181C32; font-size: 16px; font-weight: 600; margin-bottom:9px'>Need Help?</p>
                                    <p style='margin-bottom:4px'>If you have any questions, please contact our support team at 
                                    <a href='mailto:{$supportEmail}' style='font-weight: 600; color:#009ef7; text-decoration:none;'>{$supportEmail}</a></p>
                                    <p>We're here to help Mon-Fri, 9AM-6PM</p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td align='center' valign='center' style='font-size: 13px; padding:0 15px; text-align:center; font-weight: 500; color: #A1A5B7; font-family:Arial,Helvetica,sans-serif'>
                                    <p>&copy; " . date('Y') . " {$appName}. All rights reserved.</p>
                                    <p style='margin-top:10px; font-size:12px;'>This is an automated message, please do not reply to this email.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Welcome email template - Modern Metronic Style
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
                html,body { padding:0; margin:0; font-family: Inter, Helvetica, 'sans-serif'; } 
                a:hover { color: #009ef7; }
            </style>
        </head>
        <body>
            <div id='kt_app_body_content' style='background-color:#D5D9E2; font-family:Arial,Helvetica,sans-serif; line-height: 1.5; min-height: 100%; font-weight: normal; font-size: 15px; color: #2F3044; margin:0; padding:0; width:100%;'>
                <div style='background-color:#ffffff; padding: 45px 0 34px 0; border-radius: 24px; margin:40px auto; max-width: 600px;'>
                    <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%' height='auto' style='border-collapse:collapse'>
                        <tbody>
                            <tr>
                                <td align='center' valign='center' style='text-align:center; padding-bottom: 10px'>
                                    <div style='text-align:center; margin:0 60px 34px 60px'>
                                        <!-- Logo -->
                                        <div style='margin-bottom: 20px'>
                                            <h2 style='color:#181C32; font-size: 28px; font-weight:700; margin:0; font-family:Arial,Helvetica,sans-serif;'>{$appName}</h2>
                                        </div>
                                        
                                        <!-- Icon -->
                                        <div style='margin-bottom: 25px'>
                                            <div style='background-color:#e8fff3; border-radius:50%; width:80px; height:80px; display:inline-flex; align-items:center; justify-content:center;'>
                                                <span style='font-size:32px; color:#50cd89;'>🎉</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Title and Message -->
                                        <div style='font-size: 14px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;'>
                                            <p style='margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700'>Welcome to {$appName}!</p>
                                            <p style='margin-bottom:2px; color:#7E8299'>Hello <strong>{$firstName}</strong>,</p>
                                            <p style='margin-bottom:2px; color:#7E8299'>Your account has been successfully created!</p>
                                            <p style='margin-bottom:2px; color:#7E8299'>You're now ready to start using our platform.</p>
                                        </div>
                                        
                                        <!-- Action Button -->
                                        <a href='{$loginUrl}' target='_blank' style='background-color:#50cd89; border-radius:6px; display:inline-block; padding:11px 19px; color: #FFFFFF; font-size: 14px; font-weight:500; font-family:Arial,Helvetica,sans-serif; text-decoration:none;'>Login to Your Account</a>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Account Details Section -->
                            <tr style='display: flex; justify-content: center; margin:0 60px 35px 60px'>
                                <td align='start' valign='start' style='padding-bottom: 10px;'>
                                    <p style='color:#181C32; font-size: 18px; font-weight: 600; margin-bottom:13px'>Your Account Details</p>
                                    <div style='background: #F9F9F9; border-radius: 12px; padding:35px 30px'>
                                        
                                        <!-- Account Info -->
                                        <div style='display:flex; margin-bottom:20px;'>
                                            <div style='display: flex; justify-content: center; align-items: center; width:40px; height:40px; margin-right:13px; background:#e8f4f8; border-radius:8px;'>
                                                <span style='font-size:18px; color:#009ef7;'>👤</span>
                                            </div>
                                            <div>
                                                <div>
                                                    <p style='color:#181C32; font-size: 14px; font-weight: 600; margin:0; font-family:Arial,Helvetica,sans-serif'>Username</p>
                                                    <p style='color:#5E6278; font-size: 13px; font-weight: 500; padding-top:3px; margin:0; font-family:Arial,Helvetica,sans-serif'><strong>{$username}</strong></p>
                                                </div>
                                                <div style='border-bottom:1px dashed #E4E6EF; margin:17px 0 15px 0;'></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Login URL -->
                                        <div style='display:flex; margin-bottom:20px;'>
                                            <div style='display: flex; justify-content: center; align-items: center; width:40px; height:40px; margin-right:13px; background:#e8f4f8; border-radius:8px;'>
                                                <span style='font-size:18px; color:#009ef7;'>🔗</span>
                                            </div>
                                            <div>
                                                <div>
                                                    <p style='color:#181C32; font-size: 14px; font-weight: 600; margin:0; font-family:Arial,Helvetica,sans-serif'>Login URL</p>
                                                    <p style='color:#5E6278; font-size: 13px; font-weight: 500; padding-top:3px; margin:0; font-family:Arial,Helvetica,sans-serif; word-break: break-all;'><a href='{$loginUrl}' style='color:#009ef7; text-decoration:none;'>{$loginUrl}</a></p>
                                                </div>
                                                <div style='border-bottom:1px dashed #E4E6EF; margin:17px 0 15px 0;'></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Getting Started -->
                                        <div style='display:flex;'>
                                            <div style='display: flex; justify-content: center; align-items: center; width:40px; height:40px; margin-right:13px; background:#e8f4f8; border-radius:8px;'>
                                                <span style='font-size:18px; color:#009ef7;'>🚀</span>
                                            </div>
                                            <div>
                                                <div>
                                                    <p style='color:#181C32; font-size: 14px; font-weight: 600; margin:0; font-family:Arial,Helvetica,sans-serif'>Ready to Start</p>
                                                    <p style='color:#5E6278; font-size: 13px; font-weight: 500; padding-top:3px; margin:0; font-family:Arial,Helvetica,sans-serif'>You can now login and explore all the features available to you in our platform.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Support Info -->
                            <tr>
                                <td align='center' valign='center' style='font-size: 13px; text-align:center; padding: 0 10px 10px 10px; font-weight: 500; color: #A1A5B7; font-family:Arial,Helvetica,sans-serif'>
                                    <p style='color:#181C32; font-size: 16px; font-weight: 600; margin-bottom:9px'>Need Help Getting Started?</p>
                                    <p style='margin-bottom:4px'>If you have any questions or need assistance, please don't hesitate to contact our support team at 
                                    <a href='mailto:{$supportEmail}' style='font-weight: 600; color:#009ef7; text-decoration:none;'>{$supportEmail}</a></p>
                                    <p>We're here to help Mon-Fri, 9AM-6PM</p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td align='center' valign='center' style='font-size: 13px; padding:0 15px; text-align:center; font-weight: 500; color: #A1A5B7; font-family:Arial,Helvetica,sans-serif'>
                                    <p>&copy; " . date('Y') . " {$appName}. All rights reserved.</p>
                                    <p style='margin-top:10px; font-size:12px;'>Thank you for joining us!</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
}