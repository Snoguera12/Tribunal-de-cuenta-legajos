USE torres_corregida1;

-- LISTADO COMPLETO DE STORED PROCEDURES PARA EL ADMINISTRADOR
-- OBJETIVO: Permitir al administrador consultar todas las tablas principales e historicas

-- 1. TABLA: personas
-- SP #1:  sp_admin_obtener_persona            - Obtiene una persona por id
-- SP #2:  sp_admin_listar_personas            - Lista todas las personas

-- 2. TABLA: legajos
-- SP #3:  sp_admin_obtener_legajo             - Obtiene un legajo por id con datos relacionados
-- SP #4:  sp_admin_listar_legajos             - Lista todos los legajos con datos relacionados
-- SP #5:  sp_admin_listar_legajos_por_estado  - Lista legajos filtrados por estado

-- 3. TABLA: usuario
-- SP #6:  sp_admin_obtener_usuario            - Obtiene un usuario por id_legajo
-- SP #7:  sp_admin_listar_usuarios            - Lista todos los usuarios

-- 4. TABLA: titulos
-- SP #8:  sp_admin_obtener_titulo             - Obtiene un titulo por id
-- SP #9:  sp_admin_listar_titulos             - Lista todos los titulos de un legajo

-- 5. TABLA: cursos
-- SP #10: sp_admin_obtener_curso              - Obtiene un curso por id
-- SP #11: sp_admin_listar_cursos              - Lista todos los cursos de un legajo

-- 6. TABLA: idiomas
-- SP #12: sp_admin_obtener_idioma             - Obtiene un idioma por id
-- SP #13: sp_admin_listar_idiomas             - Lista todos los idiomas de un legajo

-- 7. TABLA: familiar
-- SP #14: sp_admin_obtener_familiar           - Obtiene un familiar por id
-- SP #15: sp_admin_listar_familiares          - Lista todos los familiares de un legajo

-- 8. TABLA: antecedente_laboral
-- SP #16: sp_admin_obtener_antecedente        - Obtiene un antecedente por id
-- SP #17: sp_admin_listar_antecedentes        - Lista todos los antecedentes de un legajo

-- 9. TABLA: historial_legajos
-- SP #18: sp_admin_obtener_historial          - Obtiene un historial por id
-- SP #19: sp_admin_listar_historial           - Lista todo el historial de un legajo

-- 10. TABLA: sumarios
-- SP #20: sp_admin_obtener_sumario            - Obtiene un sumario por id
-- SP #21: sp_admin_listar_sumarios            - Lista todos los sumarios de un legajo

-- 11. TABLA: documentos
-- SP #22: sp_admin_obtener_documento          - Obtiene un documento por id
-- SP #23: sp_admin_listar_documentos          - Lista todos los documentos de un legajo

-- 12. TABLA: categorias
-- SP #24: sp_admin_listar_categorias          - Lista todas las categorias

-- 13. TABLA: cargos
-- SP #25: sp_admin_listar_cargos              - Lista todos los cargos

-- 14. TABLA: oficinas
-- SP #26: sp_admin_listar_oficinas            - Lista todas las oficinas

-- 15. TABLA: historico_personas
-- SP #27: sp_admin_listar_historico_personas  - Lista el historico de una persona

-- 16. TABLA: historico_legajos
-- SP #28: sp_admin_listar_historico_legajos   - Lista el historico de un legajo

-- 17. TABLA: historico_titulos
-- SP #29: sp_admin_listar_historico_titulos   - Lista el historico de titulos de un legajo

-- 18. TABLA: historico_cursos
-- SP #30: sp_admin_listar_historico_cursos    - Lista el historico de cursos de un legajo

-- 19. TABLA: historico_idiomas
-- SP #31: sp_admin_listar_historico_idiomas   - Lista el historico de idiomas de un legajo

-- 20. TABLA: historico_familiar
-- SP #32: sp_admin_listar_historico_familiar  - Lista el historico de familiares de un legajo

-- 21. TABLA: historico_antecedente_laboral
-- SP #33: sp_admin_listar_historico_antecedente - Lista el historico de antecedentes de un legajo

-- 22. TABLA: historico_historial_legajos
-- SP #34: sp_admin_listar_historico_historial - Lista el historico del historial de un legajo

-- 23. TABLA: historico_sumarios
-- SP #35: sp_admin_listar_historico_sumarios  - Lista el historico de sumarios de un legajo

