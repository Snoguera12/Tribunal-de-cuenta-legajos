USE torres_corregida1;

-- STORED PROCEDURE DE REPORTE GENERAL PARA EL ADMINISTRADOR
-- OBJETIVO: Estadisticas generales del sistema para panel de control

-- SP #1: sp_admin_reporte_general - Devuelve estadisticas completas del sistema

-- ======================================================================
-- LIMPIEZA PREVIA
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_admin_reporte_general;

DELIMITER //

CREATE PROCEDURE sp_admin_reporte_general()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM legajos WHERE estado = 'activo') AS total_activos,
        (SELECT COUNT(*) FROM legajos WHERE estado = 'de_baja') AS total_bajas,
        (SELECT COUNT(*) FROM legajos WHERE estado = 'traslado') AS total_traslados,
        (SELECT COUNT(*) FROM legajos WHERE estado = 'prestamo') AS total_prestamos,
        (SELECT COUNT(DISTINCT id_persona) FROM legajos WHERE estado = 'activo') AS total_personas,
        (SELECT COUNT(*) FROM usuario WHERE activo = 1) AS total_usuarios_activos,
        (SELECT COUNT(*) FROM sumarios WHERE activo = 1) AS total_sumarios_activos,
        (SELECT COUNT(*) FROM historial_legajos WHERE activo = 1) AS total_historial_activo;

    SELECT c.nombre_cargo, COUNT(l.id_legajo) AS cantidad
    FROM legajos l
    INNER JOIN cargos c ON c.id_cargo = l.id_cargo
    WHERE l.estado = 'activo'
    GROUP BY c.id_cargo, c.nombre_cargo
    ORDER BY cantidad DESC;

    SELECT cat.nombre_categoria, COUNT(l.id_legajo) AS cantidad
    FROM legajos l
    INNER JOIN categorias cat ON cat.id_categoria = l.id_categoria
    WHERE l.estado = 'activo'
    GROUP BY cat.id_categoria, cat.nombre_categoria
    ORDER BY cantidad DESC;

    SELECT o.nombre_oficina, COUNT(l.id_legajo) AS cantidad
    FROM legajos l
    INNER JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.estado = 'activo'
    GROUP BY o.id_oficina, o.nombre_oficina
    ORDER BY cantidad DESC;

    SELECT l.tipo_contrato, COUNT(l.id_legajo) AS cantidad
    FROM legajos l
    WHERE l.estado = 'activo'
    GROUP BY l.tipo_contrato
    ORDER BY cantidad DESC;

    SELECT u.tipo, COUNT(u.id_usuario) AS cantidad
    FROM usuario u
    WHERE u.activo = 1
    GROUP BY u.tipo
    ORDER BY cantidad DESC;

    SELECT
        DATE_FORMAT(s.fecha_registro, '%Y-%m') AS mes,
        COUNT(*) AS cantidad_bajas
    FROM sumarios s
    WHERE s.detalle LIKE 'Baja de legajo%'
    GROUP BY DATE_FORMAT(s.fecha_registro, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 12;
END//

DELIMITER ;
