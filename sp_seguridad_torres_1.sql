USE torres_corregida1;

-- STORED PROCEDURES DE SEGURIDAD PARA torres_corregida1
-- OBJETIVO: Control de intentos de login, bloqueo, log del sistema, recuperacion de contrasena y gestion de incidentes

-- SP #1:  sp_seg_login                      - Login con contador de intentos (maximo 5), bloqueo automatico y log
-- SP #2:  sp_seg_desbloquear_usuario        - El administrador desbloquea un usuario bloqueado
-- SP #3:  sp_seg_solicitar_recuperacion     - El usuario solicita recuperacion de contrasena al administrador
-- SP #4:  sp_seg_atender_recuperacion       - El administrador atiende la solicitud y genera nueva clave
-- SP #5:  sp_seg_listar_solicitudes         - Lista solicitudes de recuperacion pendientes
-- SP #6:  sp_seg_registrar_log              - Registra una accion en el log del sistema
-- SP #7:  sp_seg_listar_log                 - Consulta el log del sistema con filtros
-- SP #8:  sp_seg_registrar_evento           - Registra un evento del sistema
-- SP #9:  sp_seg_listar_eventos             - Consulta eventos con filtros
-- SP #10: sp_seg_registrar_incidente        - Registra un incidente
-- SP #11: sp_seg_listar_incidentes          - Lista incidentes con filtros
-- SP #12: sp_seg_atender_incidente          - El administrador atiende y resuelve un incidente
-- SP #13: sp_seg_listar_usuarios_bloqueados - Lista usuarios actualmente bloqueados

-- ======================================================================
-- LIMPIEZA PREVIA
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_seg_login;
DROP PROCEDURE IF EXISTS sp_seg_desbloquear_usuario;
DROP PROCEDURE IF EXISTS sp_seg_solicitar_recuperacion;
DROP PROCEDURE IF EXISTS sp_seg_atender_recuperacion;
DROP PROCEDURE IF EXISTS sp_seg_listar_solicitudes;
DROP PROCEDURE IF EXISTS sp_seg_registrar_log;
DROP PROCEDURE IF EXISTS sp_seg_listar_log;
DROP PROCEDURE IF EXISTS sp_seg_registrar_evento;
DROP PROCEDURE IF EXISTS sp_seg_listar_eventos;
DROP PROCEDURE IF EXISTS sp_seg_registrar_incidente;
DROP PROCEDURE IF EXISTS sp_seg_listar_incidentes;
DROP PROCEDURE IF EXISTS sp_seg_atender_incidente;
DROP PROCEDURE IF EXISTS sp_seg_listar_usuarios_bloqueados;

DELIMITER //

-- ======================================================================
-- SP #1: LOGIN CON CONTADOR DE INTENTOS
-- ======================================================================