-- 24. TABLA: historico_documentos
-- SP #36: sp_admin_listar_historico_documentos - Lista el historico de documentos de un legajo

-- 25. TABLA: historico_usuario
-- SP #37: sp_admin_listar_historico_usuario   - Lista el historico de un usuario

-- 26. CONSULTAS COMBINADAS
-- SP #38: sp_admin_legajo_completo                  - Obtiene todos los datos de un legajo y su persona en una sola consulta
-- SP #39: sp_admin_buscar_persona                   - Busca personas por apellido, nombre o dni

-- 27. CONSULTAS ADICIONALES
-- SP #40: sp_admin_listar_legajos_por_persona       - Lista todos los legajos historicos de una persona
-- SP #41: sp_admin_listar_auditoria_completa        - Lista todos los cambios historicos de una persona en todas las tablas
-- SP #42: sp_admin_buscar_por_cargo_categoria_oficina - Filtra legajos por cargo, categoria u oficina
-- SP #43: sp_admin_listar_usuarios_por_tipo         - Lista usuarios filtrados por tipo
-- SP #44: sp_admin_reporte_bajas                    - Lista todos los legajos de baja con fecha y detalle
-- SP #45: sp_admin_insertar_usuario                  - Crea un usuario de cualquier tipo para un legajo existente
-- SP #46: sp_admin_modificar_usuario                 - Modifica el tipo y estado activo de un usuario

-- ======================================================================
-- LIMPIEZA PREVIA DE STORED PROCEDURES
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_admin_obtener_persona;
DROP PROCEDURE IF EXISTS sp_admin_listar_personas;
DROP PROCEDURE IF EXISTS sp_admin_obtener_legajo;
DROP PROCEDURE IF EXISTS sp_admin_listar_legajos;
DROP PROCEDURE IF EXISTS sp_admin_listar_legajos_por_estado;
DROP PROCEDURE IF EXISTS sp_admin_obtener_usuario;
DROP PROCEDURE IF EXISTS sp_admin_listar_usuarios;
DROP PROCEDURE IF EXISTS sp_admin_insertar_usuario;
DROP PROCEDURE IF EXISTS sp_admin_modificar_usuario;
DROP PROCEDURE IF EXISTS sp_admin_obtener_titulo;
DROP PROCEDURE IF EXISTS sp_admin_listar_titulos;
DROP PROCEDURE IF EXISTS sp_admin_obtener_curso;
DROP PROCEDURE IF EXISTS sp_admin_listar_cursos;
DROP PROCEDURE IF EXISTS sp_admin_obtener_idioma;
DROP PROCEDURE IF EXISTS sp_admin_listar_idiomas;
DROP PROCEDURE IF EXISTS sp_admin_obtener_familiar;
DROP PROCEDURE IF EXISTS sp_admin_listar_familiares;
DROP PROCEDURE IF EXISTS sp_admin_obtener_antecedente;
DROP PROCEDURE IF EXISTS sp_admin_listar_antecedentes;
DROP PROCEDURE IF EXISTS sp_admin_obtener_historial;
DROP PROCEDURE IF EXISTS sp_admin_listar_historial;
DROP PROCEDURE IF EXISTS sp_admin_obtener_sumario;
DROP PROCEDURE IF EXISTS sp_admin_listar_sumarios;
DROP PROCEDURE IF EXISTS sp_admin_obtener_documento;
DROP PROCEDURE IF EXISTS sp_admin_listar_documentos;
DROP PROCEDURE IF EXISTS sp_admin_listar_categorias;
DROP PROCEDURE IF EXISTS sp_admin_listar_cargos;
DROP PROCEDURE IF EXISTS sp_admin_listar_oficinas;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_personas;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_legajos;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_titulos;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_cursos;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_idiomas;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_familiar;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_antecedente;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_historial;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_sumarios;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_documentos;
DROP PROCEDURE IF EXISTS sp_admin_listar_historico_usuario;
DROP PROCEDURE IF EXISTS sp_admin_legajo_completo;
DROP PROCEDURE IF EXISTS sp_admin_buscar_persona;
DROP PROCEDURE IF EXISTS sp_admin_listar_legajos_por_persona;
DROP PROCEDURE IF EXISTS sp_admin_listar_auditoria_completa;
DROP PROCEDURE IF EXISTS sp_admin_buscar_por_cargo_categoria_oficina;
DROP PROCEDURE IF EXISTS sp_admin_listar_usuarios_por_tipo;
DROP PROCEDURE IF EXISTS sp_admin_reporte_bajas;

