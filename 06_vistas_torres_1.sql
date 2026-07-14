USE torres_corregida1;

-- ============================================================
-- SISTEMA TORRES_CORREGIDA1 — VISTAS
-- ============================================================
--
-- INDICE DE VISTAS:
-- ============================================================
--
-- [PERSONAL]
--   vista_personal_activo          — Todo el personal con legajo activo
--                                    Campos: id_legajo, dni, apellido, nombre,
--                                    genero, estado_civil, email, telefono,
--                                    categoria, cargo, oficina, tipo_contrato
--   vista_personal_baja            — Personal dado de baja con motivo y fecha
--   vista_listado_empleados        — Vista simplificada para listado rapido
--                                    (equivalente al resultado de sp_listado_empleados)
--
-- [LEGAJOS]
--   vista_legajos_completo         — Todos los legajos con datos de persona,
--                                    cargo, categoria y oficina (sin filtro de estado)
--   vista_legajos_activos          — Solo legajos en estado activo
--   vista_legajos_traslado         — Legajos en traslado o prestamo
--
-- [SEGURIDAD Y SISTEMA]
--   vista_usuarios_activos         — Usuarios activos con datos de persona
--   vista_usuarios_bloqueados      — Usuarios bloqueados con fecha y contador
--   vista_intentos_recientes       — Ultimos 100 intentos de login (exitos y fallos)
--   vista_solicitudes_pendientes   — Solicitudes de recuperacion sin atender
--   vista_sesiones_activas         — Sesiones abiertas actualmente (no expiradas)
--   vista_log_reciente             — Ultimas 200 entradas del log del sistema
--   vista_incidentes_abiertos      — Incidentes no resueltos por severidad
--
-- [AUDITORIA]
--   vista_cambios_hoy              — Todos los cambios registrados hoy en historicas
--
-- ============================================================

-- ============================================================
-- LIMPIEZA PREVIA
-- ============================================================
DROP VIEW IF EXISTS vista_personal_activo;
DROP VIEW IF EXISTS vista_personal_baja;
DROP VIEW IF EXISTS vista_listado_empleados;
DROP VIEW IF EXISTS vista_legajos_completo;
DROP VIEW IF EXISTS vista_legajos_activos;
DROP VIEW IF EXISTS vista_legajos_traslado;
DROP VIEW IF EXISTS vista_usuarios_activos;
DROP VIEW IF EXISTS vista_usuarios_bloqueados;
DROP VIEW IF EXISTS vista_intentos_recientes;
DROP VIEW IF EXISTS vista_solicitudes_pendientes;
DROP VIEW IF EXISTS vista_sesiones_activas_hoy;
DROP VIEW IF EXISTS vista_log_reciente;
DROP VIEW IF EXISTS vista_incidentes_abiertos;
DROP VIEW IF EXISTS vista_cambios_hoy;

-- ============================================================
-- VISTAS DE PERSONAL
-- ============================================================

CREATE VIEW vista_personal_activo AS
SELECT
    l.id_legajo,
    p.id_persona,
    p.dni,
    p.cuil,
    p.apellido,
    p.nombre,
    p.genero,
    p.fecha_nacimiento,
    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
    p.estado_civil,
    p.cantidad_hijos,
    p.provincia_residencia,
    p.ciudad_residencia,
    p.domicilio_datos,
    p.telefono,
    p.telefono_emergencia,
    p.email,
    l.fecha_ingreso,
    l.fecha_ingreso_administracion,
    l.tipo_contrato,
    l.estado AS estado_legajo,
    cat.nombre_categoria AS categoria,
    c.nombre_cargo       AS cargo,
    o.nombre_oficina     AS oficina
FROM legajos l
INNER JOIN personas p   ON p.id_persona   = l.id_persona
LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
LEFT JOIN cargos c       ON c.id_cargo       = l.id_cargo
LEFT JOIN oficinas o     ON o.id_oficina     = l.id_oficina
WHERE l.estado = 'activo'
ORDER BY p.apellido, p.nombre;

-- ============================================================

CREATE VIEW vista_personal_baja AS
SELECT
    l.id_legajo,
    p.dni,
    p.apellido,
    p.nombre,
    p.email,
    p.telefono,
    l.estado AS estado_legajo,
    l.tipo_contrato,
    cat.nombre_categoria AS categoria,
    c.nombre_cargo       AS cargo,
    o.nombre_oficina     AS oficina,
    s.detalle            AS motivo_baja,
    s.fecha_registro     AS fecha_baja
FROM legajos l
INNER JOIN personas p    ON p.id_persona    = l.id_persona
LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
LEFT JOIN cargos c       ON c.id_cargo       = l.id_cargo
LEFT JOIN oficinas o     ON o.id_oficina     = l.id_oficina
LEFT JOIN sumarios s     ON s.id_sumario = (
    SELECT id_sumario FROM sumarios
    WHERE id_legajo = l.id_legajo AND detalle LIKE 'Baja de legajo%'
    ORDER BY fecha_registro DESC LIMIT 1
)
WHERE l.estado = 'de_baja'
ORDER BY s.fecha_registro DESC;