CREATE PROCEDURE sp_seg_login(
    IN p_usuario VARCHAR(50),
    IN p_pass VARCHAR(255),
    IN p_ip VARCHAR(45),
    OUT p_id_usuario INT,
    OUT p_tipo VARCHAR(20),
    OUT p_id_legajo INT,
    OUT p_primer_ingreso TINYINT(1),
    OUT p_resultado VARCHAR(50)
)
BEGIN
    DECLARE v_id_usuario INT;
    DECLARE v_pass_guardada VARCHAR(255);
    DECLARE v_activo TINYINT(1);
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_id_legajo INT;
    DECLARE v_primer_ingreso TINYINT(1);
    DECLARE v_bloqueado TINYINT(1) DEFAULT 0;
    DECLARE v_intentos INT DEFAULT 0;
    DECLARE v_inicio DATETIME(3);
    DECLARE v_duracion INT;

    SET v_inicio = NOW(3);

    SELECT id_usuario, pass, activo, tipo, id_legajo, primer_ingreso
    INTO v_id_usuario, v_pass_guardada, v_activo, v_tipo, v_id_legajo, v_primer_ingreso
    FROM usuario WHERE usuario = p_usuario;

    SELECT COALESCE(bloqueado, 0), COALESCE(intentos_fallidos, 0)
    INTO v_bloqueado, v_intentos
    FROM intentos_login
    WHERE usuario = p_usuario
    ORDER BY fecha_hora DESC LIMIT 1;

    IF v_id_usuario IS NULL THEN
        SET p_resultado = 'USUARIO_NO_EXISTE';
        SET p_id_usuario = NULL;
        SET p_tipo = NULL;
        SET p_id_legajo = NULL;
        SET p_primer_ingreso = NULL;

        INSERT INTO log_sistema (id_usuario, usuario_nombre, ip, accion, resultado, detalle)
        VALUES (NULL, p_usuario, p_ip, 'LOGIN', 'fallido', 'Usuario no existe');

        INSERT INTO eventos (tipo_evento, usuario_nombre, ip, resultado, detalle)
        VALUES ('login', p_usuario, p_ip, 'fallido', 'Usuario no existe');

    ELSEIF v_bloqueado = 1 THEN
        SET p_resultado = 'USUARIO_BLOQUEADO';
        SET p_id_usuario = NULL;
        SET p_tipo = NULL;
        SET p_id_legajo = NULL;
        SET p_primer_ingreso = NULL;

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
        VALUES (v_id_usuario, p_usuario, v_tipo, p_ip, 'LOGIN', 'bloqueado', 'Usuario bloqueado por multiples intentos fallidos');

        INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, resultado, detalle)
        VALUES ('acceso_denegado', v_id_usuario, p_usuario, v_tipo, p_ip, 'bloqueado', 'Intento de acceso con usuario bloqueado');

    ELSEIF v_activo = 0 THEN
        SET p_resultado = 'USUARIO_INACTIVO';
        SET p_id_usuario = NULL;
        SET p_tipo = NULL;
        SET p_id_legajo = NULL;
        SET p_primer_ingreso = NULL;

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
        VALUES (v_id_usuario, p_usuario, v_tipo, p_ip, 'LOGIN', 'fallido', 'Usuario inactivo');

    ELSEIF v_pass_guardada != SHA2(p_pass, 256) THEN
        SET v_intentos = v_intentos + 1;

        IF v_intentos >= 5 THEN
            START TRANSACTION;

            INSERT INTO intentos_login (usuario, ip, resultado, intentos_fallidos, bloqueado, fecha_bloqueo)
            VALUES (p_usuario, p_ip, 'fallido', v_intentos, 1, NOW());

            UPDATE usuario SET activo = 0 WHERE id_usuario = v_id_usuario;

            INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
            VALUES (v_id_usuario, p_usuario, v_tipo, p_ip, 'LOGIN', 'bloqueado', CONCAT('Usuario bloqueado tras ', v_intentos, ' intentos fallidos'));

            INSERT INTO incidentes (tipo_incidente, nivel_severidad, id_usuario, usuario_nombre, ip, descripcion)
            VALUES ('multiples_intentos_fallidos', 'alto', v_id_usuario, p_usuario, p_ip,
                CONCAT('Usuario bloqueado automaticamente tras ', v_intentos, ' intentos fallidos de login'));

            INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, resultado, detalle)
            VALUES ('bloqueo_usuario', v_id_usuario, p_usuario, v_tipo, p_ip, 'exitoso',
                CONCAT('Usuario bloqueado automaticamente tras ', v_intentos, ' intentos fallidos'));

            COMMIT;

            SET p_resultado = 'USUARIO_BLOQUEADO';
        ELSE
            INSERT INTO intentos_login (usuario, ip, resultado, intentos_fallidos, bloqueado)
            VALUES (p_usuario, p_ip, 'fallido', v_intentos, 0);

            SET p_resultado = CONCAT('CONTRASENA_INCORRECTA_INTENTO_', v_intentos, '_DE_5');

            INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
            VALUES (v_id_usuario, p_usuario, v_tipo, p_ip, 'LOGIN', 'fallido',
                CONCAT('Contrasena incorrecta. Intento ', v_intentos, ' de 5'));
        END IF;

        SET p_id_usuario = NULL;
        SET p_tipo = NULL;
        SET p_id_legajo = NULL;
        SET p_primer_ingreso = NULL;

    ELSE
        INSERT INTO intentos_login (usuario, ip, resultado, intentos_fallidos, bloqueado)
        VALUES (p_usuario, p_ip, 'exitoso', 0, 0);

        UPDATE usuario SET ultimo_login = NOW() WHERE id_usuario = v_id_usuario;

        SET v_duracion = TIMESTAMPDIFF(MICROSECOND, v_inicio, NOW(3)) DIV 1000;

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, duracion_ms)
        VALUES (v_id_usuario, p_usuario, v_tipo, p_ip, 'LOGIN', 'exitoso', v_duracion);

        INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, resultado)
        VALUES ('login', v_id_usuario, p_usuario, v_tipo, p_ip, 'exitoso');

        SET p_id_usuario = v_id_usuario;
        SET p_tipo = v_tipo;
        SET p_id_legajo = v_id_legajo;
        SET p_primer_ingreso = v_primer_ingreso;
        SET p_resultado = 'OK';
    END IF;