DELIMITER //

-- ======================================================================
-- 1. TABLA: personas
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_persona(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM personas WHERE id_persona = p_id_persona;
END//

CREATE PROCEDURE sp_admin_listar_personas()
BEGIN
    CALL sp_base_listar_personas();
END//

-- ======================================================================
-- 2. TABLA: legajos
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_legajo(
    IN p_id_legajo INT
)
BEGIN
    SELECT l.*, p.apellido, p.nombre, p.dni,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.id_legajo = p_id_legajo;
END//

CREATE PROCEDURE sp_admin_listar_legajos()
BEGIN
    CALL sp_base_listar_legajos();
END//

CREATE PROCEDURE sp_admin_listar_legajos_por_estado(
    IN p_estado ENUM('activo','de_baja','traslado','prestamo')
)
BEGIN
    CALL sp_base_listar_legajos_por_estado(p_estado);
END//

-- ======================================================================
-- 3. TABLA: usuario
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_usuario(
    IN p_id_legajo INT
)
BEGIN
    SELECT id_usuario, usuario, tipo, id_legajo, primer_ingreso, activo, fecha_creacion, ultimo_login
    FROM usuario
    WHERE id_legajo = p_id_legajo;
END//

CREATE PROCEDURE sp_admin_listar_usuarios()
BEGIN
    SELECT u.id_usuario, u.usuario, u.tipo, u.id_legajo, u.primer_ingreso, u.activo, u.fecha_creacion, u.ultimo_login,
        p.apellido, p.nombre, p.dni
    FROM usuario u
    INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    INNER JOIN personas p ON p.id_persona = l.id_persona
    ORDER BY p.apellido, p.nombre;
END//

CREATE PROCEDURE sp_admin_insertar_usuario(
    IN p_id_usuario_admin INT,
    IN p_id_legajo INT,
    IN p_tipo ENUM('funcionario','rrhh','empleado','administrador'),
    OUT p_usuario_generado VARCHAR(50),
    OUT p_pass_generada VARCHAR(50),
    OUT p_email_persona VARCHAR(50),
    OUT p_telefono_persona VARCHAR(20)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_apellido VARCHAR(50);
    DECLARE v_num1 INT;
    DECLARE v_num2 INT;
    DECLARE v_usuario_base VARCHAR(50);
    DECLARE v_usuario_final VARCHAR(50);
    DECLARE v_pass VARCHAR(50);
    DECLARE v_existe INT;
    DECLARE v_contador INT DEFAULT 0;
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede crear usuarios de cualquier tipo.';
    END IF;
    SELECT COUNT(*) INTO v_existe FROM usuario WHERE id_legajo = p_id_legajo;
    IF v_existe > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Este legajo ya tiene un usuario asignado.';
    END IF;
    SELECT p.apellido, p.email, p.telefono
    INTO v_apellido, p_email_persona, p_telefono_persona
    FROM personas p
    INNER JOIN legajos l ON l.id_persona = p.id_persona
    WHERE l.id_legajo = p_id_legajo;
    SET v_usuario_base = LOWER(CONCAT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        v_apellido,
        'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),
        'Á','a'),'É','e'),'Í','i'),'Ó','o'),'Ú','u'),
        'ñ','n'),'Ñ','n'),
        ' ','_'), '.', ''), '_', CONVERT(p_id_legajo, CHAR)));
    SET v_usuario_final = v_usuario_base;
    SELECT COUNT(*) INTO v_existe FROM usuario WHERE usuario = v_usuario_final;
    WHILE v_existe > 0 DO
        SET v_contador = v_contador + 1;
        SET v_usuario_final = CONCAT(v_usuario_base, v_contador);
        SELECT COUNT(*) INTO v_existe FROM usuario WHERE usuario = v_usuario_final;
    END WHILE;
    SET v_num1 = FLOOR(RAND() * 10);
    SET v_num2 = FLOOR(RAND() * 10);
    SET v_pass = CONCAT(UPPER(LEFT(v_apellido, 1)), LOWER(SUBSTRING(v_apellido, 2, 4)), v_num1, v_num2);
    INSERT INTO usuario (usuario, pass, tipo, id_legajo, activo)
    VALUES (v_usuario_final, SHA2(v_pass, 256), p_tipo, p_id_legajo, 1);
    SET p_usuario_generado = v_usuario_final;
    SET p_pass_generada = v_pass;