-- ============================================================

CREATE VIEW vista_listado_empleados AS
SELECT
    l.id_legajo,
    p.dni,
    p.nombre,
    p.apellido,
    l.estado            AS estado_legajo,
    p.genero,
    cat.nombre_categoria AS categoria,
    p.email,
    p.telefono
FROM legajos l
INNER JOIN personas p    ON p.id_persona    = l.id_persona
LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
ORDER BY p.apellido, p.nombre;

-- ============================================================
-- VISTAS DE LEGAJOS
-- ============================================================

CREATE VIEW vista_legajos_completo AS
SELECT
    l.id_legajo,
    l.estado,
    l.tipo_contrato,
    l.fecha_ingreso,
    l.fecha_ingreso_administracion,
    p.id_persona,
    p.dni,
    p.apellido,
    p.nombre,
    p.email,
    p.telefono,
    cat.nombre_categoria AS categoria,
    c.nombre_cargo       AS cargo,
    o.nombre_oficina     AS oficina,
    u.usuario,
    u.tipo              AS tipo_usuario,
    u.activo            AS usuario_activo,
    u.ultimo_login
FROM legajos l
INNER JOIN personas p    ON p.id_persona    = l.id_persona
LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
LEFT JOIN cargos c       ON c.id_cargo       = l.id_cargo
LEFT JOIN oficinas o     ON o.id_oficina     = l.id_oficina
LEFT JOIN usuario u      ON u.id_legajo      = l.id_legajo
ORDER BY p.apellido, p.nombre;

-- ============================================================

CREATE VIEW vista_legajos_activos AS
SELECT * FROM vista_legajos_completo
WHERE estado = 'activo';

-- ============================================================

CREATE VIEW vista_legajos_traslado AS
SELECT * FROM vista_legajos_completo
WHERE estado IN ('traslado', 'prestamo');

-- ============================================================
-- VISTAS DE SEGURIDAD Y SISTEMA
-- ============================================================

CREATE VIEW vista_usuarios_activos AS
SELECT
    u.id_usuario,
    u.usuario,
    u.tipo,
    u.primer_ingreso,
    u.fecha_creacion,
    u.ultimo_login,
    l.id_legajo,
    l.estado        AS estado_legajo,
    p.dni,
    p.apellido,
    p.nombre,
    p.email,
    p.telefono
FROM usuario u
INNER JOIN legajos l ON l.id_legajo   = u.id_legajo
INNER JOIN personas p ON p.id_persona = l.id_persona
WHERE u.activo = 1
ORDER BY u.tipo, p.apellido, p.nombre;

-- ============================================================

CREATE VIEW vista_usuarios_bloqueados AS
SELECT
    u.id_usuario,
    u.usuario,
    u.tipo,
    p.dni,
    p.apellido,
    p.nombre,
    p.email,
    p.telefono,
    i.intentos_fallidos,
    i.fecha_bloqueo,
    i.ip            AS ip_ultimo_intento
FROM usuario u
INNER JOIN legajos l  ON l.id_legajo   = u.id_legajo
INNER JOIN personas p ON p.id_persona  = l.id_persona
INNER JOIN (
    SELECT usuario,
           MAX(intentos_fallidos) AS intentos_fallidos,
           MAX(fecha_bloqueo)     AS fecha_bloqueo,
           MAX(ip)                AS ip
    FROM intentos_login
    WHERE bloqueado = 1
    GROUP BY usuario
) i ON i.usuario = u.usuario
WHERE u.activo = 0
ORDER BY i.fecha_bloqueo DESC;

-- ============================================================

CREATE VIEW vista_intentos_recientes AS
SELECT
    id_intento,
    usuario,
    ip,
    fecha_hora,
    resultado,
    intentos_fallidos,
    bloqueado
FROM intentos_login
ORDER BY fecha_hora DESC
LIMIT 100;

-- ============================================================

CREATE VIEW vista_solicitudes_pendientes AS
SELECT
    sr.id_solicitud,
    sr.id_usuario,
    sr.usuario,
    sr.fecha_solicitud,
    sr.estado,
    p.apellido,
    p.nombre,
    p.email,
    p.telefono
FROM solicitudes_recuperacion sr
INNER JOIN usuario u  ON u.id_usuario  = sr.id_usuario
INNER JOIN legajos l  ON l.id_legajo   = u.id_legajo
INNER JOIN personas p ON p.id_persona  = l.id_persona
WHERE sr.estado = 'pendiente'
ORDER BY sr.fecha_solicitud ASC;