END//

-- ======================================================================
-- SP #2: DESBLOQUEAR USUARIO
-- ======================================================================

CREATE PROCEDURE sp_seg_desbloquear_usuario(
    IN p_id_admin INT,
    IN p_id_usuario_bloquear INT,
    IN p_ip VARCHAR(45)
)
BEGIN
    DECLARE v_tipo_admin VARCHAR(20);
    DECLARE v_usuario_nombre VARCHAR(50);

    SELECT tipo INTO v_tipo_admin FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo_admin != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede desbloquear usuarios.';
    END IF;

    SELECT usuario INTO v_usuario_nombre FROM usuario WHERE id_usuario = p_id_usuario_bloquear;

    START TRANSACTION;

    UPDATE usuario SET activo = 1 WHERE id_usuario = p_id_usuario_bloquear;

    UPDATE intentos_login SET bloqueado = 0, fecha_desbloqueo = NOW()
    WHERE usuario = v_usuario_nombre AND bloqueado = 1;

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, tabla_afectada, id_registro, resultado, detalle)
    VALUES (p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin), 'administrador',
        p_ip, 'DESBLOQUEO_USUARIO', 'usuario', p_id_usuario_bloquear, 'exitoso',
        CONCAT('Usuario ', v_usuario_nombre, ' desbloqueado'));

    INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, id_registro, resultado, detalle)
    VALUES ('desbloqueo_usuario', p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
        'administrador', p_ip, p_id_usuario_bloquear, 'exitoso',
        CONCAT('Usuario ', v_usuario_nombre, ' desbloqueado por administrador'));

    COMMIT;
END//

-- ======================================================================
-- SP #3: SOLICITAR RECUPERACION DE CONTRASENA
-- ======================================================================

CREATE PROCEDURE sp_seg_solicitar_recuperacion(
    IN p_usuario VARCHAR(50),
    IN p_ip VARCHAR(45),
    OUT p_resultado VARCHAR(100)
)
BEGIN
    DECLARE v_id_usuario INT;
    DECLARE v_activo TINYINT(1);
    DECLARE v_pendiente INT;

    SELECT id_usuario, activo INTO v_id_usuario, v_activo
    FROM usuario WHERE usuario = p_usuario;

    IF v_id_usuario IS NULL THEN
        SET p_resultado = 'USUARIO_NO_EXISTE';
    ELSE
        SELECT COUNT(*) INTO v_pendiente FROM solicitudes_recuperacion
        WHERE id_usuario = v_id_usuario AND estado = 'pendiente';

        IF v_pendiente > 0 THEN
            SET p_resultado = 'SOLICITUD_YA_PENDIENTE';
        ELSE
            INSERT INTO solicitudes_recuperacion (id_usuario, usuario, detalle)
            VALUES (v_id_usuario, p_usuario, 'Solicitud de recuperacion de contrasena');

            INSERT INTO log_sistema (id_usuario, usuario_nombre, ip, accion, resultado, detalle)
            VALUES (v_id_usuario, p_usuario, p_ip, 'SOLICITUD_RECUPERACION_PASS', 'exitoso',
                'Solicitud enviada al administrador');

            INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, ip, resultado, detalle)
            VALUES ('recuperacion_pass', v_id_usuario, p_usuario, p_ip, 'exitoso',
                'Solicitud de recuperacion de contrasena registrada');

            SET p_resultado = 'OK_SOLICITUD_REGISTRADA';
        END IF;
    END IF;
END//

-- ======================================================================
-- SP #4: ADMINISTRADOR ATIENDE SOLICITUD DE RECUPERACION
-- ======================================================================