END//

CREATE PROCEDURE sp_admin_modificar_usuario(
    IN p_id_usuario_admin INT,
    IN p_id_usuario INT,
    IN p_tipo ENUM('funcionario','rrhh','empleado','administrador'),
    IN p_activo TINYINT(1)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede modificar usuarios.';
    END IF;
    UPDATE usuario SET
        tipo = p_tipo,
        activo = p_activo
    WHERE id_usuario = p_id_usuario;
END//

-- ======================================================================
-- 4. TABLA: titulos
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_titulo(
    IN p_id_titulo INT
)
BEGIN
    SELECT * FROM titulos WHERE id_titulo = p_id_titulo;
END//

CREATE PROCEDURE sp_admin_listar_titulos(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_titulos(p_id_persona);
END//

-- ======================================================================
-- 5. TABLA: cursos
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_curso(
    IN p_id_curso INT
)
BEGIN
    SELECT * FROM cursos WHERE id_curso = p_id_curso;
END//

CREATE PROCEDURE sp_admin_listar_cursos(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_cursos(p_id_persona);
END//

-- ======================================================================
-- 6. TABLA: idiomas
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_idioma(
    IN p_id_idioma INT
)
BEGIN
    SELECT * FROM idiomas WHERE id_idioma = p_id_idioma;
END//

CREATE PROCEDURE sp_admin_listar_idiomas(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_idiomas(p_id_persona);
END//

-- ======================================================================
-- 7. TABLA: familiar
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_familiar(
    IN p_id_familiar INT
)
BEGIN
    SELECT * FROM familiar WHERE id_familiar = p_id_familiar;
END//

CREATE PROCEDURE sp_admin_listar_familiares(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_familiares(p_id_persona);
END//

-- ======================================================================
-- 8. TABLA: antecedente_laboral
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_antecedente(
    IN p_id_antecedente INT
)
BEGIN
    SELECT * FROM antecedente_laboral WHERE id_antecedente = p_id_antecedente;
END//

CREATE PROCEDURE sp_admin_listar_antecedentes(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_antecedentes(p_id_persona);
END//

-- ======================================================================
-- 9. TABLA: historial_legajos
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_historial(
    IN p_id_historial INT
)
BEGIN
    SELECT * FROM historial_legajos WHERE id_historial = p_id_historial;
END//

CREATE PROCEDURE sp_admin_listar_historial(
    IN p_id_legajo INT
)
BEGIN
    CALL sp_base_listar_historial(p_id_legajo);
END//

-- ======================================================================
-- 10. TABLA: sumarios
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_sumario(
    IN p_id_sumario INT
)
BEGIN
    SELECT * FROM sumarios WHERE id_sumario = p_id_sumario;
END//

CREATE PROCEDURE sp_admin_listar_sumarios(
    IN p_id_legajo INT
)
BEGIN
    CALL sp_base_listar_sumarios(p_id_legajo);
END//

-- ======================================================================
-- 11. TABLA: documentos
-- ======================================================================

CREATE PROCEDURE sp_admin_obtener_documento(
    IN p_id_documento INT
)
BEGIN
    SELECT * FROM documentos WHERE id_documento = p_id_documento;
END//

CREATE PROCEDURE sp_admin_listar_documentos(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_documentos(p_id_persona);
END//

-- ======================================================================
-- 12. TABLA: categorias
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_categorias()
BEGIN
    CALL sp_base_listar_categorias();
END//

-- ======================================================================
-- 13. TABLA: cargos
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_cargos()
BEGIN
    CALL sp_base_listar_cargos();
END//

-- ======================================================================
-- 14. TABLA: oficinas
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_oficinas()
BEGIN
    CALL sp_base_listar_oficinas();
END//

-- ======================================================================
-- 15. TABLA: historico_personas
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_personas(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_personas(p_id_persona);
END//

-- ======================================================================
-- 16. TABLA: historico_legajos
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_legajos(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_legajos(p_id_persona);
END//

-- ======================================================================
-- 17. TABLA: historico_titulos
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_titulos(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_titulos(p_id_persona);
END//

-- ======================================================================
-- 18. TABLA: historico_cursos
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_cursos(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_cursos(p_id_persona);
END//

-- ======================================================================
-- 19. TABLA: historico_idiomas
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_idiomas(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_idiomas(p_id_persona);
END//

-- ======================================================================
-- 20. TABLA: historico_familiar
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_familiar(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_familiar(p_id_persona);
END//

-- ======================================================================
-- 21. TABLA: historico_antecedente_laboral
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_antecedente(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_antecedente(p_id_persona);
END//

-- ======================================================================
-- 22. TABLA: historico_historial_legajos
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_historial(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_historial(p_id_persona);
END//

-- ======================================================================
-- 23. TABLA: historico_sumarios
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_sumarios(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_sumarios(p_id_persona);
END//

-- ======================================================================
-- 24. TABLA: historico_documentos
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_documentos(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_historico_documentos(p_id_persona);
END//

-- ======================================================================
-- 25. TABLA: historico_usuario
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_historico_usuario(
    IN p_id_legajo INT
)
BEGIN
    CALL sp_base_listar_historico_usuario(p_id_legajo);
END//

-- ======================================================================
-- 26. CONSULTAS COMBINADAS
-- ======================================================================

CREATE PROCEDURE sp_admin_legajo_completo(
    IN p_id_legajo INT
)
BEGIN
    CALL sp_base_legajo_completo(p_id_legajo);
END//

CREATE PROCEDURE sp_admin_buscar_persona(
    IN p_busqueda VARCHAR(100)
)
BEGIN
    CALL sp_base_buscar_persona(p_busqueda);
END//

-- ======================================================================
-- 27. CONSULTAS ADICIONALES
-- ======================================================================

CREATE PROCEDURE sp_admin_listar_legajos_por_persona(
    IN p_id_persona INT
)
BEGIN
    CALL sp_base_listar_legajos_por_persona(p_id_persona);
END//

CREATE PROCEDURE sp_admin_listar_auditoria_completa(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM historico_personas WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
    SELECT hl.* FROM historico_legajos hl
    INNER JOIN legajos l ON l.id_legajo = hl.id_legajo
    WHERE l.id_persona = p_id_persona ORDER BY hl.fecha_accion DESC;
    SELECT * FROM historico_titulos WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
    SELECT * FROM historico_cursos WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
    SELECT * FROM historico_idiomas WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
    SELECT * FROM historico_familiar WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
    SELECT * FROM historico_antecedente_laboral WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
    SELECT hh.* FROM historico_historial_legajos hh
    INNER JOIN legajos l ON l.id_legajo = hh.id_legajo
    WHERE l.id_persona = p_id_persona ORDER BY hh.fecha_accion DESC;
    SELECT hs.* FROM historico_sumarios hs
    INNER JOIN legajos l ON l.id_legajo = hs.id_legajo
    WHERE l.id_persona = p_id_persona ORDER BY hs.fecha_accion DESC;
    SELECT * FROM historico_documentos WHERE id_persona = p_id_persona ORDER BY fecha_accion DESC;
    SELECT hu.* FROM historico_usuario hu
    INNER JOIN legajos l ON l.id_legajo = hu.id_legajo
    WHERE l.id_persona = p_id_persona ORDER BY hu.fecha_accion DESC;
END//

CREATE PROCEDURE sp_admin_buscar_por_cargo_categoria_oficina(
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
    WHERE (p_id_cargo IS NULL OR l.id_cargo = p_id_cargo)
        AND (p_id_categoria IS NULL OR l.id_categoria = p_id_categoria)
        AND (p_id_oficina IS NULL OR l.id_oficina = p_id_oficina)
    ORDER BY p.apellido, p.nombre;
END//

CREATE PROCEDURE sp_admin_listar_usuarios_por_tipo(
    IN p_tipo ENUM('funcionario','rrhh','empleado','administrador')
)
BEGIN
    SELECT u.id_usuario, u.usuario, u.tipo, u.id_legajo, u.activo, u.fecha_creacion, u.ultimo_login,
        p.apellido, p.nombre, p.dni
    FROM usuario u
    INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    INNER JOIN personas p ON p.id_persona = l.id_persona
    WHERE u.tipo = p_tipo
    ORDER BY p.apellido, p.nombre;
END//

CREATE PROCEDURE sp_admin_reporte_bajas()
BEGIN
    SELECT l.id_legajo, l.estado, l.fecha_ingreso,
        p.apellido, p.nombre, p.dni,
        c.nombre_cargo, cat.nombre_categoria, o.nombre_oficina,
        s.detalle AS motivo_baja, s.fecha_registro AS fecha_baja
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
    WHERE l.estado = 'de_baja'
    ORDER BY s.fecha_registro DESC;
END//

DELIMITER ;
