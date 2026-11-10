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

    // Optimización de los documentos al guardarlos (se cifran siempre; esto es solo la recompresión)
    'documentos' => [
        // true: los documentos nuevos se guardan cifrados (AES-256 con la APP_KEY).
        // false: se guardan sin cifrar (igual se recomprimen). Los ya cifrados se siguen abriendo en los dos casos.
        'cifrar' => (bool) env('DOC_CIFRAR', true),
        // false: se guarda el archivo tal cual llegó (igual se cifra)
        'optimizar' => (bool) env('DOC_OPTIMIZAR', true),
        // calidad del jpeg al recomprimir (1-100)
        'jpeg_calidad' => (int) env('DOC_JPEG_CALIDAD', 78),
        // lado más largo permitido en px (2480 = una hoja A4 a 300 dpi)
        'jpeg_lado_maximo' => (int) env('DOC_JPEG_LADO', 2480),
        // ghostscript: screen (72 dpi), ebook (150 dpi), printer (300 dpi). Sin ghostscript instalado los PDF no se recomprimen
        'pdf_perfil' => env('DOC_PDF_PERFIL', 'ebook'),
        // si no se ahorra al menos esto (0.05 = 5%) se guarda el original
        'ahorro_minimo' => (float) env('DOC_AHORRO_MINIMO', 0.05),
    ],

    // Copias de seguridad (sistema:backup). Incluyen la base, los documentos y la configuración.
    'backup' => [
        // discos donde se copia cada backup (el primero es el principal): backups, backups_externo
        'discos' => array_filter(explode(',', (string) env('BACKUP_DISCOS', 'backups'))),
        // clave del zip (AES-256). Sin ella el zip no va cifrado y NO se incluye el .env (que tiene la APP_KEY)
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'incluir_env' => (bool) env('BACKUP_INCLUIR_ENV', true),
        // si el último backup es más viejo que esto, sistema:backup-verificar lo anota como incidente
        'horas_maximas' => 26,

        // cuánto tiempo se guardan
        'retencion' => [
            'todos_dias' => 7,
            'diarios_dias' => 30,
            'semanales_semanas' => 12,
            'mensuales_meses' => 24,
        ],
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
