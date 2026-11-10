<?php

return [
    // Hora de fin de la jornada laboral (HH:MM), usada por la duración "Hasta el fin de la jornada laboral"
    'fin_jornada' => env('FIN_JORNADA', '18:00'),

    // Intentos de clave: 5 por tanda con 20 s entre intentos; al agotar la 1ra tanda se
    // bloquea 1 hora; al agotar la 2da el bloqueo es definitivo (lo levanta el administrador).
    // Si se frena en 4 intentos, a los 10 min se devuelven (hasta 3 veces). Tope total: 12 claves mal.
    // Un usuario no puede tener dos sesiones a la vez.
    // modo: bloquear (el 2do ingreso se rechaza), reemplazar (el 2do ingreso cierra el 1ro) o apagada
    'sesion_unica' => [
        'modo' => env('SESION_UNICA_MODO', 'bloquear'),
        // si la sesión abierta no tuvo actividad en este tiempo, se considera abandonada
        'inactividad_minutos' => (int) env('SESION_UNICA_INACTIVIDAD', 10),
    ],

    // Antivirus para los archivos que se suben o llegan por la API (apagado por defecto).
    // Necesita un programa instalado en el servidor, por ejemplo ClamAV: sudo apt install clamav
    'antivirus' => [
        'activo' => (bool) env('ANTIVIRUS_ACTIVO', false),
        // el archivo a revisar se agrega al final. Sale con 0 = limpio, 1 = amenaza, otro número = error del programa
        'comando' => env('ANTIVIRUS_COMANDO', 'clamscan --no-summary'),
        // si el programa no funciona: 'permitir' (se acepta el archivo y se anota) o 'rechazar'
        'si_falla' => env('ANTIVIRUS_SI_FALLA', 'permitir'),
        'timeout' => (int) env('ANTIVIRUS_TIMEOUT', 30),
    ],

    'login' => [
        'intentos_por_tanda' => 5,
        'espera_segundos' => 20,
        'bloqueo_minutos' => 60,
        'tandas_hasta_bloqueo_definitivo' => 2,
        'tope_total' => 12,
        'reinicio_intentos' => 4,
        'reinicio_minutos' => 10,
        'reinicios_maximos' => 3,
    ],
];
