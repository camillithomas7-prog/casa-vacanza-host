<?php
// Copia questo file come config.php (NON committare config.php su Git)
// Su Hostinger: caricare config.php manualmente via File Manager

return [
    'db' => [
        'host'     => 'localhost',
        'name'     => 'u749757264_apppatrizia',
        'user'     => 'u749757264_apppatrizia',
        'pass'     => '123Provaprova@',
        'charset'  => 'utf8mb4',
    ],
    'site' => [
        'name'     => 'Casa Vacanza',
        'url'      => 'https://sienna-caribou-611155.hostingersite.com',
        'email'    => 'info@casavacanza.it',
        'phone'    => '+39 000 000 0000',
        'currency' => 'EUR',
        'locale'   => 'it_IT',
        'timezone' => 'Europe/Rome',
    ],
    'auth' => [
        'session_name' => 'cv_session',
        'session_ttl'  => 60 * 60 * 24 * 30, // 30 giorni
    ],
    'admin_default' => [
        'email'    => 'admin@casavacanza.it',
        'password' => 'admin123', // cambialo dopo il primo login
    ],
];
