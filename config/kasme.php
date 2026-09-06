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
];
