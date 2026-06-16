USE torres_corregida1;

-- LISTADO COMPLETO DE TRIGGERS ADICIONALES PARA torres_corregida1
-- OBJETIVO: Complementar la auditoria, sincronizacion automatica y seguridad del sistema

-- 1. TABLA: usuario
-- TRIGGER #1:  trg_actualizar_ultimo_login          - Registra en historico cuando cambia ultimo_login
-- TRIGGER #2:  trg_primer_ingreso_usuario           - Actualiza primer_ingreso a 0 en el primer UPDATE activo
-- TRIGGER #3:  trg_log_cambio_pass                  - Registra en historico cuando cambia la contrasena
-- TRIGGER #4:  trg_bloqueo_admin_unico_update       - Bloquea desactivar el ultimo administrador activo
-- TRIGGER #5:  trg_bloqueo_admin_unico_delete       - Bloquea eliminar el ultimo administrador activo

-- 2. TABLA: titulos
-- TRIGGER #6:  trg_primer_ingreso_titulos           - Actualiza primer_ingreso a 0 en el primer UPDATE

-- 3. TABLA: cursos
-- TRIGGER #7:  trg_primer_ingreso_cursos            - Actualiza primer_ingreso a 0 en el primer UPDATE

-- 4. TABLA: idiomas
-- TRIGGER #8:  trg_primer_ingreso_idiomas           - Actualiza primer_ingreso a 0 en el primer UPDATE

-- 5. TABLA: familiar
-- TRIGGER #9:  trg_primer_ingreso_familiar          - Actualiza primer_ingreso a 0 en el primer UPDATE
-- TRIGGER #10: trg_sync_cantidad_hijos_insert       - Actualiza cantidad_hijos en personas al agregar un hijo
-- TRIGGER #11: trg_sync_cantidad_hijos_delete       - Actualiza cantidad_hijos en personas al eliminar un hijo
-- TRIGGER #12: trg_sync_estado_civil_conyuge        - Actualiza estado_civil a viudo/a cuando fallece el conyuge

-- 6. TABLA: antecedente_laboral
-- TRIGGER #13: trg_primer_ingreso_antecedente       - Actualiza primer_ingreso a 0 en el primer UPDATE

-- 7. TABLA: documentos
-- TRIGGER #14: trg_primer_ingreso_documentos        - Actualiza primer_ingreso a 0 en el primer UPDATE

-- 8. TABLA: historial_legajos
-- TRIGGER #15: trg_validar_historial_sumario        - Verifica que exista sumario real antes de registrar accion sumario
-- TRIGGER #16: trg_validar_estado_legajo_historial  - Verifica que el legajo este activo antes de registrar baja definitiva

-- ======================================================================
-- LIMPIEZA PREVIA DE DISPARADORES
-- ======================================================================
DROP TRIGGER IF EXISTS trg_actualizar_ultimo_login;
DROP TRIGGER IF EXISTS trg_primer_ingreso_usuario;
DROP TRIGGER IF EXISTS trg_log_cambio_pass;
DROP TRIGGER IF EXISTS trg_bloqueo_admin_unico_update;
DROP TRIGGER IF EXISTS trg_bloqueo_admin_unico_delete;
DROP TRIGGER IF EXISTS trg_primer_ingreso_titulos;
DROP TRIGGER IF EXISTS trg_primer_ingreso_cursos;
DROP TRIGGER IF EXISTS trg_primer_ingreso_idiomas;
DROP TRIGGER IF EXISTS trg_primer_ingreso_familiar;
DROP TRIGGER IF EXISTS trg_sync_cantidad_hijos_insert;
DROP TRIGGER IF EXISTS trg_sync_cantidad_hijos_delete;
DROP TRIGGER IF EXISTS trg_sync_estado_civil_conyuge;
DROP TRIGGER IF EXISTS trg_primer_ingreso_antecedente;
DROP TRIGGER IF EXISTS trg_primer_ingreso_documentos;
DROP TRIGGER IF EXISTS trg_validar_historial_sumario;
DROP TRIGGER IF EXISTS trg_validar_estado_legajo_historial;

DELIMITER //

-- ======================================================================
-- 1. TABLA: usuario
-- ======================================================================

CREATE TRIGGER trg_actualizar_ultimo_login
AFTER UPDATE ON usuario
FOR EACH ROW
BEGIN
    IF NEW.ultimo_login != OLD.ultimo_login OR (NEW.ultimo_login IS NOT NULL AND OLD.ultimo_login IS NULL) THEN
        INSERT INTO historico_usuario (
            id_usuario, usuario, pass, tipo, id_legajo, primer_ingreso, activo,
            fecha_creacion, ultimo_login, usuario_accion, tipo_cambio
        ) VALUES (
            NEW.id_usuario, NEW.usuario, NEW.pass, NEW.tipo, NEW.id_legajo, NEW.primer_ingreso, NEW.activo,
            DATE(NEW.fecha_creacion), NEW.ultimo_login, USER(), 'UPDATE'
        );
    END IF;
END//

CREATE TRIGGER trg_primer_ingreso_usuario
AFTER UPDATE ON usuario
FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 AND NEW.activo = 1 AND NEW.ultimo_login IS NOT NULL THEN
        UPDATE usuario SET primer_ingreso = 0 WHERE id_usuario = NEW.id_usuario;
    END IF;
END//

