<?php

declare(strict_types=1);

/*
 * Plantilla de configuración de SIGEL.
 *
 * Copie este archivo como config/config.php y ajuste los valores.
 * config/config.php contiene credenciales reales: está excluido de Git
 * y se ubica fuera de public/ para que Apache nunca lo sirva.
 */

return [
    'app' => [
        'name'     => 'SIGEL',
        // 'development' o 'production'. Nunca se muestran errores al usuario.
        'env'      => 'development',
        // Ruta base de la aplicación: '/sigel' en http://127.0.0.1/sigel; '' con el virtual host sigel.local.
        'base_path' => '/sigel',
        'timezone' => 'America/Costa_Rica',
    ],

    'db' => [
        // MariaDB corre en Docker (ver docker-compose.yml); puerto = DB_PORT de .env.
        'host'     => '127.0.0.1',
        'port'     => 3307,
        'name'     => 'sigel',
        // Usuario dedicado con privilegios mínimos; debe coincidir con DB_APP_* de .env.
        'user'     => 'sigel_app',
        'password' => 'cambiar_esta_clave',
        'charset'  => 'utf8mb4',
    ],

    'session' => [
        'name'         => 'SIGEL_SESSION',
        // Segundos de inactividad antes de cerrar la sesión (30 minutos).
        'idle_timeout' => 1800,
    ],

    'security' => [
        // Intentos fallidos permitidos antes de bloquear la cuenta.
        'max_login_attempts' => 5,
        // Minutos que la cuenta permanece bloqueada.
        'lockout_minutes'    => 15,
        // Días de validez del token «Recordarme».
        'remember_me_days'   => 14,
    ],

    'cookies' => [
        'remember'         => 'sigel_remember',
        'per_page'         => 'sigel_per_page',
        'recent_equipment' => 'sigel_recent_equipment',
    ],

    'pagination' => [
        // Lista blanca de registros por página; cualquier otro valor usa el predeterminado.
        'allowed_per_page' => [5, 10, 25, 50],
        'default_per_page' => 10,
    ],

    'uploads' => [
        'path'           => dirname(__DIR__) . '/storage/uploads',
        // Tamaño máximo por imagen: 2 MB.
        'max_size_bytes' => 2 * 1024 * 1024,
        // Lista blanca: extensión permitida => tipo MIME real esperado (verificado con finfo).
        'allowed_types'  => [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
        ],
    ],

    'log' => [
        'path' => dirname(__DIR__) . '/storage/logs/app.log',
    ],
];
