USE torres_corregida1;

-- STORED PROCEDURES DE BACKUP PARA torres_corregida1
-- OBJETIVO: Generar instrucciones de backup diario y semanal con rotacion automatica

-- SP #1: sp_backup_generar_diario   - Genera el script de backup diario y registra en backup_log
-- SP #2: sp_backup_generar_semanal  - Genera el script de backup semanal y registra en backup_log
-- SP #3: sp_backup_listar           - Lista el historial de backups
-- SP #4: sp_backup_limpiar_diarios  - Elimina registros de backups diarios anteriores al ultimo
-- SP #5: sp_backup_limpiar_semanales - Elimina registros de backups semanales anteriores al ultimo

-- ======================================================================
-- LIMPIEZA PREVIA
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_backup_generar_diario;
DROP PROCEDURE IF EXISTS sp_backup_generar_semanal;
DROP PROCEDURE IF EXISTS sp_backup_listar;
DROP PROCEDURE IF EXISTS sp_backup_limpiar_diarios;
DROP PROCEDURE IF EXISTS sp_backup_limpiar_semanales;

DELIMITER //

-- ======================================================================
-- SP #1: BACKUP DIARIO
-- ======================================================================

CREATE PROCEDURE sp_backup_generar_diario(
    IN p_id_admin INT,
    IN p_ruta_base VARCHAR(500),
    IN p_ip VARCHAR(45),
    OUT p_nombre_archivo VARCHAR(255),
    OUT p_comando VARCHAR(1000),
    OUT p_resultado VARCHAR(50)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_fecha VARCHAR(20);
    DECLARE v_nombre VARCHAR(255);
    DECLARE v_ruta VARCHAR(500);
    DECLARE v_id_anterior INT;

    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede generar backups.';
    END IF;

    SET v_fecha = DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s');
    SET v_nombre = CONCAT('backup_diario_torres_', v_fecha, '.sql');
    SET v_ruta = CONCAT(p_ruta_base, '/', v_nombre);

    SELECT id_backup INTO v_id_anterior FROM backup_log
    WHERE tipo = 'diario' AND resultado = 'exitoso'
    ORDER BY fecha_hora DESC LIMIT 1;

    INSERT INTO backup_log (tipo, nombre_archivo, ruta_archivo, resultado, detalle)
    VALUES ('diario', v_nombre, v_ruta, 'exitoso',
        CONCAT('Backup diario generado por usuario id=', p_id_admin));

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
    VALUES (p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
        'administrador', p_ip, 'BACKUP_DIARIO', 'exitoso',
        CONCAT('Archivo: ', v_nombre));

    INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, resultado, detalle)
    VALUES ('backup', p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
        'administrador', p_ip, 'exitoso', CONCAT('Backup diario: ', v_nombre));

    IF v_id_anterior IS NOT NULL THEN
        CALL sp_backup_limpiar_diarios(v_id_anterior);
    END IF;

    SET p_nombre_archivo = v_nombre;
    SET p_comando = CONCAT(
        'mysqldump -u [usuario] -p[password] torres_corregida1 > ', v_ruta
    );
    SET p_resultado = 'OK';
END//

-- ======================================================================
-- SP #2: BACKUP SEMANAL
-- ======================================================================

CREATE PROCEDURE sp_backup_generar_semanal(
    IN p_id_admin INT,
    IN p_ruta_base VARCHAR(500),
    IN p_ip VARCHAR(45),
    OUT p_nombre_archivo VARCHAR(255),
    OUT p_comando VARCHAR(1000),
    OUT p_resultado VARCHAR(50)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    DECLARE v_fecha VARCHAR(20);
    DECLARE v_nombre VARCHAR(255);
    DECLARE v_ruta VARCHAR(500);
    DECLARE v_id_anterior INT;

    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede generar backups.';
    END IF;

    SET v_fecha = DATE_FORMAT(NOW(), '%Y_%U');
    SET v_nombre = CONCAT('backup_semanal_torres_semana_', v_fecha, '.sql');
    SET v_ruta = CONCAT(p_ruta_base, '/', v_nombre);

    SELECT id_backup INTO v_id_anterior FROM backup_log
    WHERE tipo = 'semanal' AND resultado = 'exitoso'
    ORDER BY fecha_hora DESC LIMIT 1;

    INSERT INTO backup_log (tipo, nombre_archivo, ruta_archivo, resultado, detalle)
    VALUES ('semanal', v_nombre, v_ruta, 'exitoso',
        CONCAT('Backup semanal generado por usuario id=', p_id_admin));

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip, accion, resultado, detalle)
    VALUES (p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
        'administrador', p_ip, 'BACKUP_SEMANAL', 'exitoso',
        CONCAT('Archivo: ', v_nombre));

    INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario, ip, resultado, detalle)
    VALUES ('backup', p_id_admin, (SELECT usuario FROM usuario WHERE id_usuario = p_id_admin),
        'administrador', p_ip, 'exitoso', CONCAT('Backup semanal: ', v_nombre));

    IF v_id_anterior IS NOT NULL THEN
        CALL sp_backup_limpiar_semanales(v_id_anterior);
    END IF;

    SET p_nombre_archivo = v_nombre;
    SET p_comando = CONCAT(
        'mysqldump -u [usuario] -p[password] torres_corregida1 > ', v_ruta
    );
    SET p_resultado = 'OK';
END//

-- ======================================================================
-- SP #3: LISTAR BACKUPS
-- ======================================================================

CREATE PROCEDURE sp_backup_listar(
    IN p_id_admin INT,
    IN p_tipo ENUM('diario', 'semanal')
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede ver los backups.';
    END IF;
    SELECT * FROM backup_log
    WHERE (p_tipo IS NULL OR tipo = p_tipo)
    ORDER BY fecha_hora DESC;
END//

-- ======================================================================
-- SP #4: LIMPIAR BACKUPS DIARIOS ANTERIORES
-- ======================================================================

CREATE PROCEDURE sp_backup_limpiar_diarios(
    IN p_id_ultimo INT
)
BEGIN
    DELETE FROM backup_log
    WHERE tipo = 'diario' AND id_backup != p_id_ultimo;
END//

-- ======================================================================
-- SP #5: LIMPIAR BACKUPS SEMANALES ANTERIORES
-- ======================================================================

CREATE PROCEDURE sp_backup_limpiar_semanales(
    IN p_id_ultimo INT
)
BEGIN
    DELETE FROM backup_log
    WHERE tipo = 'semanal' AND id_backup != p_id_ultimo;
END//

DELIMITER ;
