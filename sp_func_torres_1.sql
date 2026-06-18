USE torres_corregida1;

-- LISTADO COMPLETO DE STORED PROCEDURES DE CONSULTA PARA EL FUNCIONARIO
-- OBJETIVO: Permitir al funcionario consultar todas las tablas principales e historicas sin poder modificar nada
-- NOTA: Todos los SPs reciben IN p_id_usuario_func INT como primer parametro para validar el rol.

-- 1. TABLA: personas
-- SP #1:  sp_func_obtener_persona             - Obtiene una persona por id
-- SP #2:  sp_func_listar_personas             - Lista todas las personas

-- 2. TABLA: legajos
-- SP #3:  sp_func_obtener_legajo              - Obtiene un legajo por id con datos relacionados
-- SP #4:  sp_func_listar_legajos              - Lista todos los legajos con datos relacionados
-- SP #5:  sp_func_listar_legajos_por_estado   - Lista legajos filtrados por estado

-- 3. TABLA: usuario
-- SP #6:  sp_func_obtener_usuario             - Obtiene un usuario por id_legajo
-- SP #7:  sp_func_listar_usuarios             - Lista todos los usuarios

-- 4. TABLA: titulos
-- SP #8:  sp_func_obtener_titulo              - Obtiene un titulo por id
-- SP #9:  sp_func_listar_titulos              - Lista todos los titulos de un legajo

-- 5. TABLA: cursos
-- SP #10: sp_func_obtener_curso               - Obtiene un curso por id
-- SP #11: sp_func_listar_cursos               - Lista todos los cursos de un legajo

-- 6. TABLA: idiomas
-- SP #12: sp_func_obtener_idioma              - Obtiene un idioma por id
-- SP #13: sp_func_listar_idiomas              - Lista todos los idiomas de un legajo

-- 7. TABLA: familiar
-- SP #14: sp_func_obtener_familiar            - Obtiene un familiar por id
-- SP #15: sp_func_listar_familiares           - Lista todos los familiares de un legajo

-- 8. TABLA: antecedente_laboral
-- SP #16: sp_func_obtener_antecedente         - Obtiene un antecedente por id
-- SP #17: sp_func_listar_antecedentes         - Lista todos los antecedentes de un legajo

-- 9. TABLA: historial_legajos
-- SP #18: sp_func_obtener_historial           - Obtiene un historial por id
-- SP #19: sp_func_listar_historial            - Lista todo el historial de un legajo

-- 10. TABLA: sumarios
-- SP #20: sp_func_obtener_sumario             - Obtiene un sumario por id
-- SP #21: sp_func_listar_sumarios             - Lista todos los sumarios de un legajo

-- 11. TABLA: documentos
-- SP #22: sp_func_obtener_documento           - Obtiene un documento por id
-- SP #23: sp_func_listar_documentos           - Lista todos los documentos de un legajo

-- 12. TABLA: categorias
-- SP #24: sp_func_listar_categorias           - Lista todas las categorias

-- 13. TABLA: cargos
-- SP #25: sp_func_listar_cargos               - Lista todos los cargos

-- 14. TABLA: oficinas
-- SP #26: sp_func_listar_oficinas             - Lista todas las oficinas

-- 15. TABLA: historico_personas
-- SP #27: sp_func_listar_historico_personas   - Lista el historico de una persona

-- 16. TABLA: historico_legajos
-- SP #28: sp_func_listar_historico_legajos    - Lista el historico de un legajo

-- 17. TABLA: historico_titulos
-- SP #29: sp_func_listar_historico_titulos    - Lista el historico de titulos de un legajo

-- 18. TABLA: historico_cursos
-- SP #30: sp_func_listar_historico_cursos     - Lista el historico de cursos de un legajo

-- 19. TABLA: historico_idiomas
-- SP #31: sp_func_listar_historico_idiomas    - Lista el historico de idiomas de un legajo

-- 20. TABLA: historico_familiar
-- SP #32: sp_func_listar_historico_familiar   - Lista el historico de familiares de un legajo

