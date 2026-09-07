<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allow Public Registration
    |--------------------------------------------------------------------------
    |
    | When set to false, new user registrations are closed once the instance
    | owner (first user) has been created. If no users exist yet, registration
    | is always open so the instance owner can set up the application.
    |
    */
    'allow_registration' => (bool) env('ALLOW_REGISTRATION', false),

    /*
    |--------------------------------------------------------------------------
    | PHP CLI Binary Path
    |--------------------------------------------------------------------------
    |
    | Optional absolute path to the PHP CLI binary to recommend in the
    | cPanel Cron Job guide (e.g. /opt/alt/php84/usr/bin/php). Defaults to
    | PHP_BINARY when left empty.
    |
    */
    'php_cli_binary' => env('KASME_PHP_CLI_BINARY', PHP_BINARY ?: 'php'),

    /*
    |--------------------------------------------------------------------------
    | PHP CLI Extensions
    |--------------------------------------------------------------------------
    |
    | Optional comma-separated list of extensions to include as -d extension=X.so
    | flags in the recommended Cron Job command (e.g. bcmath,dom,fileinfo,mbstring,zip).
    |
    */
    'php_cli_extensions' => env('KASME_PHP_CLI_EXTENSIONS', ''),
];
