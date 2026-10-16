<?php

return [

    // tablas de la base principal que tienen historico en la base aparte
    'tablas_historico' => [
        'personas', 'legajos', 'documentos', 'historialbajas', 'users',
        'areas', 'cargos', 'categorias', 'estudios', 'titulos', 'familiars',
        'cursos', 'idiomas', 'antecedente_laborals', 'oficinas', 'api_clients',
    ],

    // cuando se considera que algo es un incidente
    'incidentes' => [
        'minutos' => 10,
        'intentos_login' => 5,
        'accesos_denegados' => 3,
        'borrados' => 10,
        'errores' => 5,
    ],

    // intentos de clave: 5 por tanda con 20 s entre intentos; al agotar la 1ra tanda se
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

    // un usuario no puede tener dos sesiones a la vez
    // modo: bloquear (el 2do ingreso se rechaza), reemplazar (el 2do ingreso cierra el 1ro) o apagada
    'sesion_unica' => [
        'modo' => env('SESION_UNICA_MODO', 'bloquear'),
        // si la sesion abierta no tuvo actividad en este tiempo, se considera abandonada
        'inactividad_minutos' => (int) env('SESION_UNICA_INACTIVIDAD', 10),
    ],

    // avisos por correo a los administradores (ademas de la notificacion del panel)
    'alertas' => [
        'correo' => (bool) env('ALERTAS_CORREO', false),
        // correos extra, separados por coma (ej: el de sistemas)
        'destinos' => array_filter(array_map('trim', explode(',', (string) env('ALERTAS_DESTINOS', '')))),
        // solo incidentes de esta gravedad o mas: alta o critica
        'desde' => env('ALERTAS_DESDE', 'alta'),
    ],

    // como se guardan los documentos subidos
    'documentos' => [
        'optimizar' => (bool) env('DOC_OPTIMIZAR', true),
        // calidad del jpeg al recomprimir (1-100)
        'jpeg_calidad' => (int) env('DOC_JPEG_CALIDAD', 78),
        // lado mas largo permitido en px (2480 = una hoja A4 a 300 dpi)
        'jpeg_lado_maximo' => (int) env('DOC_JPEG_LADO', 2480),
        // ghostscript: screen (72dpi), ebook (150dpi), printer (300dpi)
        'pdf_perfil' => env('DOC_PDF_PERFIL', 'ebook'),
        // si no se ahorra al menos esto (0.05 = 5%) se guarda el original
        'ahorro_minimo' => (float) env('DOC_AHORRO_MINIMO', 0.05),
    ],

    'backup' => [
        // discos donde se copia cada backup (el primero es el principal)
        'discos' => array_filter(explode(',', (string) env('BACKUP_DISCOS', 'backups'))),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'incluir_env' => (bool) env('BACKUP_INCLUIR_ENV', true),
        'horas_maximas' => 26,

        // cuanto tiempo se guardan
        'retencion' => [
            'todos_dias' => 7,
            'diarios_dias' => 30,
            'semanales_semanas' => 12,
            'mensuales_meses' => 24,
        ],
    ],

];
