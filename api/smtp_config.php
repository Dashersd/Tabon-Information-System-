<?php
// api/smtp_config.php - Gmail SMTP Configuration for Barangay Tabon

return [
    /*
    |--------------------------------------------------------------------------
    | SMTP Enable Flag
    |--------------------------------------------------------------------------
    | Set to true once you enter your Gmail address and 16-character App Password below.
    | When false, the system runs in local testing mode and displays the PIN on-screen.
    */
    'smtp_enabled'  => false,

    /*
    |--------------------------------------------------------------------------
    | Gmail SMTP Server Settings
    |--------------------------------------------------------------------------
    */
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,           // 587 for TLS or 465 for SSL
    'smtp_secure'   => 'tls',         // 'tls' or 'ssl'

    /*
    |--------------------------------------------------------------------------
    | Gmail Credentials
    |--------------------------------------------------------------------------
    | 1. Use your sender Gmail address.
    | 2. Generate a 16-character App Password from:
    |    Google Account -> Security -> 2-Step Verification -> App Passwords
    |    (Do NOT use your standard account password)
    */
    'smtp_username' => '',            // e.g. 'barangaytabon@gmail.com'
    'smtp_password' => '',            // e.g. 'abcd efgh ijkl mnop'

    /*
    |--------------------------------------------------------------------------
    | Sender Identity
    |--------------------------------------------------------------------------
    */
    'from_email'    => 'barangaytabon@gmail.com',
    'from_name'     => 'Barangay Tabon'
];
