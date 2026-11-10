<?php

return [
    // Hora de fin de la jornada laboral (HH:MM), usada por la duración "Hasta el fin de la jornada laboral"
    'fin_jornada' => env('FIN_JORNADA', '18:00'),

    // Intentos de clave: 5 por tanda con 20 s entre intentos; al agotar la 1ra tanda se
    // bloquea 1 hora; al agotar la 2da el bloqueo es definitivo (lo levanta el administrador).
    // Si se frena en 4 intentos, a los 10 min se devuelven (hasta 3 veces). Tope total: 12 claves mal.
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
