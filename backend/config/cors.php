<?php

return [

    // Define las rutas del backend a las que se les aplicará la política CORS.
    // 'api/*' significa que todas las rutas que empiecen por /api tendrán CORS.

    'paths' => ['api/*'],

    // Métodos HTTP permitidos.
    // El '*' significa que permite TODOS:
    // GET, POST, PUT, PATCH, DELETE, OPTIONS, etc.
    'allowed_methods' => ['*'],

    // Dominios (frontends) autorizados para consumir la API.
    // Solo las peticiones desde estas URLs serán aceptadas.
    'allowed_origins' => [
        'http://localhost:5173',  // Servidor de desarrollo de Vue con Vite
        'http://localhost:5175',  // Puerto host actual del frontend en Docker
        'http://localhost:3000',  // Otro puerto común (React / pruebas)
    ],

    // Patrón: acepta cualquier puerto de localhost (evita romper CORS si el
    // puerto del frontend cambia de nuevo en desarrollo).
    'allowed_origins_patterns' => [
        '#^http://localhost:\d+$#',
    ],

    // Headers permitidos en la petición.
    // '*' permite todos, por ejemplo:
    // Content-Type, Authorization, Accept, etc.
    'allowed_headers' => ['*'],

    // Headers que el navegador podrá leer explícitamente en la respuesta.
    // Vacío significa que no se exponen headers adicionales.
    'exposed_headers' => [],

    // Tiempo en segundos que el navegador puede guardar en caché
    // la respuesta preflight (OPTIONS).
    // 0 significa que no se cachea.
    'max_age' => 0,

    // Indica si se permiten credenciales como:
    // cookies, sesiones o encabezados Authorization con credenciales.
    // false = no usa cookies ni sesiones.
    // Para JWT normalmente puede estar en false.
    'supports_credentials' => false,
];