-- 21. TABLA: historico_antecedente_laboral
-- SP #33: sp_func_listar_historico_antecedente - Lista el historico de antecedentes de un legajo

-- 22. TABLA: historico_historial_legajos
-- SP #34: sp_func_listar_historico_historial  - Lista el historico del historial de un legajo

-- 23. TABLA: historico_sumarios
-- SP #35: sp_func_listar_historico_sumarios   - Lista el historico de sumarios de un legajo

-- 24. TABLA: historico_documentos
-- SP #36: sp_func_listar_historico_documentos - Lista el historico de documentos de un legajo

-- 25. TABLA: historico_usuario
-- SP #37: sp_func_listar_historico_usuario    - Lista el historico de un usuario

-- 26. CONSULTAS COMBINADAS
-- SP #38: sp_func_legajo_completo             - Obtiene todos los datos de un legajo y su persona en una sola consulta
-- SP #39: sp_func_buscar_persona              - Busca personas por apellido, nombre o dni

-- 27. CONSULTAS ADICIONALES
-- SP #40: sp_func_buscar_legajo               - Busca legajos por apellido, cargo, categoria u oficina
-- SP #41: sp_func_listar_legajos_por_persona  - Lista todos los legajos de una persona
-- SP #42: sp_func_reporte_personal_activo     - Lista todo el personal activo con datos principales

-- ======================================================================
-- LIMPIEZA PREVIA DE STORED PROCEDURES
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_func_obtener_persona;
DROP PROCEDURE IF EXISTS sp_func_listar_personas;
DROP PROCEDURE IF EXISTS sp_func_obtener_legajo;
DROP PROCEDURE IF EXISTS sp_func_listar_legajos;
DROP PROCEDURE IF EXISTS sp_func_listar_legajos_por_estado;
DROP PROCEDURE IF EXISTS sp_func_obtener_usuario;
DROP PROCEDURE IF EXISTS sp_func_listar_usuarios;
DROP PROCEDURE IF EXISTS sp_func_obtener_titulo;
DROP PROCEDURE IF EXISTS sp_func_listar_titulos;
DROP PROCEDURE IF EXISTS sp_func_obtener_curso;
DROP PROCEDURE IF EXISTS sp_func_listar_cursos;
DROP PROCEDURE IF EXISTS sp_func_obtener_idioma;
DROP PROCEDURE IF EXISTS sp_func_listar_idiomas;
DROP PROCEDURE IF EXISTS sp_func_obtener_familiar;
DROP PROCEDURE IF EXISTS sp_func_listar_familiares;
DROP PROCEDURE IF EXISTS sp_func_obtener_antecedente;
DROP PROCEDURE IF EXISTS sp_func_listar_antecedentes;
DROP PROCEDURE IF EXISTS sp_func_obtener_historial;
DROP PROCEDURE IF EXISTS sp_func_listar_historial;
DROP PROCEDURE IF EXISTS sp_func_obtener_sumario;
DROP PROCEDURE IF EXISTS sp_func_listar_sumarios;
DROP PROCEDURE IF EXISTS sp_func_obtener_documento;
DROP PROCEDURE IF EXISTS sp_func_listar_documentos;
DROP PROCEDURE IF EXISTS sp_func_listar_categorias;
DROP PROCEDURE IF EXISTS sp_func_listar_cargos;
DROP PROCEDURE IF EXISTS sp_func_listar_oficinas;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_personas;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_legajos;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_titulos;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_cursos;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_idiomas;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_familiar;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_antecedente;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_historial;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_sumarios;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_documentos;
DROP PROCEDURE IF EXISTS sp_func_listar_historico_usuario;
DROP PROCEDURE IF EXISTS sp_func_legajo_completo;
DROP PROCEDURE IF EXISTS sp_func_buscar_persona;
DROP PROCEDURE IF EXISTS sp_func_buscar_legajo;
DROP PROCEDURE IF EXISTS sp_func_listar_legajos_por_persona;
DROP PROCEDURE IF EXISTS sp_func_reporte_personal_activo;

DELIMITER //

