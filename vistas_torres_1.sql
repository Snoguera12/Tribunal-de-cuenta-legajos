USE torres_corregida1;

-- LISTADO COMPLETO DE VISTAS PARA torres_corregida1
-- OBJETIVO: Simplificar consultas frecuentes y complejas del sistema

-- VISTA #1:  vista_personal_activo         - Personal activo con todos sus datos relacionados
-- VISTA #2:  vista_personal_baja           - Personal dado de baja con datos relacionados
-- VISTA #3:  vista_legajos_completo        - Todos los legajos con datos relacionados sin filtro de estado
-- VISTA #4:  vista_titulos_activos         - Titulos activos con datos de la persona
-- VISTA #5:  vista_cursos_activos          - Cursos activos con datos de la persona
-- VISTA #6:  vista_idiomas_activos         - Idiomas activos con datos de la persona
-- VISTA #7:  vista_familiares_activos      - Familiares activos con datos de la persona
-- VISTA #8:  vista_documentos_activos      - Documentos activos con datos de la persona
-- VISTA #9:  vista_sumarios_activos        - Sumarios activos con datos de la persona
-- VISTA #10: vista_historial_activo        - Historial activo con datos de la persona
-- VISTA #11: vista_usuarios_activos        - Usuarios activos con datos de la persona
-- VISTA #12: vista_reporte_estadisticas    - Estadisticas generales del sistema

-- ======================================================================
-- LIMPIEZA PREVIA
-- ======================================================================
DROP VIEW IF EXISTS vista_personal_activo;
DROP VIEW IF EXISTS vista_personal_baja;
DROP VIEW IF EXISTS vista_legajos_completo;
DROP VIEW IF EXISTS vista_titulos_activos;
DROP VIEW IF EXISTS vista_cursos_activos;
DROP VIEW IF EXISTS vista_idiomas_activos;
DROP VIEW IF EXISTS vista_familiares_activos;
DROP VIEW IF EXISTS vista_documentos_activos;
DROP VIEW IF EXISTS vista_sumarios_activos;
DROP VIEW IF EXISTS vista_historial_activo;
DROP VIEW IF EXISTS vista_usuarios_activos;
DROP VIEW IF EXISTS vista_reporte_estadisticas;

-- ======================================================================
-- VISTA #1: personal activo
-- ======================================================================
CREATE VIEW vista_personal_activo AS
SELECT
    l.id_legajo,
    p.id_persona,
    p.apellido,
    p.nombre,
    p.dni,
    p.cuil,
    p.genero,
    p.fecha_nacimiento,
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
    l.estado,
    c.nombre_cargo,
    cat.nombre_categoria,
    o.nombre_oficina
FROM legajos l
INNER JOIN personas p ON p.id_persona = l.id_persona
LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
WHERE l.estado = 'activo';

-- ======================================================================
-- VISTA #2: personal de baja
-- ======================================================================
CREATE VIEW vista_personal_baja AS
SELECT
    l.id_legajo,
    p.id_persona,
    p.apellido,
    p.nombre,
    p.dni,
    p.cuil,
    l.fecha_ingreso,
    l.tipo_contrato,
    l.estado,
    c.nombre_cargo,
    cat.nombre_categoria,
    o.nombre_oficina,
    s.detalle AS motivo_baja,
    s.fecha_registro AS fecha_baja
FROM legajos l
INNER JOIN personas p ON p.id_persona = l.id_persona
LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
LEFT JOIN sumarios s ON s.id_sumario = (
    SELECT id_sumario FROM sumarios
    WHERE id_legajo = l.id_legajo AND detalle LIKE 'Baja de legajo%'
    ORDER BY fecha_registro DESC LIMIT 1
)
WHERE l.estado = 'de_baja';

-- ======================================================================
-- VISTA #3: todos los legajos
-- ======================================================================
CREATE VIEW vista_legajos_completo AS
SELECT
    l.id_legajo,
    p.id_persona,
    p.apellido,
    p.nombre,
    p.dni,
    p.cuil,
    l.fecha_ingreso,
    l.fecha_ingreso_administracion,
    l.tipo_contrato,
    l.estado,
    c.nombre_cargo,
    cat.nombre_categoria,
    o.nombre_oficina
FROM legajos l
INNER JOIN personas p ON p.id_persona = l.id_persona
LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina;

