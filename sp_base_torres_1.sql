USE torres_corregida1;

-- STORED PROCEDURES BASE INTERNOS PARA torres_corregida1
-- OBJETIVO: Contienen la logica SQL pura, sin validacion de rol.
-- Son llamados por los SPs de cada rol (sp_admin, sp_func, sp_rrhh, sp_empleado).
-- NO se exponen directamente a ningun usuario externo ni tienen GRANT de EXECUTE.
-- Consolidacion de redundancias: reemplaza 38 SPs duplicados entre roles.

-- sp_base_listar_personas               - Lista todas las personas
-- sp_base_listar_legajos                 - Lista todos los legajos con datos relacionados
-- sp_base_listar_legajos_por_estado      - Filtra legajos por estado
-- sp_base_listar_legajos_por_persona     - Lista todos los legajos de una persona
-- sp_base_buscar_persona                 - Busqueda libre en personas
-- sp_base_buscar_legajo                  - Busqueda de legajos con filtros
-- sp_base_listar_categorias              - Lista categorias
-- sp_base_listar_cargos                  - Lista cargos
-- sp_base_listar_oficinas                - Lista oficinas
-- sp_base_listar_titulos                 - Lista titulos por id_persona
-- sp_base_listar_cursos                  - Lista cursos por id_persona
-- sp_base_listar_idiomas                 - Lista idiomas por id_persona
-- sp_base_listar_familiares              - Lista familiares por id_persona
-- sp_base_listar_antecedentes            - Lista antecedentes por id_persona
-- sp_base_listar_historial               - Lista historial por id_legajo
-- sp_base_listar_sumarios                - Lista sumarios por id_legajo
-- sp_base_listar_documentos              - Lista documentos por id_persona
-- sp_base_listar_historico_personas      - Lista historico_personas por id_persona
-- sp_base_listar_historico_legajos       - Lista historico_legajos por id_persona
-- sp_base_listar_historico_titulos       - Lista historico_titulos por id_persona
-- sp_base_listar_historico_cursos        - Lista historico_cursos por id_persona
-- sp_base_listar_historico_idiomas       - Lista historico_idiomas por id_persona
-- sp_base_listar_historico_familiar      - Lista historico_familiar por id_persona
-- sp_base_listar_historico_antecedente   - Lista historico_antecedente_laboral por id_persona
-- sp_base_listar_historico_historial     - Lista historico_historial_legajos por id_persona
-- sp_base_listar_historico_sumarios      - Lista historico_sumarios por id_persona
-- sp_base_listar_historico_documentos    - Lista historico_documentos por id_persona
-- sp_base_listar_historico_usuario       - Lista historico_usuario por id_legajo
-- sp_base_legajo_completo                - Devuelve todos los datos de un legajo en result sets

-- ======================================================================
-- LIMPIEZA PREVIA
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_base_listar_personas;
DROP PROCEDURE IF EXISTS sp_base_listar_legajos;
DROP PROCEDURE IF EXISTS sp_base_listar_legajos_por_estado;
DROP PROCEDURE IF EXISTS sp_base_listar_legajos_por_persona;
DROP PROCEDURE IF EXISTS sp_base_buscar_persona;
DROP PROCEDURE IF EXISTS sp_base_buscar_legajo;
DROP PROCEDURE IF EXISTS sp_base_listar_categorias;
DROP PROCEDURE IF EXISTS sp_base_listar_cargos;
DROP PROCEDURE IF EXISTS sp_base_listar_oficinas;
DROP PROCEDURE IF EXISTS sp_base_listar_titulos;
DROP PROCEDURE IF EXISTS sp_base_listar_cursos;
DROP PROCEDURE IF EXISTS sp_base_listar_idiomas;
DROP PROCEDURE IF EXISTS sp_base_listar_familiares;
DROP PROCEDURE IF EXISTS sp_base_listar_antecedentes;
DROP PROCEDURE IF EXISTS sp_base_listar_historial;
DROP PROCEDURE IF EXISTS sp_base_listar_sumarios;
DROP PROCEDURE IF EXISTS sp_base_listar_documentos;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_personas;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_legajos;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_titulos;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_cursos;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_idiomas;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_familiar;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_antecedente;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_historial;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_sumarios;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_documentos;
DROP PROCEDURE IF EXISTS sp_base_listar_historico_usuario;
DROP PROCEDURE IF EXISTS sp_base_legajo_completo;

DELIMITER //

CREATE PROCEDURE sp_base_listar_personas()
BEGIN
    SELECT * FROM personas ORDER BY apellido, nombre;
END//

CREATE PROCEDURE sp_base_listar_legajos()
BEGIN
    SELECT l.*, p.apellido, p.nombre, p.dni,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    ORDER BY p.apellido, p.nombre;
END//

CREATE PROCEDURE sp_base_listar_legajos_por_estado(
    IN p_estado ENUM('activo','de_baja','traslado','prestamo')
)
BEGIN
    SELECT l.*, p.apellido, p.nombre, p.dni,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.estado = p_estado
    ORDER BY p.apellido, p.nombre;
END//

CREATE PROCEDURE sp_base_listar_legajos_por_persona(
    IN p_id_persona INT
)
BEGIN
    SELECT l.*, c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.id_persona = p_id_persona
    ORDER BY l.fecha_registro DESC;
END//

CREATE PROCEDURE sp_base_buscar_persona(
    IN p_busqueda VARCHAR(100)
)
BEGIN
    SELECT DISTINCT p.*, l.id_legajo, l.estado,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM personas p
    LEFT JOIN legajos l ON l.id_legajo = (
        SELECT id_legajo FROM legajos
        WHERE id_persona = p.id_persona
        ORDER BY id_legajo DESC LIMIT 1
    )
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE p.apellido LIKE CONCAT('%', p_busqueda, '%')
        OR p.nombre LIKE CONCAT('%', p_busqueda, '%')
        OR p.dni LIKE CONCAT('%', p_busqueda, '%')
        OR p.cuil LIKE CONCAT('%', p_busqueda, '%')
    ORDER BY p.apellido, p.nombre;
