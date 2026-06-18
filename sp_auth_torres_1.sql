USE torres_corregida1;

-- LISTADO COMPLETO DE STORED PROCEDURES DE AUTENTICACION PARA torres_corregida1
-- OBJETIVO: Cambio de contrasena
-- NOTA: El login se realiza mediante sp_seg_login (sp_seguridad_torres_1.sql),
-- que incluye contador de intentos, bloqueo automatico y registro de log/eventos.
-- La recuperacion de contrasena se realiza mediante sp_seg_solicitar_recuperacion
-- y sp_seg_atender_recuperacion (recuperacion mediada por el administrador).

-- SP #1: sp_auth_cambiar_pass         - Cambia la contrasena validando la actual

-- ======================================================================
-- LIMPIEZA PREVIA
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_auth_cambiar_pass;

DELIMITER //

-- ======================================================================
-- SP #1: CAMBIAR CONTRASENA
-- ======================================================================

CREATE PROCEDURE sp_auth_cambiar_pass(
    IN p_id_usuario INT,
    IN p_pass_actual VARCHAR(255),
    IN p_pass_nueva VARCHAR(255),
    IN p_ip VARCHAR(45),
    OUT p_resultado VARCHAR(50)
)
BEGIN
    DECLARE v_pass_guardada VARCHAR(255);
    DECLARE v_activo TINYINT(1);
    DECLARE v_usuario VARCHAR(50);
    DECLARE v_tipo VARCHAR(20);

    SELECT pass, activo, usuario, tipo INTO v_pass_guardada, v_activo, v_usuario, v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario;

    IF v_pass_guardada IS NULL THEN
        SET p_resultado = 'USUARIO_NO_EXISTE';
    ELSEIF v_activo = 0 THEN
        SET p_resultado = 'USUARIO_INACTIVO';
    ELSEIF v_pass_guardada != SHA2(p_pass_actual, 256) THEN
        SET p_resultado = 'CONTRASENA_ACTUAL_INCORRECTA';

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
        VALUES (p_id_usuario, v_usuario, v_tipo, p_ip, 'CAMBIO_PASS', 'fallido', 'Contrasena actual incorrecta');
    ELSEIF SHA2(p_pass_nueva, 256) = v_pass_guardada THEN
        SET p_resultado = 'CONTRASENA_IGUAL_A_ACTUAL';
    ELSEIF CHAR_LENGTH(p_pass_nueva) < 6 THEN
        SET p_resultado = 'CONTRASENA_DEMASIADO_CORTA';
    ELSE
        UPDATE usuario SET pass = SHA2(p_pass_nueva, 256), primer_ingreso = 0
        WHERE id_usuario = p_id_usuario;
        SET p_resultado = 'OK';

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
        VALUES (p_id_usuario, v_usuario, v_tipo, p_ip, 'CAMBIO_PASS', 'exitoso', 'Contrasena actualizada por el usuario');

        INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, resultado)
        VALUES ('cambio_pass', p_id_usuario, v_usuario, v_tipo, p_ip, 'exitoso');
    END IF;
END//

DELIMITER ;
