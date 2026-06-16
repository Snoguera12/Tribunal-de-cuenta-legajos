USE torres_corregida1;

-- LISTADO COMPLETO DE STORED PROCEDURES PARA EL ROL EMPLEADO
-- OBJETIVO: Consultar solo la propia informacion en tablas principales
-- ACCESO: Solo usuarios con tipo = 'empleado' pueden usar estos procedimientos

-- 1. TABLA: personas
-- SP #1:  sp_empl_obtener_persona       - Muestra los datos propios de la persona

-- 2. TABLA: legajos
-- SP #2:  sp_empl_obtener_legajo        - Muestra los datos propios del legajo

-- 3. TABLA: titulos
-- SP #3:  sp_empl_obtener_titulos       - Muestra los propios titulos

-- 4. TABLA: cursos
-- SP #4:  sp_empl_obtener_cursos        - Muestra los propios cursos

-- 5. TABLA: idiomas
-- SP #5:  sp_empl_obtener_idiomas       - Muestra los propios idiomas

-- 6. TABLA: familiar
-- SP #6:  sp_empl_obtener_familiares    - Muestra los propios familiares

-- 7. TABLA: antecedente_laboral
-- SP #7:  sp_empl_obtener_antecedentes  - Muestra los propios antecedentes laborales

-- 8. TABLA: historial_legajos
-- SP #8:  sp_empl_obtener_historial     - Muestra el propio historial

-- 9. TABLA: sumarios
-- SP #9:  sp_empl_obtener_sumarios      - Muestra los propios sumarios

-- 10. TABLA: documentos
-- SP #10: sp_empl_obtener_documentos    - Muestra los propios documentos

-- 11. TABLA: usuario
-- SP #11: sp_empl_obtener_usuario       - Muestra los propios datos de usuario

-- 12. CONSULTA COMBINADA
-- SP #12: sp_empl_legajo_completo       - Muestra toda la propia informacion en una sola consulta

-- 13. CONSULTAS ADICIONALES
-- SP #13: sp_empl_buscar_documento_por_tipo  - Busca entre sus propios documentos por tipo
-- SP #14: sp_empl_obtener_datos_contacto     - Muestra solo sus datos de contacto

-- ======================================================================
-- LIMPIEZA PREVIA DE STORED PROCEDURES
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_empl_obtener_persona;
DROP PROCEDURE IF EXISTS sp_empl_obtener_legajo;
DROP PROCEDURE IF EXISTS sp_empl_obtener_titulos;
DROP PROCEDURE IF EXISTS sp_empl_obtener_cursos;
DROP PROCEDURE IF EXISTS sp_empl_obtener_idiomas;
DROP PROCEDURE IF EXISTS sp_empl_obtener_familiares;
DROP PROCEDURE IF EXISTS sp_empl_obtener_antecedentes;
DROP PROCEDURE IF EXISTS sp_empl_obtener_historial;
DROP PROCEDURE IF EXISTS sp_empl_obtener_sumarios;
DROP PROCEDURE IF EXISTS sp_empl_obtener_documentos;
DROP PROCEDURE IF EXISTS sp_empl_obtener_usuario;
DROP PROCEDURE IF EXISTS sp_empl_legajo_completo;
DROP PROCEDURE IF EXISTS sp_empl_buscar_documento_por_tipo;
DROP PROCEDURE IF EXISTS sp_empl_obtener_datos_contacto;

DELIMITER //