END//

CREATE PROCEDURE sp_base_buscar_legajo(
    IN p_busqueda VARCHAR(100),
    IN p_id_cargo INT,
    IN p_id_categoria INT,
    IN p_id_oficina INT
)
BEGIN
    SELECT l.*, p.apellido, p.nombre, p.dni,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE (p_busqueda IS NULL
        OR p.apellido LIKE CONCAT('%', p_busqueda, '%')
        OR p.nombre LIKE CONCAT('%', p_busqueda, '%')
        OR p.dni LIKE CONCAT('%', p_busqueda, '%'))
        AND (p_id_cargo IS NULL OR l.id_cargo = p_id_cargo)
        AND (p_id_categoria IS NULL OR l.id_categoria = p_id_categoria)
        AND (p_id_oficina IS NULL OR l.id_oficina = p_id_oficina)
    ORDER BY p.apellido, p.nombre;
END//

CREATE PROCEDURE sp_base_listar_categorias()
BEGIN
    SELECT * FROM categorias ORDER BY nombre_categoria;
END//

CREATE PROCEDURE sp_base_listar_cargos()
BEGIN
    SELECT * FROM cargos ORDER BY nombre_cargo;
END//

CREATE PROCEDURE sp_base_listar_oficinas()
BEGIN
    SELECT * FROM oficinas ORDER BY nombre_oficina;
END//

CREATE PROCEDURE sp_base_listar_titulos(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM titulos WHERE id_persona = p_id_persona ORDER BY fecha_fin DESC;
END//

CREATE PROCEDURE sp_base_listar_cursos(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM cursos WHERE id_persona = p_id_persona ORDER BY fecha_inicio DESC;
END//

CREATE PROCEDURE sp_base_listar_idiomas(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM idiomas WHERE id_persona = p_id_persona ORDER BY nombre;
END//

CREATE PROCEDURE sp_base_listar_familiares(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM familiar WHERE id_persona = p_id_persona ORDER BY relacion_empleado, apellido_familiar;
END//

CREATE PROCEDURE sp_base_listar_antecedentes(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM antecedente_laboral WHERE id_persona = p_id_persona ORDER BY fecha_inicio DESC;
END//

CREATE PROCEDURE sp_base_listar_historial(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM historial_legajos WHERE id_legajo = p_id_legajo ORDER BY fecha_registro DESC;
END//

CREATE PROCEDURE sp_base_listar_sumarios(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM sumarios WHERE id_legajo = p_id_legajo ORDER BY fecha_registro DESC;
END//

CREATE PROCEDURE sp_base_listar_documentos(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM documentos WHERE id_persona = p_id_persona ORDER BY creado_en DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_personas(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM historico_personas WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_legajos(
    IN p_id_persona INT
)
BEGIN
    SELECT hl.* FROM historico_legajos hl
    INNER JOIN legajos l ON l.id_legajo = hl.id_legajo
    WHERE l.id_persona = p_id_persona
    ORDER BY hl.fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_titulos(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM historico_titulos WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_cursos(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM historico_cursos WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_idiomas(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM historico_idiomas WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_familiar(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM historico_familiar WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_antecedente(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM historico_antecedente_laboral WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_historial(
    IN p_id_persona INT
)
BEGIN
    SELECT hh.* FROM historico_historial_legajos hh
    INNER JOIN legajos l ON l.id_legajo = hh.id_legajo
    WHERE l.id_persona = p_id_persona
    ORDER BY hh.fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_sumarios(
    IN p_id_persona INT
)
BEGIN
    SELECT hs.* FROM historico_sumarios hs
    INNER JOIN legajos l ON l.id_legajo = hs.id_legajo
    WHERE l.id_persona = p_id_persona
    ORDER BY hs.fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_documentos(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM historico_documentos WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_listar_historico_usuario(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM historico_usuario WHERE id_legajo = p_id_legajo ORDER BY fecha_accion DESC;
END//

CREATE PROCEDURE sp_base_legajo_completo(
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_id_persona INT;
    SELECT id_persona INTO v_id_persona FROM legajos WHERE id_legajo = p_id_legajo;
    SELECT l.*, p.apellido, p.nombre, p.dni, p.cuil, p.genero, p.fecha_nacimiento,
        p.estado_civil, p.cantidad_hijos, p.provincia_residencia, p.ciudad_residencia,
        p.domicilio_datos, p.telefono, p.telefono_emergencia, p.email,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.id_legajo = p_id_legajo;
    SELECT * FROM titulos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_fin DESC;
    SELECT * FROM cursos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_inicio DESC;
    SELECT * FROM idiomas WHERE id_persona = v_id_persona AND activo = 1 ORDER BY nombre;
    SELECT * FROM familiar WHERE id_persona = v_id_persona AND activo = 1 ORDER BY relacion_empleado;
    SELECT * FROM antecedente_laboral WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_inicio DESC;
    SELECT * FROM historial_legajos WHERE id_legajo = p_id_legajo AND activo = 1 ORDER BY fecha_registro DESC;
    SELECT * FROM sumarios WHERE id_legajo = p_id_legajo AND activo = 1 ORDER BY fecha_registro DESC;
    SELECT * FROM documentos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY creado_en DESC;
    SELECT id_usuario, usuario, tipo, primer_ingreso, activo, fecha_creacion, ultimo_login
    FROM usuario WHERE id_legajo = p_id_legajo;
END//

DELIMITER ;