CREATE TRIGGER trg_log_cambio_pass
BEFORE UPDATE ON usuario
FOR EACH ROW
BEGIN
    IF NEW.pass != OLD.pass THEN
        INSERT INTO historico_usuario (
            id_usuario, usuario, pass, tipo, id_legajo, primer_ingreso, activo,
            fecha_creacion, ultimo_login, usuario_accion, tipo_cambio
        ) VALUES (
            OLD.id_usuario, OLD.usuario, NULL, OLD.tipo, OLD.id_legajo, OLD.primer_ingreso, OLD.activo,
            DATE(OLD.fecha_creacion), OLD.ultimo_login, USER(), 'UPDATE'
        );
    END IF;
END//

CREATE TRIGGER trg_bloqueo_admin_unico_update
BEFORE UPDATE ON usuario
FOR EACH ROW
BEGIN
    DECLARE v_cantidad INT;
    IF OLD.tipo = 'administrador' AND (NEW.activo = 0 OR NEW.tipo != 'administrador') THEN
        SELECT COUNT(*) INTO v_cantidad FROM usuario
        WHERE tipo = 'administrador' AND activo = 1 AND id_usuario != OLD.id_usuario;
        IF v_cantidad = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede desactivar o cambiar el tipo del ultimo administrador activo del sistema.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_bloqueo_admin_unico_delete
BEFORE DELETE ON usuario
FOR EACH ROW
BEGIN
    DECLARE v_cantidad INT;
    IF OLD.tipo = 'administrador' AND OLD.activo = 1 THEN
        SELECT COUNT(*) INTO v_cantidad FROM usuario
        WHERE tipo = 'administrador' AND activo = 1 AND id_usuario != OLD.id_usuario;
        IF v_cantidad = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede eliminar el ultimo administrador activo del sistema.';
        END IF;
    END IF;
END//

-- ======================================================================
-- 2. TABLA: titulos
-- ======================================================================

CREATE TRIGGER trg_primer_ingreso_titulos
AFTER UPDATE ON titulos
FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE titulos SET primer_ingreso = 0 WHERE id_titulo = NEW.id_titulo;
    END IF;
END//

-- ======================================================================
-- 3. TABLA: cursos
-- ======================================================================

CREATE TRIGGER trg_primer_ingreso_cursos
AFTER UPDATE ON cursos
FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE cursos SET primer_ingreso = 0 WHERE id_curso = NEW.id_curso;
    END IF;
END//

-- ======================================================================
-- 4. TABLA: idiomas
-- ======================================================================

CREATE TRIGGER trg_primer_ingreso_idiomas
AFTER UPDATE ON idiomas
FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE idiomas SET primer_ingreso = 0 WHERE id_idioma = NEW.id_idioma;
    END IF;
END//

-- ======================================================================
-- 5. TABLA: familiar
-- ======================================================================

CREATE TRIGGER trg_primer_ingreso_familiar
AFTER UPDATE ON familiar
FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE familiar SET primer_ingreso = 0 WHERE id_familiar = NEW.id_familiar;
    END IF;
END//

CREATE TRIGGER trg_sync_cantidad_hijos_insert
AFTER INSERT ON familiar
FOR EACH ROW
BEGIN
    IF NEW.relacion_empleado = 'hijos' AND NEW.activo = 1 THEN
        UPDATE personas SET cantidad_hijos = cantidad_hijos + 1 WHERE id_persona = NEW.id_persona;
    END IF;
END//

CREATE TRIGGER trg_sync_cantidad_hijos_delete
BEFORE DELETE ON familiar
FOR EACH ROW
BEGIN
    IF OLD.relacion_empleado = 'hijos' AND OLD.activo = 1 THEN
        UPDATE personas SET cantidad_hijos = GREATEST(cantidad_hijos - 1, 0) WHERE id_persona = OLD.id_persona;
    END IF;
END//

CREATE TRIGGER trg_sync_estado_civil_conyuge
AFTER UPDATE ON familiar
FOR EACH ROW
BEGIN
    IF NEW.relacion_empleado = 'conyuge' AND NEW.estado = 'fallecido' AND OLD.estado = 'vivo' THEN
        UPDATE personas SET estado_civil = 'viudo/a' WHERE id_persona = NEW.id_persona;
    END IF;
END//

-- ======================================================================
-- 6. TABLA: antecedente_laboral
-- ======================================================================

CREATE TRIGGER trg_primer_ingreso_antecedente
AFTER UPDATE ON antecedente_laboral
FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE antecedente_laboral SET primer_ingreso = 0 WHERE id_antecedente = NEW.id_antecedente;
    END IF;
END//

-- ======================================================================
-- 7. TABLA: documentos
-- ======================================================================

CREATE TRIGGER trg_primer_ingreso_documentos
AFTER UPDATE ON documentos
FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE documentos SET primer_ingreso = 0 WHERE id_documento = NEW.id_documento;
    END IF;
END//

-- ======================================================================
-- 8. TABLA: historial_legajos
-- ======================================================================

CREATE TRIGGER trg_validar_historial_sumario
BEFORE INSERT ON historial_legajos
FOR EACH ROW
BEGIN
    DECLARE v_cantidad INT;
    IF NEW.accion = 'sumario' THEN
        SELECT COUNT(*) INTO v_cantidad FROM sumarios
        WHERE id_legajo = NEW.id_legajo AND activo = 1;
        IF v_cantidad = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede registrar accion sumario sin que exista un sumario real para este legajo.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_validar_estado_legajo_historial
BEFORE INSERT ON historial_legajos
FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(20);
    IF NEW.accion IN ('jubilacion', 'renuncia', 'difunto', 'vencimiento_contrato', 'incapacidad') THEN
        SELECT estado INTO v_estado FROM legajos WHERE id_legajo = NEW.id_legajo;
        IF v_estado = 'de_baja' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede registrar esta accion porque el legajo ya esta dado de baja.';
        END IF;
    END IF;
END//

DELIMITER ;