-- ============================================================

CREATE VIEW vista_sesiones_activas_hoy AS
SELECT
    sa.id_sesion,
    sa.id_usuario,
    u.usuario,
    u.tipo,
    sa.ip,
    sa.fecha_inicio,
    sa.ultima_actividad,
    sa.fecha_expiracion,
    TIMESTAMPDIFF(MINUTE, sa.fecha_inicio, NOW()) AS minutos_activa,
    p.apellido,
    p.nombre
FROM sesiones_activas sa
INNER JOIN usuario u  ON u.id_usuario  = sa.id_usuario
INNER JOIN legajos l  ON l.id_legajo   = u.id_legajo
INNER JOIN personas p ON p.id_persona  = l.id_persona
WHERE sa.activa = 1 AND sa.fecha_expiracion > NOW()
ORDER BY sa.ultima_actividad DESC;

-- ============================================================

CREATE VIEW vista_log_reciente AS
SELECT
    id_log,
    fecha_hora,
    usuario_nombre,
    tipo_usuario,
    ip,
    accion,
    tabla_afectada,
    id_registro,
    resultado,
    detalle,
    duracion_ms
FROM log_sistema
ORDER BY fecha_hora DESC
LIMIT 200;

-- ============================================================

CREATE VIEW vista_incidentes_abiertos AS
SELECT
    id_incidente,
    fecha_hora,
    tipo_incidente,
    nivel_severidad,
    usuario_nombre,
    ip,
    descripcion,
    estado
FROM incidentes
WHERE estado IN ('abierto', 'en_revision')
ORDER BY
    FIELD(nivel_severidad, 'critico', 'alto', 'medio', 'bajo'),
    fecha_hora DESC;

-- ============================================================
-- VISTA DE AUDITORIA
-- ============================================================

CREATE VIEW vista_cambios_hoy AS
SELECT 'personas'             AS tabla, id_historico_persona  AS id_historico,
       id_persona             AS id_registro, tipo_cambio,
       fecha_accion, usuario_accion
FROM historico_personas WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'legajos',              id_historico_legajo,
       id_legajo,              tipo_cambio, fecha_accion, usuario_accion
FROM historico_legajos WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'titulos',              id_historico_titulo,
       id_titulo,              tipo_cambio, fecha_accion, usuario_accion
FROM historico_titulos WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'cursos',               id_historico_curso,
       id_curso,               tipo_cambio, fecha_accion, usuario_accion
FROM historico_cursos WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'idiomas',              id_historico_idioma,
       id_idioma,              tipo_cambio, fecha_accion, usuario_accion
FROM historico_idiomas WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'familiar',             id_historico_familiar,
       id_familiar,            tipo_cambio, fecha_accion, usuario_accion
FROM historico_familiar WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'antecedente_laboral',  id_historico_antecedente,
       id_antecedente,         tipo_cambio, fecha_accion, usuario_accion
FROM historico_antecedente_laboral WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'historial_legajos',    id_historico_historial,
       id_historial,           tipo_cambio, fecha_accion, usuario_accion
FROM historico_historial_legajos WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'sumarios',             id_historico_sumario,
       id_sumario,             tipo_cambio, fecha_accion, usuario_accion
FROM historico_sumarios WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'documentos',           id_historico_documento,
       id_documento,           tipo_cambio, fecha_accion, usuario_accion
FROM historico_documentos WHERE DATE(fecha_accion) = CURDATE()
UNION ALL
SELECT 'usuario',              id_historico_usuario,
       id_usuario,             tipo_cambio, fecha_accion, usuario_accion
FROM historico_usuario WHERE DATE(fecha_accion) = CURDATE()
ORDER BY fecha_accion DESC;

-- ============================================================
-- PERMISOS SOBRE VISTAS
-- ============================================================
GRANT SELECT ON torres_corregida1.vista_personal_activo       TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_personal_baja         TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_listado_empleados     TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_legajos_completo      TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_legajos_activos       TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_legajos_traslado      TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_usuarios_activos      TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_usuarios_bloqueados   TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_intentos_recientes    TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_solicitudes_pendientes TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_sesiones_activas_hoy  TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_log_reciente          TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_incidentes_abiertos   TO 'rol_administrador';
GRANT SELECT ON torres_corregida1.vista_cambios_hoy           TO 'rol_administrador';

GRANT SELECT ON torres_corregida1.vista_personal_activo       TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.vista_personal_baja         TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.vista_listado_empleados     TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.vista_legajos_completo      TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.vista_legajos_activos       TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.vista_solicitudes_pendientes TO 'rol_rrhh';

GRANT SELECT ON torres_corregida1.vista_personal_activo       TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.vista_personal_baja         TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.vista_listado_empleados     TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.vista_legajos_completo      TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.vista_legajos_activos       TO 'rol_funcionario';
