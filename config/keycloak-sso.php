<?php

// Sisanya (HRIS, org_levels, roles) memakai default package syifa/keycloak-sso.
return [
    'client_id' => env('KEYCLOAK_CLIENT_ID'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
    'redirect_uri' => env('KEYCLOAK_REDIRECT_URI', env('APP_URL').'/auth/keycloak/callback'),
    'base_url' => env('KEYCLOAK_BASE_URL'),
    'realm' => env('KEYCLOAK_REALM'),

    'routes' => [
        'prefix' => 'auth/keycloak',
        'middleware' => ['web'],
        'redirect_after_login' => '/home',
        'redirect_after_logout' => '/login',
    ],

    'user' => [
        'model' => \App\Models\User::class,
        'sub_column' => 'keycloak_sub',
        // NIP dari klaim "nip" (fallback "preferred_username"), dicocokkan ke users.nip.
        'match_by' => ['keycloak_sub', 'nip'],
        // Tidak auto-buat akun: user harus sudah ada (NIP diisi admin).
        'provision' => false,
    ],

    'hris_directory' => [
        'base_url' => env('HRIS_API_BASE_URL'),
        'token' => env('HRIS_API_TOKEN'),
    ],
];