-- ======================================================================
-- 1. TABLA: personas
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_persona(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT p.* FROM personas p
    INNER JOIN legajos l ON l.id_persona = p.id_persona
    INNER JOIN usuario u ON u.id_legajo = l.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
END//

-- ======================================================================
-- 2. TABLA: legajos
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_legajo(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT l.*, p.apellido, p.nombre, p.dni,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    INNER JOIN usuario u ON u.id_legajo = l.id_legajo
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE u.id_usuario = p_id_usuario_empl;
END//

-- ======================================================================
-- 3. TABLA: titulos
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_titulos(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_persona INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT l.id_persona INTO v_id_persona
    FROM usuario u INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
    SELECT * FROM titulos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_fin DESC;
END//

-- ======================================================================
-- 4. TABLA: cursos
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_cursos(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_persona INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT l.id_persona INTO v_id_persona
    FROM usuario u INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
    SELECT * FROM cursos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_inicio DESC;
END//

-- ======================================================================
-- 5. TABLA: idiomas
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_idiomas(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_persona INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT l.id_persona INTO v_id_persona
    FROM usuario u INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
    SELECT * FROM idiomas WHERE id_persona = v_id_persona AND activo = 1 ORDER BY nombre;
END//

-- ======================================================================
-- 6. TABLA: familiar
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_familiares(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_persona INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT l.id_persona INTO v_id_persona
    FROM usuario u INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
    SELECT * FROM familiar WHERE id_persona = v_id_persona AND activo = 1 ORDER BY relacion_empleado, apellido_familiar;
END//

-- ======================================================================
-- 7. TABLA: antecedente_laboral
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_antecedentes(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_persona INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT l.id_persona INTO v_id_persona
    FROM usuario u INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
    SELECT * FROM antecedente_laboral WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_inicio DESC;
END//

-- ======================================================================
-- 8. TABLA: historial_legajos
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_historial(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_legajo INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT id_legajo INTO v_id_legajo FROM usuario WHERE id_usuario = p_id_usuario_empl;
    SELECT * FROM historial_legajos WHERE id_legajo = v_id_legajo AND activo = 1 ORDER BY fecha_registro DESC;
END//

-- ======================================================================
-- 9. TABLA: sumarios
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_sumarios(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_legajo INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT id_legajo INTO v_id_legajo FROM usuario WHERE id_usuario = p_id_usuario_empl;
    SELECT * FROM sumarios WHERE id_legajo = v_id_legajo AND activo = 1 ORDER BY fecha_registro DESC;
END//

-- ======================================================================
-- 10. TABLA: documentos
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_documentos(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_persona INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT l.id_persona INTO v_id_persona
    FROM usuario u INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
    SELECT * FROM documentos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY creado_en DESC;
END//

-- ======================================================================
-- 11. TABLA: usuario
-- ======================================================================

CREATE PROCEDURE sp_empl_obtener_usuario(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT id_usuario, usuario, tipo, id_legajo, primer_ingreso, activo, fecha_creacion, ultimo_login
    FROM usuario WHERE id_usuario = p_id_usuario_empl;
END//

-- ======================================================================
-- 12. CONSULTA COMBINADA
-- ======================================================================

CREATE PROCEDURE sp_empl_legajo_completo(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_legajo INT;
    DECLARE v_id_persona INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT id_legajo INTO v_id_legajo FROM usuario WHERE id_usuario = p_id_usuario_empl;
    SELECT id_persona INTO v_id_persona FROM legajos WHERE id_legajo = v_id_legajo;
    SELECT l.*, p.apellido, p.nombre, p.dni, p.cuil, p.genero, p.fecha_nacimiento,
        p.estado_civil, p.cantidad_hijos, p.provincia_residencia, p.ciudad_residencia,
        p.domicilio_datos, p.telefono, p.telefono_emergencia, p.email,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.id_legajo = v_id_legajo;
    SELECT * FROM titulos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_fin DESC;
    SELECT * FROM cursos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_inicio DESC;
    SELECT * FROM idiomas WHERE id_persona = v_id_persona AND activo = 1 ORDER BY nombre;
    SELECT * FROM familiar WHERE id_persona = v_id_persona AND activo = 1 ORDER BY relacion_empleado;
    SELECT * FROM antecedente_laboral WHERE id_persona = v_id_persona AND activo = 1 ORDER BY fecha_inicio DESC;
    SELECT * FROM historial_legajos WHERE id_legajo = v_id_legajo AND activo = 1 ORDER BY fecha_registro DESC;
    SELECT * FROM sumarios WHERE id_legajo = v_id_legajo AND activo = 1 ORDER BY fecha_registro DESC;
    SELECT * FROM documentos WHERE id_persona = v_id_persona AND activo = 1 ORDER BY creado_en DESC;
    SELECT id_usuario, usuario, tipo, primer_ingreso, activo, fecha_creacion, ultimo_login
    FROM usuario WHERE id_legajo = v_id_legajo;
END//

-- ======================================================================
-- 13. CONSULTAS ADICIONALES
-- ======================================================================

CREATE PROCEDURE sp_empl_buscar_documento_por_tipo(
    IN p_id_usuario_empl INT,
    IN p_tipo_doc ENUM('DNI','TITULO','CURSOS','LICENCIA','ACTA_DE_NACIMIENTO','CERTIFICADO_ESCOLARIDAD','CERTIFICADO_DEFUNCION','SUMARIO','RESOLUCION','CERTIFICADO_DE_CASAMIENTO','FOTO_PERFIL','CURRICULUM','OTRO')
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_persona INT;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT l.id_persona INTO v_id_persona
    FROM usuario u INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
    SELECT * FROM documentos
    WHERE id_persona = v_id_persona AND tipo_doc = p_tipo_doc AND activo = 1
    ORDER BY creado_en DESC;
END//

CREATE PROCEDURE sp_empl_obtener_datos_contacto(
    IN p_id_usuario_empl INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_empl AND activo = 1;
    IF v_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo empleados pueden usar este procedimiento.';
    END IF;
    SELECT p.telefono, p.telefono_emergencia, p.email,
        p.provincia_residencia, p.ciudad_residencia, p.domicilio_datos
    FROM personas p
    INNER JOIN legajos l ON l.id_persona = p.id_persona
    INNER JOIN usuario u ON u.id_legajo = l.id_legajo
    WHERE u.id_usuario = p_id_usuario_empl;
END//

DELIMITER ;
