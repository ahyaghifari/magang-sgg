<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super admin
    |--------------------------------------------------------------------------
    |
    | Daftar email yang diperlakukan sebagai super admin — satu-satunya yang
    | punya akses penuh ke panel Filament (/admin). Isi lewat SUPER_ADMIN_EMAILS
    | di .env, dipisah koma. Contoh:
    |
    |   SUPER_ADMIN_EMAILS="owner@example.com, admin@example.com"
    |
    */

    'super_admin_emails' => array_values(array_filter(array_map(
        fn ($email) => mb_strtolower(trim($email)),
        explode(',', (string) env('SUPER_ADMIN_EMAILS', '')),
    ))),

];
