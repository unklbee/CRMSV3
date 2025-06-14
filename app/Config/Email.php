<?php

// Update app/Config/Email.php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'mail';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = '';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 25;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 5;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'html';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use "\r\n" to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use "\r\n" to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;

    public function __construct()
    {
        parent::__construct();

        // Load from environment variables dengan fallback
        $this->fromEmail = env('email.fromEmail', env('EMAIL_FROM_ADDRESS', 'cs@optiontech.id'));
        $this->fromName = env('email.fromName', env('EMAIL_FROM_NAME', 'Computer Repair Shop'));
        $this->protocol = env('email.protocol', env('EMAIL_PROTOCOL', 'mail'));
        $this->SMTPHost = env('email.SMTPHost', env('EMAIL_SMTP_HOST', ''));
        $this->SMTPUser = env('email.SMTPUser', env('EMAIL_SMTP_USER', ''));
        $this->SMTPPass = env('email.SMTPPass', env('EMAIL_SMTP_PASS', ''));
        $this->SMTPPort = (int) env('email.SMTPPort', env('EMAIL_SMTP_PORT', 587));
        $this->SMTPCrypto = env('email.SMTPCrypto', env('EMAIL_SMTP_CRYPTO', 'tls'));
        $this->mailType = env('email.mailType', 'html');
        $this->charset = env('email.charset', 'UTF-8');
        $this->wordWrap = (bool) env('email.wordWrap', true);

        // Fix untuk Hostinger SMTP
        if ($this->SMTPHost === 'smtp.hostinger.com') {
            $this->SMTPCrypto = 'ssl';  // Gunakan SSL bukan TLS untuk Hostinger
            $this->SMTPPort = 465;      // Port SSL untuk Hostinger
        }

        // Development mode fallback
        if (ENVIRONMENT === 'development') {
            if (empty($this->SMTPHost)) {
                $this->protocol = 'mail';
            }
        }
    }
}