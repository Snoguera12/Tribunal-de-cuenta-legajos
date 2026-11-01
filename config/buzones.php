<?php

return [
    'licencias' => [
        'nombre' => 'Licencias',
        'url' => env('LICENCIAS_BUZON_URL'),
        'clave' => env('LICENCIAS_BUZON_CLAVE'),
    ],

    // Para sumar otro sistema: agregá un bloque igual y sus variables en el .env
    'otro_sistema' => [
        'nombre' => 'Otro sistema',
        'url' => env('OTRO_SISTEMA_BUZON_URL'),
        'clave' => env('OTRO_SISTEMA_BUZON_CLAVE'),
    ],
];