<?php

return [
    // Discos donde se guardan los archivos comprimidos de la auditoría vieja (auditoria:archivar).
    // Conviene agregar un disco en otro equipo o unidad: AUDITORIA_DISCOS=local,backups
    'discos' => array_filter(explode(',', env('AUDITORIA_DISCOS', 'local'))),
];
