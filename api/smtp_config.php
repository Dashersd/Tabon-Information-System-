<?php
// api/smtp_config.php - Gmail SMTP Configuration for Barangay Tabon

return [
    /*
    |--------------------------------------------------------------------------
    | SMTP Enable Flag
    |--------------------------------------------------------------------------
    */
    'smtp_enabled'  => true,

    /*
    |--------------------------------------------------------------------------
    | Gmail SMTP Server Settings
    |--------------------------------------------------------------------------
    */
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,
    'smtp_secure'   => 'tls',

    /*
    |--------------------------------------------------------------------------
    | Gmail Credentials
    |--------------------------------------------------------------------------
    */
    'smtp_username' => 'helmandacuma5@gmail.com',
    'smtp_password' => 'byemaoomhlxwgate', // Verified 16-character Google App Password

    /*
    |--------------------------------------------------------------------------
    | Sender Identity
    |--------------------------------------------------------------------------
    */
    'from_email'    => 'helmandacuma5@gmail.com',
    'from_name'     => 'Barangay Tabon Information System'
];