-- ======================================================================
-- 1. TABLA: personas
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_persona(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM personas WHERE id_persona = p_id_persona;
END//

CREATE PROCEDURE sp_func_listar_personas(
    IN p_id_usuario_func INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_personas();
END//

-- ======================================================================
-- 2. TABLA: legajos
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_legajo(
    IN p_id_usuario_func INT,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT l.*, p.apellido, p.nombre, p.dni,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.id_legajo = p_id_legajo;
END//

CREATE PROCEDURE sp_func_listar_legajos(
    IN p_id_usuario_func INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_legajos();
END//

CREATE PROCEDURE sp_func_listar_legajos_por_estado(
    IN p_id_usuario_func INT,
    IN p_estado ENUM('activo','de_baja','traslado','prestamo')
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_legajos_por_estado(p_estado);
END//

-- ======================================================================
-- 3. TABLA: usuario
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_usuario(
    IN p_id_usuario_func INT,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT id_usuario, usuario, tipo, id_legajo, primer_ingreso, activo, fecha_creacion, ultimo_login
    FROM usuario
    WHERE id_legajo = p_id_legajo;
END//

CREATE PROCEDURE sp_func_listar_usuarios(
    IN p_id_usuario_func INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT u.id_usuario, u.usuario, u.tipo, u.id_legajo, u.primer_ingreso, u.activo, u.fecha_creacion, u.ultimo_login,
        p.apellido, p.nombre, p.dni
    FROM usuario u
    INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    INNER JOIN personas p ON p.id_persona = l.id_persona
    ORDER BY p.apellido, p.nombre;
END//

-- ======================================================================
-- 4. TABLA: titulos
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_titulo(
    IN p_id_usuario_func INT,
    IN p_id_titulo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM titulos WHERE id_titulo = p_id_titulo;
END//

CREATE PROCEDURE sp_func_listar_titulos(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_titulos(p_id_persona);
END//

-- ======================================================================
-- 5. TABLA: cursos
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_curso(
    IN p_id_usuario_func INT,
    IN p_id_curso INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM cursos WHERE id_curso = p_id_curso;
END//

CREATE PROCEDURE sp_func_listar_cursos(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_cursos(p_id_persona);
END//

-- ======================================================================
-- 6. TABLA: idiomas
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_idioma(
    IN p_id_usuario_func INT,
    IN p_id_idioma INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM idiomas WHERE id_idioma = p_id_idioma;
END//

CREATE PROCEDURE sp_func_listar_idiomas(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_idiomas(p_id_persona);
END//

-- ======================================================================
-- 7. TABLA: familiar
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_familiar(
    IN p_id_usuario_func INT,
    IN p_id_familiar INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM familiar WHERE id_familiar = p_id_familiar;
END//

CREATE PROCEDURE sp_func_listar_familiares(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_familiares(p_id_persona);
END//

-- ======================================================================
-- 8. TABLA: antecedente_laboral
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_antecedente(
    IN p_id_usuario_func INT,
    IN p_id_antecedente INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM antecedente_laboral WHERE id_antecedente = p_id_antecedente;
END//

CREATE PROCEDURE sp_func_listar_antecedentes(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_antecedentes(p_id_persona);
END//

-- ======================================================================
-- 9. TABLA: historial_legajos
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_historial(
    IN p_id_usuario_func INT,
    IN p_id_historial INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM historial_legajos WHERE id_historial = p_id_historial;
END//

CREATE PROCEDURE sp_func_listar_historial(
    IN p_id_usuario_func INT,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historial(p_id_legajo);
END//

-- ======================================================================
-- 10. TABLA: sumarios
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_sumario(
    IN p_id_usuario_func INT,
    IN p_id_sumario INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM sumarios WHERE id_sumario = p_id_sumario;
END//

CREATE PROCEDURE sp_func_listar_sumarios(
    IN p_id_usuario_func INT,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_sumarios(p_id_legajo);
END//

-- ======================================================================
-- 11. TABLA: documentos
-- ======================================================================

CREATE PROCEDURE sp_func_obtener_documento(
    IN p_id_usuario_func INT,
    IN p_id_documento INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT * FROM documentos WHERE id_documento = p_id_documento;
END//

CREATE PROCEDURE sp_func_listar_documentos(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_documentos(p_id_persona);
END//

-- ======================================================================
-- 12. TABLA: categorias
-- ======================================================================

CREATE PROCEDURE sp_func_listar_categorias(
    IN p_id_usuario_func INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_categorias();
END//

-- ======================================================================
-- 13. TABLA: cargos
-- ======================================================================

CREATE PROCEDURE sp_func_listar_cargos(
    IN p_id_usuario_func INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_cargos();
END//

-- ======================================================================
-- 14. TABLA: oficinas
-- ======================================================================

CREATE PROCEDURE sp_func_listar_oficinas(
    IN p_id_usuario_func INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_oficinas();
END//

-- ======================================================================
-- 15. TABLA: historico_personas
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_personas(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_personas(p_id_persona);
END//

-- ======================================================================
-- 16. TABLA: historico_legajos
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_legajos(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_legajos(p_id_persona);
END//

-- ======================================================================
-- 17. TABLA: historico_titulos
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_titulos(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_titulos(p_id_persona);
END//

-- ======================================================================
-- 18. TABLA: historico_cursos
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_cursos(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_cursos(p_id_persona);
END//

-- ======================================================================
-- 19. TABLA: historico_idiomas
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_idiomas(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_idiomas(p_id_persona);
END//

-- ======================================================================
-- 20. TABLA: historico_familiar
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_familiar(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_familiar(p_id_persona);
END//

-- ======================================================================
-- 21. TABLA: historico_antecedente_laboral
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_antecedente(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_antecedente(p_id_persona);
END//

-- ======================================================================
-- 22. TABLA: historico_historial_legajos
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_historial(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_historial(p_id_persona);
END//

-- ======================================================================
-- 23. TABLA: historico_sumarios
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_sumarios(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_sumarios(p_id_persona);
END//

-- ======================================================================
-- 24. TABLA: historico_documentos
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_documentos(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_documentos(p_id_persona);
END//

-- ======================================================================
-- 25. TABLA: historico_usuario
-- ======================================================================

CREATE PROCEDURE sp_func_listar_historico_usuario(
    IN p_id_usuario_func INT,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_historico_usuario(p_id_legajo);
END//

-- ======================================================================
-- 26. CONSULTAS COMBINADAS
-- ======================================================================

CREATE PROCEDURE sp_func_legajo_completo(
    IN p_id_usuario_func INT,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_legajo_completo(p_id_legajo);
END//

CREATE PROCEDURE sp_func_buscar_persona(
    IN p_id_usuario_func INT,
    IN p_busqueda VARCHAR(100)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_buscar_persona(p_busqueda);
END//

-- ======================================================================
-- 27. CONSULTAS ADICIONALES
-- ======================================================================

CREATE PROCEDURE sp_func_buscar_legajo(
    IN p_id_usuario_func INT,
    IN p_busqueda VARCHAR(100),
    IN p_id_cargo INT,
    IN p_id_categoria INT,
    IN p_id_oficina INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_buscar_legajo(p_busqueda, p_id_cargo, p_id_categoria, p_id_oficina);
END//

CREATE PROCEDURE sp_func_listar_legajos_por_persona(
    IN p_id_usuario_func INT,
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado.';
    END IF;
    CALL sp_base_listar_legajos_por_persona(p_id_persona);
END//

CREATE PROCEDURE sp_func_reporte_personal_activo(
    IN p_id_usuario_func INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_func AND activo = 1;
    IF v_tipo != 'funcionario' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo funcionarios pueden usar este procedimiento.';
    END IF;
    SELECT l.id_legajo, l.tipo_contrato, l.fecha_ingreso,
        p.apellido, p.nombre, p.dni, p.cuil,
        p.telefono, p.email,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.estado = 'activo'
    ORDER BY p.apellido, p.nombre;
END//

DELIMITER ;