CREATE PROCEDURE sp_seg_atender_recuperacion(
    IN p_id_admin INT,
    IN p_id_solicitud INT,
    IN p_accion ENUM('atendida', 'rechazada'),
    IN p_ip VARCHAR(45),
    OUT p_pass_nueva VARCHAR(50),
    OUT p_resultado VARCHAR(50)
)
BEGIN
    DECLARE v_tipo_admin VARCHAR(20);
    DECLARE v_id_usuario INT;
    DECLARE v_usuario VARCHAR(50);
    DECLARE v_id_legajo INT;
    DECLARE v_apellido VARCHAR(50);
    DECLARE v_num1 INT;
    DECLARE v_num2 INT;
    DECLARE v_error_msg VARCHAR(200);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_resultado = CONCAT('ERROR: ', v_error_msg);
        SET p_pass_nueva = NULL;
    END;

    SELECT tipo INTO v_tipo_admin FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo_admin != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede atender solicitudes.';
    END IF;

    SELECT id_usuario, usuario INTO v_id_usuario, v_usuario
    FROM solicitudes_recuperacion WHERE id_solicitud = p_id_solicitud AND estado = 'pendiente';

    IF v_id_usuario IS NULL THEN
        SET p_resultado = 'SOLICITUD_NO_ENCONTRADA';
        SET p_pass_nueva = NULL;
    ELSEIF p_accion = 'rechazada' THEN
        START TRANSACTION;

        UPDATE solicitudes_recuperacion SET
            estado = 'rechazada',
            id_admin_atiende = p_id_admin,
            fecha_atencion = NOW()
        WHERE id_solicitud = p_id_solicitud;

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
        VALUES (p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
            'administrador', p_ip, 'RECHAZO_RECUPERACION_PASS', 'exitoso',
            CONCAT('Solicitud de ', v_usuario, ' rechazada'));

        COMMIT;

        SET p_pass_nueva = NULL;
        SET p_resultado = 'SOLICITUD_RECHAZADA';
    ELSE
        SELECT id_legajo INTO v_id_legajo FROM usuario WHERE id_usuario = v_id_usuario;

        SELECT p.apellido INTO v_apellido
        FROM personas p
        INNER JOIN legajos l ON l.id_persona = p.id_persona
        WHERE l.id_legajo = v_id_legajo;

        SET v_num1 = FLOOR(RAND() * 10);
        SET v_num2 = FLOOR(RAND() * 10);
        SET p_pass_nueva = CONCAT(UPPER(LEFT(v_apellido, 1)), LOWER(SUBSTRING(v_apellido, 2, 4)), v_num1, v_num2);

        START TRANSACTION;

        UPDATE usuario SET
            pass = SHA2(p_pass_nueva, 256),
            primer_ingreso = 1,
            activo = 1
        WHERE id_usuario = v_id_usuario;

        UPDATE intentos_login SET bloqueado = 0, fecha_desbloqueo = NOW()
        WHERE usuario = v_usuario AND bloqueado = 1;

        UPDATE solicitudes_recuperacion SET
            estado = 'atendida',
            id_admin_atiende = p_id_admin,
            fecha_atencion = NOW()
        WHERE id_solicitud = p_id_solicitud;

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
        VALUES (p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
            'administrador', p_ip, 'ATENCION_RECUPERACION_PASS', 'exitoso',
            CONCAT('Contrasena regenerada para usuario ', v_usuario));

        INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, resultado, detalle)
        VALUES ('recuperacion_pass', p_id_admin,
            (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
            'administrador', p_ip, 'exitoso',
            CONCAT('Contrasena regenerada para usuario ', v_usuario));

        COMMIT;

        SET p_resultado = 'OK';
    END IF;
END//

-- ======================================================================
-- SP #5: LISTAR SOLICITUDES DE RECUPERACION
-- ======================================================================

CREATE PROCEDURE sp_seg_listar_solicitudes(
    IN p_id_admin INT,
    IN p_estado ENUM('pendiente', 'atendida', 'rechazada')
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede ver las solicitudes.';
    END IF;
    SELECT s.*, p.apellido, p.nombre, p.telefono, p.email
    FROM solicitudes_recuperacion s
    INNER JOIN usuario u ON u.id_usuario = s.id_usuario
    INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    INNER JOIN personas p ON p.id_persona = l.id_persona
    WHERE (p_estado IS NULL OR s.estado = p_estado)
    ORDER BY s.fecha_solicitud DESC;
END//

-- ======================================================================
-- SP #6: REGISTRAR LOG
-- ======================================================================

CREATE PROCEDURE sp_seg_registrar_log(
    IN p_id_usuario INT,
    IN p_usuario_nombre VARCHAR(50),
    IN p_tipo_usuario VARCHAR(20),
    IN p_ip VARCHAR(45),
    IN p_accion VARCHAR(100),
    IN p_tabla_afectada VARCHAR(50),
    IN p_id_registro INT,
    IN p_detalle TEXT,
    IN p_resultado ENUM('exitoso', 'fallido', 'bloqueado'),
    IN p_duracion_ms INT
)
BEGIN
    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, tabla_afectada, id_registro, detalle, resultado, duracion_ms)
    VALUES (p_id_usuario, p_usuario_nombre, p_tipo_usuario, p_ip, p_accion, p_tabla_afectada, p_id_registro, p_detalle, p_resultado, p_duracion_ms);
END//

-- ======================================================================
-- SP #7: LISTAR LOG
-- ======================================================================

CREATE PROCEDURE sp_seg_listar_log(
    IN p_id_admin INT,
    IN p_id_usuario INT,
    IN p_accion VARCHAR(100),
    IN p_resultado ENUM('exitoso', 'fallido', 'bloqueado'),
    IN p_fecha_desde DATETIME,
    IN p_fecha_hasta DATETIME
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede consultar el log.';
    END IF;
    SELECT * FROM log_sistema
    WHERE (p_id_usuario IS NULL OR id_usuario = p_id_usuario)
        AND (p_accion IS NULL OR accion LIKE CONCAT('%', p_accion, '%'))
        AND (p_resultado IS NULL OR resultado = p_resultado)
        AND (p_fecha_desde IS NULL OR fecha_hora >= p_fecha_desde)
        AND (p_fecha_hasta IS NULL OR fecha_hora <= p_fecha_hasta)
    ORDER BY fecha_hora DESC;
END//

-- ======================================================================
-- SP #8: REGISTRAR EVENTO
-- ======================================================================

CREATE PROCEDURE sp_seg_registrar_evento(
    IN p_tipo_evento ENUM('login','logout','cambio_pass','creacion_usuario','baja_legajo',
        'reactivacion_legajo','modificacion_datos','backup','recuperacion_pass',
        'bloqueo_usuario','desbloqueo_usuario','acceso_denegado','otro'),
    IN p_id_usuario INT,
    IN p_usuario_nombre VARCHAR(50),
    IN p_tipo_usuario VARCHAR(20),
    IN p_ip VARCHAR(45),
    IN p_tabla_afectada VARCHAR(50),
    IN p_id_registro INT,
    IN p_detalle TEXT,
    IN p_resultado ENUM('exitoso', 'fallido', 'bloqueado')
)
BEGIN
    INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, tabla_afectada, id_registro, detalle, resultado)
    VALUES (p_tipo_evento, p_id_usuario, p_usuario_nombre, p_tipo_usuario, p_ip, p_tabla_afectada, p_id_registro, p_detalle, p_resultado);
END//

-- ======================================================================
-- SP #9: LISTAR EVENTOS
-- ======================================================================

CREATE PROCEDURE sp_seg_listar_eventos(
    IN p_id_admin INT,
    IN p_tipo_evento VARCHAR(50),
    IN p_id_usuario INT,
    IN p_resultado ENUM('exitoso', 'fallido', 'bloqueado'),
    IN p_fecha_desde DATETIME,
    IN p_fecha_hasta DATETIME
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede consultar eventos.';
    END IF;
    SELECT * FROM eventos
    WHERE (p_tipo_evento IS NULL OR tipo_evento = p_tipo_evento)
        AND (p_id_usuario IS NULL OR id_usuario = p_id_usuario)
        AND (p_resultado IS NULL OR resultado = p_resultado)
        AND (p_fecha_desde IS NULL OR fecha_hora >= p_fecha_desde)
        AND (p_fecha_hasta IS NULL OR fecha_hora <= p_fecha_hasta)
    ORDER BY fecha_hora DESC;
END//

-- ======================================================================
-- SP #10: REGISTRAR INCIDENTE
-- ======================================================================

CREATE PROCEDURE sp_seg_registrar_incidente(
    IN p_tipo_incidente ENUM('acceso_no_autorizado','multiples_intentos_fallidos','usuario_bloqueado',
        'cambio_pass_forzado','backup_fallido','error_sistema','dato_invalido',
        'intento_modificacion_admin','otro'),
    IN p_nivel_severidad ENUM('bajo', 'medio', 'alto', 'critico'),
    IN p_id_usuario INT,
    IN p_usuario_nombre VARCHAR(50),
    IN p_ip VARCHAR(45),
    IN p_descripcion TEXT
)
BEGIN
    INSERT INTO incidentes (tipo_incidente, nivel_severidad, id_usuario, usuario_nombre, ip, descripcion)
    VALUES (p_tipo_incidente, p_nivel_severidad, p_id_usuario, p_usuario_nombre, p_ip, p_descripcion);
END//

-- ======================================================================
-- SP #11: LISTAR INCIDENTES
-- ======================================================================

CREATE PROCEDURE sp_seg_listar_incidentes(
    IN p_id_admin INT,
    IN p_tipo ENUM('acceso_no_autorizado','multiples_intentos_fallidos','usuario_bloqueado',
        'cambio_pass_forzado','backup_fallido','error_sistema','dato_invalido',
        'intento_modificacion_admin','otro'),
    IN p_nivel ENUM('bajo', 'medio', 'alto', 'critico'),
    IN p_estado ENUM('abierto', 'en_revision', 'resuelto', 'cerrado'),
    IN p_fecha_desde DATETIME,
    IN p_fecha_hasta DATETIME
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede consultar incidentes.';
    END IF;
    SELECT * FROM incidentes
    WHERE (p_tipo IS NULL OR tipo_incidente = p_tipo)
        AND (p_nivel IS NULL OR nivel_severidad = p_nivel)
        AND (p_estado IS NULL OR estado = p_estado)
        AND (p_fecha_desde IS NULL OR fecha_hora >= p_fecha_desde)
        AND (p_fecha_hasta IS NULL OR fecha_hora <= p_fecha_hasta)
    ORDER BY fecha_hora DESC;
END//

-- ======================================================================
-- SP #12: ATENDER INCIDENTE
-- ======================================================================

CREATE PROCEDURE sp_seg_atender_incidente(
    IN p_id_admin INT,
    IN p_id_incidente INT,
    IN p_estado ENUM('en_revision', 'resuelto', 'cerrado'),
    IN p_resolucion TEXT,
    IN p_ip VARCHAR(45)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede gestionar incidentes.';
    END IF;
    UPDATE incidentes SET
        estado = p_estado,
        id_admin_atiende = p_id_admin,
        fecha_resolucion = CASE WHEN p_estado IN ('resuelto', 'cerrado') THEN NOW() ELSE NULL END,
        resolucion = p_resolucion
    WHERE id_incidente = p_id_incidente;

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, tabla_afectada, id_registro, resultado, detalle)
    VALUES (p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
        'administrador', p_ip, 'GESTION_INCIDENTE', 'incidentes', p_id_incidente, 'exitoso',
        CONCAT('Incidente ', p_id_incidente, ' actualizado a estado: ', p_estado));
END//

-- ======================================================================
-- SP #13: LISTAR USUARIOS BLOQUEADOS
-- ======================================================================

CREATE PROCEDURE sp_seg_listar_usuarios_bloqueados(
    IN p_id_admin INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede ver usuarios bloqueados.';
    END IF;
    SELECT u.id_usuario, u.usuario, u.tipo, u.activo, u.ultimo_login,
        p.apellido, p.nombre, p.dni, p.telefono, p.email,
        i.intentos_fallidos, i.fecha_bloqueo
    FROM usuario u
    INNER JOIN legajos l ON l.id_legajo = u.id_legajo
    INNER JOIN personas p ON p.id_persona = l.id_persona
    INNER JOIN (
        SELECT usuario, MAX(intentos_fallidos) AS intentos_fallidos, MAX(fecha_bloqueo) AS fecha_bloqueo
        FROM intentos_login
        WHERE bloqueado = 1
        GROUP BY usuario
    ) i ON i.usuario = u.usuario
    WHERE u.activo = 0
    ORDER BY i.fecha_bloqueo DESC;
END//

DELIMITER ;