-- ======================================================================
-- VISTA #4: titulos activos
-- ======================================================================
CREATE VIEW vista_titulos_activos AS
SELECT
    t.*,
    p.apellido,
    p.nombre,
    p.dni
FROM titulos t
INNER JOIN personas p ON p.id_persona = t.id_persona
WHERE t.activo = 1;

-- ======================================================================
-- VISTA #5: cursos activos
-- ======================================================================
CREATE VIEW vista_cursos_activos AS
SELECT
    c.*,
    p.apellido,
    p.nombre,
    p.dni
FROM cursos c
INNER JOIN personas p ON p.id_persona = c.id_persona
WHERE c.activo = 1;

-- ======================================================================
-- VISTA #6: idiomas activos
-- ======================================================================
CREATE VIEW vista_idiomas_activos AS
SELECT
    i.*,
    p.apellido,
    p.nombre,
    p.dni
FROM idiomas i
INNER JOIN personas p ON p.id_persona = i.id_persona
WHERE i.activo = 1;

-- ======================================================================
-- VISTA #7: familiares activos
-- ======================================================================
CREATE VIEW vista_familiares_activos AS
SELECT
    f.*,
    p.apellido,
    p.nombre,
    p.dni
FROM familiar f
INNER JOIN personas p ON p.id_persona = f.id_persona
WHERE f.activo = 1;

-- ======================================================================
-- VISTA #8: documentos activos
-- ======================================================================
CREATE VIEW vista_documentos_activos AS
SELECT
    d.*,
    p.apellido,
    p.nombre,
    p.dni
FROM documentos d
INNER JOIN personas p ON p.id_persona = d.id_persona
WHERE d.activo = 1;

-- ======================================================================
-- VISTA #9: sumarios activos
-- ======================================================================
CREATE VIEW vista_sumarios_activos AS
SELECT
    s.*,
    p.apellido,
    p.nombre,
    p.dni
FROM sumarios s
INNER JOIN legajos l ON l.id_legajo = s.id_legajo
INNER JOIN personas p ON p.id_persona = l.id_persona
WHERE s.activo = 1;

-- ======================================================================
-- VISTA #10: historial activo
-- ======================================================================
CREATE VIEW vista_historial_activo AS
SELECT
    h.*,
    p.apellido,
    p.nombre,
    p.dni
FROM historial_legajos h
INNER JOIN legajos l ON l.id_legajo = h.id_legajo
INNER JOIN personas p ON p.id_persona = l.id_persona
WHERE h.activo = 1;

-- ======================================================================
-- VISTA #11: usuarios activos
-- ======================================================================
CREATE VIEW vista_usuarios_activos AS
SELECT
    u.id_usuario,
    u.usuario,
    u.tipo,
    u.id_legajo,
    u.primer_ingreso,
    u.fecha_creacion,
    u.ultimo_login,
    p.apellido,
    p.nombre,
    p.dni,
    p.email,
    p.telefono
FROM usuario u
INNER JOIN legajos l ON l.id_legajo = u.id_legajo
INNER JOIN personas p ON p.id_persona = l.id_persona
WHERE u.activo = 1;

-- ======================================================================
-- VISTA #12: estadisticas generales
-- ======================================================================
CREATE VIEW vista_reporte_estadisticas AS
SELECT
    (SELECT COUNT(*) FROM legajos WHERE estado = 'activo') AS total_activos,
    (SELECT COUNT(*) FROM legajos WHERE estado = 'de_baja') AS total_bajas,
    (SELECT COUNT(*) FROM legajos WHERE estado = 'traslado') AS total_traslados,
    (SELECT COUNT(*) FROM legajos WHERE estado = 'prestamo') AS total_prestamos,
    (SELECT COUNT(*) FROM usuario WHERE activo = 1 AND tipo = 'empleado') AS usuarios_empleado,
    (SELECT COUNT(*) FROM usuario WHERE activo = 1 AND tipo = 'rrhh') AS usuarios_rrhh,
    (SELECT COUNT(*) FROM usuario WHERE activo = 1 AND tipo = 'funcionario') AS usuarios_funcionario,
    (SELECT COUNT(*) FROM usuario WHERE activo = 1 AND tipo = 'administrador') AS usuarios_administrador,
    (SELECT COUNT(*) FROM sumarios WHERE activo = 1) AS total_sumarios_activos,
    (SELECT COUNT(DISTINCT id_persona) FROM legajos WHERE estado = 'activo') AS total_personas;
