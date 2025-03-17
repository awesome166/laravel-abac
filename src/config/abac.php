<?php

return [
    'user_model' => env('ABAC_USER_MODEL', App\Models\User::class),
    'cache_ttl' => 86400,
    'account_status_check' => true,
];
