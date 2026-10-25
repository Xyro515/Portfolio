<?php
/**
 * Copy this file to config.local.php and replace every placeholder.
 * config.local.php contains secrets and must not be published.
 */
return [
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'portfolio_db',
        'user' => 'root',
        'password' => 'YOUR_LOCAL_XAMPP_PASSWORD',
    ],
    'admin' => [
        'username' => 'YOUR_ADMIN_USERNAME',
        // Generate with: php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
        'password_hash' => 'YOUR_PASSWORD_HASH',
    ],
];
