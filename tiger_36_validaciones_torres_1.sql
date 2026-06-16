USE torres_corregida1;

-- LISTADO COMPLETO DE TRIGGERS PARA torres_corregida1
-- OBJETIVO: Validar integridad de datos antes de INSERT y UPDATE

-- 1. TABLA: personas
-- TRIGGER #1:  trg_val_edad_minima_personas_insert          - Valida que la persona tenga al menos 18 años antes de un INSERT
-- TRIGGER #2:  trg_val_edad_minima_personas_update          - Valida que la persona tenga al menos 18 años antes de un UPDATE
-- TRIGGER #3:  trg_val_fecha_nac_personas_insert            - Valida que la fecha de nacimiento no sea futura antes de un INSERT
-- TRIGGER #4:  trg_val_fecha_nac_personas_update            - Valida que la fecha de nacimiento no sea futura antes de un UPDATE
-- TRIGGER #5:  trg_val_dni_personas_insert                  - Valida que el DNI tenga exactamente 8 dígitos numéricos antes de un INSERT
-- TRIGGER #6:  trg_val_dni_personas_update                  - Valida que el DNI tenga exactamente 8 dígitos numéricos antes de un UPDATE
-- TRIGGER #7:  trg_val_cuil_personas_insert                 - Valida que el CUIL tenga formato válido y contenga el DNI antes de un INSERT
-- TRIGGER #8:  trg_val_cuil_personas_update                 - Valida que el CUIL tenga formato válido y contenga el DNI antes de un UPDATE
-- TRIGGER #9:  trg_val_email_personas_insert                - Valida que el email tenga formato válido antes de un INSERT
-- TRIGGER #10: trg_val_email_personas_update                - Valida que el email tenga formato válido antes de un UPDATE
-- TRIGGER #11: trg_val_telefono_personas_insert             - Valida que teléfono y teléfono de emergencia tengan mínimo 7 dígitos antes de un INSERT
-- TRIGGER #12: trg_val_telefono_personas_update             - Valida que teléfono y teléfono de emergencia tengan mínimo 7 dígitos antes de un UPDATE

-- 2. TABLA: legajos
-- TRIGGER #13: trg_val_fecha_ingreso_legajos_insert         - Valida que la fecha de ingreso no sea futura antes de un INSERT
-- TRIGGER #14: trg_val_fecha_ingreso_legajos_update         - Valida que la fecha de ingreso no sea futura antes de un UPDATE
-- TRIGGER #15: trg_val_fechas_legajos_insert                - Valida que fecha_ingreso_administracion no sea anterior a fecha_ingreso antes de un INSERT
-- TRIGGER #16: trg_val_fechas_legajos_update                - Valida que fecha_ingreso_administracion no sea anterior a fecha_ingreso antes de un UPDATE
-- TRIGGER #17: trg_val_edad_ingreso_legajos_insert          - Valida que la persona tenga al menos 18 años al momento de fecha_ingreso antes de un INSERT
-- TRIGGER #18: trg_val_edad_ingreso_legajos_update          - Valida que la persona tenga al menos 18 años al momento de fecha_ingreso antes de un UPDATE
-- TRIGGER #19: trg_val_legajo_activo_unico_insert           - Valida que la persona no tenga otro legajo activo antes de un INSERT
-- TRIGGER #20: trg_val_legajo_activo_unico_update           - Valida que la persona no tenga otro legajo activo antes de un UPDATE
-- TRIGGER #21: trg_val_id_persona_legajos_update            - Valida que id_persona no cambie una vez creado el legajo

-- 3. TABLA: usuario
-- TRIGGER #22: trg_val_legajo_activo_usuario_insert         - Valida que el legajo no esté de_baja antes de crear un usuario
-- TRIGGER #23: trg_val_reactivacion_usuario_update          - Valida que no se reactive un usuario si el legajo sigue de_baja

-- 4. TABLA: titulos
-- TRIGGER #24: trg_val_fechas_titulos_insert                - Valida que fecha_fin no sea anterior a fecha_inicio antes de un INSERT
-- TRIGGER #25: trg_val_fechas_titulos_update                - Valida que fecha_fin no sea anterior a fecha_inicio antes de un UPDATE
-- TRIGGER #26: trg_val_legajo_activo_titulos_insert         - Valida que el legajo no esté de_baja antes de un INSERT
-- TRIGGER #27: trg_val_dup_titulo_insert                    - Valida que no exista el mismo título en la misma institución y fechas antes de un INSERT

-- 5. TABLA: cursos
-- TRIGGER #28: trg_val_fechas_cursos_insert                 - Valida que fecha_inicio no sea futura antes de un INSERT
-- TRIGGER #29: trg_val_fechas_cursos_update                 - Valida que fecha_inicio no sea futura antes de un UPDATE
-- TRIGGER #30: trg_val_legajo_activo_cursos_insert          - Valida que el legajo no esté de_baja antes de un INSERT
-- TRIGGER #31: trg_val_horas_cursos_insert                  - Valida que las horas sean mayor a 0 si se cargan antes de un INSERT
-- TRIGGER #32: trg_val_horas_cursos_update                  - Valida que las horas sean mayor a 0 si se cargan antes de un UPDATE

-- 6. TABLA: idiomas
-- TRIGGER #33: trg_val_dup_idioma_insert                    - Valida que no exista el mismo idioma para el mismo legajo antes de un INSERT
-- TRIGGER #34: trg_val_legajo_activo_idiomas_insert         - Valida que el legajo no esté de_baja antes de un INSERT

-- 7. TABLA: familiar
-- TRIGGER #35: trg_val_dni_familiar_insert                  - Valida que el DNI del familiar sea distinto al DNI del empleado antes de un INSERT
-- TRIGGER #36: trg_val_dni_familiar_update                  - Valida que el DNI del familiar sea distinto al DNI del empleado antes de un UPDATE
-- TRIGGER #37: trg_val_dni_digitos_familiar_insert          - Valida que el DNI del familiar tenga exactamente 8 dígitos antes de un INSERT
-- TRIGGER #38: trg_val_dni_digitos_familiar_update          - Valida que el DNI del familiar tenga exactamente 8 dígitos antes de un UPDATE
-- TRIGGER #39: trg_val_fecha_nac_familiar_insert            - Valida que la fecha de nacimiento del familiar no sea futura antes de un INSERT
-- TRIGGER #40: trg_val_fecha_nac_familiar_update            - Valida que la fecha de nacimiento del familiar no sea futura antes de un UPDATE
-- TRIGGER #41: trg_val_conyuge_unico_insert                 - Valida que no exista otro cónyuge activo para el mismo legajo antes de un INSERT
-- TRIGGER #42: trg_val_conyuge_unico_update                 - Valida que no exista otro cónyuge activo para el mismo legajo antes de un UPDATE
-- TRIGGER #43: trg_val_edad_familiar_insert                 - Valida que la edad del familiar sea coherente según su relación antes de un INSERT
-- TRIGGER #44: trg_val_edad_familiar_update                 - Valida que la edad del familiar sea coherente según su relación antes de un UPDATE
-- TRIGGER #45: trg_val_legajo_activo_familiar_insert        - Valida que el legajo no esté de_baja antes de un INSERT

-- 8. TABLA: antecedente_laboral
-- TRIGGER #46: trg_val_fechas_antecedente_insert            - Valida que fecha_fin no sea anterior a fecha_inicio antes de un INSERT
-- TRIGGER #47: trg_val_fechas_antecedente_update            - Valida que fecha_fin no sea anterior a fecha_inicio antes de un UPDATE
-- TRIGGER #48: trg_val_legajo_activo_antecedente_insert     - Valida que el legajo no esté de_baja antes de un INSERT

-- 9. TABLA: documentos
-- TRIGGER #49: trg_val_legajo_activo_documentos_insert      - Valida que el legajo no esté de_baja antes de un INSERT
-- TRIGGER #50: trg_val_hash_dup_documentos_insert           - Valida que no exista el mismo hash de archivo para el mismo legajo antes de un INSERT
-- TRIGGER #51: trg_val_foto_perfil_unica_insert             - Valida que no exista otra FOTO_PERFIL activa para el mismo legajo antes de un INSERT
-- TRIGGER #52: trg_val_foto_perfil_unica_update             - Valida que no exista otra FOTO_PERFIL activa para el mismo legajo antes de un UPDATE
-- TRIGGER #53: trg_val_mime_extension_insert                - Valida que la extensión sea coherente con el mime_type antes de un INSERT
-- TRIGGER #54: trg_val_mime_extension_update                - Valida que la extensión sea coherente con el mime_type antes de un UPDATE

-- ======================================================================
-- LIMPIEZA PREVIA DE DISPARADORES
-- ======================================================================
DROP TRIGGER IF EXISTS trg_val_edad_minima_personas_insert;
DROP TRIGGER IF EXISTS trg_val_edad_minima_personas_update;
DROP TRIGGER IF EXISTS trg_val_fecha_nac_personas_insert;
DROP TRIGGER IF EXISTS trg_val_fecha_nac_personas_update;
DROP TRIGGER IF EXISTS trg_val_dni_personas_insert;
DROP TRIGGER IF EXISTS trg_val_dni_personas_update;
DROP TRIGGER IF EXISTS trg_val_cuil_personas_insert;
DROP TRIGGER IF EXISTS trg_val_cuil_personas_update;
DROP TRIGGER IF EXISTS trg_val_email_personas_insert;
DROP TRIGGER IF EXISTS trg_val_email_personas_update;
DROP TRIGGER IF EXISTS trg_val_telefono_personas_insert;
DROP TRIGGER IF EXISTS trg_val_telefono_personas_update;
DROP TRIGGER IF EXISTS trg_val_fecha_ingreso_legajos_insert;
DROP TRIGGER IF EXISTS trg_val_fecha_ingreso_legajos_update;
DROP TRIGGER IF EXISTS trg_val_fechas_legajos_insert;
DROP TRIGGER IF EXISTS trg_val_fechas_legajos_update;
DROP TRIGGER IF EXISTS trg_val_edad_ingreso_legajos_insert;
DROP TRIGGER IF EXISTS trg_val_edad_ingreso_legajos_update;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_unico_insert;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_unico_update;
DROP TRIGGER IF EXISTS trg_val_id_persona_legajos_update;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_usuario_insert;
DROP TRIGGER IF EXISTS trg_val_reactivacion_usuario_update;
DROP TRIGGER IF EXISTS trg_val_fechas_titulos_insert;
DROP TRIGGER IF EXISTS trg_val_fechas_titulos_update;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_titulos_insert;
DROP TRIGGER IF EXISTS trg_val_dup_titulo_insert;
DROP TRIGGER IF EXISTS trg_val_fechas_cursos_insert;
DROP TRIGGER IF EXISTS trg_val_fechas_cursos_update;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_cursos_insert;
DROP TRIGGER IF EXISTS trg_val_horas_cursos_insert;
DROP TRIGGER IF EXISTS trg_val_horas_cursos_update;
DROP TRIGGER IF EXISTS trg_val_dup_idioma_insert;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_idiomas_insert;
DROP TRIGGER IF EXISTS trg_val_dni_familiar_insert;
DROP TRIGGER IF EXISTS trg_val_dni_familiar_update;
DROP TRIGGER IF EXISTS trg_val_dni_digitos_familiar_insert;
DROP TRIGGER IF EXISTS trg_val_dni_digitos_familiar_update;
DROP TRIGGER IF EXISTS trg_val_fecha_nac_familiar_insert;
DROP TRIGGER IF EXISTS trg_val_fecha_nac_familiar_update;
DROP TRIGGER IF EXISTS trg_val_conyuge_unico_insert;
DROP TRIGGER IF EXISTS trg_val_conyuge_unico_update;
DROP TRIGGER IF EXISTS trg_val_edad_familiar_insert;
DROP TRIGGER IF EXISTS trg_val_edad_familiar_update;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_familiar_insert;
DROP TRIGGER IF EXISTS trg_val_fechas_antecedente_insert;
DROP TRIGGER IF EXISTS trg_val_fechas_antecedente_update;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_antecedente_insert;
DROP TRIGGER IF EXISTS trg_val_legajo_activo_documentos_insert;
DROP TRIGGER IF EXISTS trg_val_hash_dup_documentos_insert;
DROP TRIGGER IF EXISTS trg_val_foto_perfil_unica_insert;
DROP TRIGGER IF EXISTS trg_val_foto_perfil_unica_update;
DROP TRIGGER IF EXISTS trg_val_mime_extension_insert;
DROP TRIGGER IF EXISTS trg_val_mime_extension_update;

DELIMITER //

-- ======================================================================
-- 1. TABLA: personas
-- ======================================================================

CREATE TRIGGER trg_val_edad_minima_personas_insert
BEFORE INSERT ON personas
FOR EACH ROW
BEGIN
    IF TIMESTAMPDIFF(YEAR, NEW.fecha_nacimiento, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La persona debe tener al menos 18 años.';
    END IF;
END//

CREATE TRIGGER trg_val_edad_minima_personas_update
BEFORE UPDATE ON personas
FOR EACH ROW
BEGIN
    IF TIMESTAMPDIFF(YEAR, NEW.fecha_nacimiento, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La persona debe tener al menos 18 años.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_nac_personas_insert
BEFORE INSERT ON personas
FOR EACH ROW
BEGIN
    IF NEW.fecha_nacimiento > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de nacimiento no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_nac_personas_update
BEFORE UPDATE ON personas
FOR EACH ROW
BEGIN
    IF NEW.fecha_nacimiento > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de nacimiento no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_personas_insert
BEFORE INSERT ON personas
FOR EACH ROW
BEGIN
    IF NEW.dni REGEXP '[^0-9]' OR CHAR_LENGTH(NEW.dni) != 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El DNI debe contener exactamente 8 dígitos numéricos.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_personas_update
BEFORE UPDATE ON personas
FOR EACH ROW
BEGIN
    IF NEW.dni REGEXP '[^0-9]' OR CHAR_LENGTH(NEW.dni) != 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El DNI debe contener exactamente 8 dígitos numéricos.';
    END IF;
END//

CREATE TRIGGER trg_val_cuil_personas_insert
BEFORE INSERT ON personas
FOR EACH ROW
BEGIN
    IF CHAR_LENGTH(NEW.cuil) != 13
        OR NEW.cuil NOT REGEXP '^[0-9]{2}-[0-9]{8}-[0-9]{1}$'
        OR LOCATE(NEW.dni, NEW.cuil) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El CUIL debe tener formato XX-XXXXXXXX-X y contener el DNI de la persona.';
    END IF;
END//

CREATE TRIGGER trg_val_cuil_personas_update
BEFORE UPDATE ON personas
FOR EACH ROW
BEGIN
    IF CHAR_LENGTH(NEW.cuil) != 13
        OR NEW.cuil NOT REGEXP '^[0-9]{2}-[0-9]{8}-[0-9]{1}$'
        OR LOCATE(NEW.dni, NEW.cuil) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El CUIL debe tener formato XX-XXXXXXXX-X y contener el DNI de la persona.';
    END IF;
END//

CREATE TRIGGER trg_val_email_personas_insert
BEFORE INSERT ON personas
FOR EACH ROW
BEGIN
    IF NEW.email IS NOT NULL AND NEW.email != '' THEN
        IF NEW.email NOT REGEXP '^[^@]+@[^@]+\.[^@]+$' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El email no tiene un formato válido.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_email_personas_update
BEFORE UPDATE ON personas
FOR EACH ROW
BEGIN
    IF NEW.email IS NOT NULL AND NEW.email != '' THEN
        IF NEW.email NOT REGEXP '^[^@]+@[^@]+\.[^@]+$' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El email no tiene un formato válido.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_telefono_personas_insert
BEFORE INSERT ON personas
FOR EACH ROW
BEGIN
    DECLARE tel_digits INT;
    DECLARE tel_em_digits INT;
    SET tel_digits = CHAR_LENGTH(NEW.telefono) - CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(NEW.telefono,'0',''),'1',''),'2',''),'3',''),'4',''),'5',''),'6',''),'7',''),'8',''),'9',''));
    SET tel_em_digits = CHAR_LENGTH(NEW.telefono_emergencia) - CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(NEW.telefono_emergencia,'0',''),'1',''),'2',''),'3',''),'4',''),'5',''),'6',''),'7',''),'8',''),'9',''));
    IF tel_digits < 7 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El teléfono debe tener al menos 7 dígitos numéricos.';
    END IF;
    IF tel_em_digits < 7 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El teléfono de emergencia debe tener al menos 7 dígitos numéricos.';
    END IF;
END//

CREATE TRIGGER trg_val_telefono_personas_update
BEFORE UPDATE ON personas
FOR EACH ROW
BEGIN
    DECLARE tel_digits INT;
    DECLARE tel_em_digits INT;
    SET tel_digits = CHAR_LENGTH(NEW.telefono) - CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(NEW.telefono,'0',''),'1',''),'2',''),'3',''),'4',''),'5',''),'6',''),'7',''),'8',''),'9',''));
    SET tel_em_digits = CHAR_LENGTH(NEW.telefono_emergencia) - CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(NEW.telefono_emergencia,'0',''),'1',''),'2',''),'3',''),'4',''),'5',''),'6',''),'7',''),'8',''),'9',''));
    IF tel_digits < 7 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El teléfono debe tener al menos 7 dígitos numéricos.';
    END IF;
    IF tel_em_digits < 7 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El teléfono de emergencia debe tener al menos 7 dígitos numéricos.';
    END IF;
END//

-- ======================================================================
-- 2. TABLA: legajos
-- ======================================================================

CREATE TRIGGER trg_val_fecha_ingreso_legajos_insert
BEFORE INSERT ON legajos
FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso IS NOT NULL AND NEW.fecha_ingreso > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de ingreso no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_ingreso_legajos_update
BEFORE UPDATE ON legajos
FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso IS NOT NULL AND NEW.fecha_ingreso > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de ingreso no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fechas_legajos_insert
BEFORE INSERT ON legajos
FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso_administracion IS NOT NULL AND NEW.fecha_ingreso IS NOT NULL THEN
        IF NEW.fecha_ingreso_administracion < NEW.fecha_ingreso THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La fecha de ingreso a la administración no puede ser anterior a la fecha de ingreso.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_fechas_legajos_update
BEFORE UPDATE ON legajos
FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso_administracion IS NOT NULL AND NEW.fecha_ingreso IS NOT NULL THEN
        IF NEW.fecha_ingreso_administracion < NEW.fecha_ingreso THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La fecha de ingreso a la administración no puede ser anterior a la fecha de ingreso.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_edad_ingreso_legajos_insert
BEFORE INSERT ON legajos
FOR EACH ROW
BEGIN
    DECLARE fecha_nac DATE;
    SELECT fecha_nacimiento INTO fecha_nac FROM personas WHERE id_persona = NEW.id_persona;
    IF NEW.fecha_ingreso IS NOT NULL THEN
        IF TIMESTAMPDIFF(YEAR, fecha_nac, NEW.fecha_ingreso) < 18 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La persona debe tener al menos 18 años al momento de la fecha de ingreso.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_edad_ingreso_legajos_update
BEFORE UPDATE ON legajos
FOR EACH ROW
BEGIN
    DECLARE fecha_nac DATE;
    SELECT fecha_nacimiento INTO fecha_nac FROM personas WHERE id_persona = NEW.id_persona;
    IF NEW.fecha_ingreso IS NOT NULL THEN
        IF TIMESTAMPDIFF(YEAR, fecha_nac, NEW.fecha_ingreso) < 18 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La persona debe tener al menos 18 años al momento de la fecha de ingreso.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_unico_insert
BEFORE INSERT ON legajos
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad
    FROM legajos
    WHERE id_persona = NEW.id_persona AND estado = 'activo';
    IF NEW.estado = 'activo' AND cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La persona ya tiene un legajo activo. No se permiten legajos duplicados.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_unico_update
BEFORE UPDATE ON legajos
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM legajos
    WHERE id_persona = NEW.id_persona AND estado = 'activo' AND id_legajo != NEW.id_legajo;
    IF NEW.estado = 'activo' AND cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La persona ya tiene un legajo activo. No se permiten legajos duplicados.';
    END IF;
END//

CREATE TRIGGER trg_val_id_persona_legajos_update
BEFORE UPDATE ON legajos
FOR EACH ROW
BEGIN
    IF NEW.id_persona != OLD.id_persona THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede cambiar la persona asignada a un legajo una vez creado.';
    END IF;
END//

-- ======================================================================
-- 3. TABLA: usuario
-- ======================================================================

CREATE TRIGGER trg_val_legajo_activo_usuario_insert
BEFORE INSERT ON usuario
FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_legajo = NEW.id_legajo;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede crear un usuario para un legajo dado de baja.';
    END IF;
END//

CREATE TRIGGER trg_val_reactivacion_usuario_update
BEFORE UPDATE ON usuario
FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_legajo = NEW.id_legajo;
    IF NEW.activo = 1 AND OLD.activo = 0 AND estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede reactivar un usuario cuyo legajo está dado de baja.';
    END IF;
END//

-- ======================================================================
-- 4. TABLA: titulos
-- ======================================================================

CREATE TRIGGER trg_val_fechas_titulos_insert
BEFORE INSERT ON titulos
FOR EACH ROW
BEGIN
    IF NEW.fecha_fin < NEW.fecha_inicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de fin del título no puede ser anterior a la fecha de inicio.';
    END IF;
END//

CREATE TRIGGER trg_val_fechas_titulos_update
BEFORE UPDATE ON titulos
FOR EACH ROW
BEGIN
    IF NEW.fecha_fin < NEW.fecha_inicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de fin del título no puede ser anterior a la fecha de inicio.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_titulos_insert
BEFORE INSERT ON titulos
FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_persona = NEW.id_persona;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede agregar un título a un legajo dado de baja.';
    END IF;
END//

CREATE TRIGGER trg_val_dup_titulo_insert
BEFORE INSERT ON titulos
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM titulos
    WHERE id_persona = NEW.id_persona
    AND titulo = NEW.titulo
    AND institucion = NEW.institucion
    AND fecha_inicio = NEW.fecha_inicio
    AND fecha_fin = NEW.fecha_fin;
    IF cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe un título idéntico registrado para este legajo.';
    END IF;
END//

-- ======================================================================
-- 5. TABLA: cursos
-- ======================================================================

CREATE TRIGGER trg_val_fechas_cursos_insert
BEFORE INSERT ON cursos
FOR EACH ROW
BEGIN
    IF NEW.fecha_inicio > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de inicio del curso no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fechas_cursos_update
BEFORE UPDATE ON cursos
FOR EACH ROW
BEGIN
    IF NEW.fecha_inicio > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de inicio del curso no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_cursos_insert
BEFORE INSERT ON cursos
FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_persona = NEW.id_persona;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede agregar un curso a un legajo dado de baja.';
    END IF;
END//

CREATE TRIGGER trg_val_horas_cursos_insert
BEFORE INSERT ON cursos
FOR EACH ROW
BEGIN
    IF NEW.horas IS NOT NULL AND NEW.horas <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La cantidad de horas del curso debe ser mayor a 0.';
    END IF;
END//

CREATE TRIGGER trg_val_horas_cursos_update
BEFORE UPDATE ON cursos
FOR EACH ROW
BEGIN
    IF NEW.horas IS NOT NULL AND NEW.horas <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La cantidad de horas del curso debe ser mayor a 0.';
    END IF;
END//

-- ======================================================================
-- 6. TABLA: idiomas
-- ======================================================================

CREATE TRIGGER trg_val_dup_idioma_insert
BEFORE INSERT ON idiomas
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM idiomas
    WHERE id_persona = NEW.id_persona AND nombre = NEW.nombre;
    IF cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe ese idioma registrado para este legajo.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_idiomas_insert
BEFORE INSERT ON idiomas
FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_persona = NEW.id_persona;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede agregar un idioma a un legajo dado de baja.';
    END IF;
END//

-- ======================================================================
-- 7. TABLA: familiar
-- ======================================================================

CREATE TRIGGER trg_val_dni_familiar_insert
BEFORE INSERT ON familiar
FOR EACH ROW
BEGIN
    DECLARE dni_empleado CHAR(8);
    SELECT p.dni INTO dni_empleado
    FROM personas p
    WHERE p.id_persona = NEW.id_persona;
    IF NEW.dni_familiar = dni_empleado THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El DNI del familiar no puede ser igual al DNI del empleado.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_familiar_update
BEFORE UPDATE ON familiar
FOR EACH ROW
BEGIN
    DECLARE dni_empleado CHAR(8);
    SELECT p.dni INTO dni_empleado
    FROM personas p
    WHERE p.id_persona = NEW.id_persona;
    IF NEW.dni_familiar = dni_empleado THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El DNI del familiar no puede ser igual al DNI del empleado.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_digitos_familiar_insert
BEFORE INSERT ON familiar
FOR EACH ROW
BEGIN
    IF NEW.dni_familiar REGEXP '[^0-9]' OR CHAR_LENGTH(NEW.dni_familiar) != 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El DNI del familiar debe contener exactamente 8 dígitos numéricos.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_digitos_familiar_update
BEFORE UPDATE ON familiar
FOR EACH ROW
BEGIN
    IF NEW.dni_familiar REGEXP '[^0-9]' OR CHAR_LENGTH(NEW.dni_familiar) != 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El DNI del familiar debe contener exactamente 8 dígitos numéricos.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_nac_familiar_insert
BEFORE INSERT ON familiar
FOR EACH ROW
BEGIN
    IF NEW.fecha_nac_familiar > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de nacimiento del familiar no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_nac_familiar_update
BEFORE UPDATE ON familiar
FOR EACH ROW
BEGIN
    IF NEW.fecha_nac_familiar > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de nacimiento del familiar no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_conyuge_unico_insert
BEFORE INSERT ON familiar
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM familiar
    WHERE id_persona = NEW.id_persona AND relacion_empleado = 'conyuge' AND activo = 1;
    IF NEW.relacion_empleado = 'conyuge' AND cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe un cónyuge activo registrado para este legajo.';
    END IF;
END//

CREATE TRIGGER trg_val_conyuge_unico_update
BEFORE UPDATE ON familiar
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM familiar
    WHERE id_persona = NEW.id_persona AND relacion_empleado = 'conyuge' AND activo = 1 AND id_familiar != NEW.id_familiar;
    IF NEW.relacion_empleado = 'conyuge' AND cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe un cónyuge activo registrado para este legajo.';
    END IF;
END//

CREATE TRIGGER trg_val_edad_familiar_insert
BEFORE INSERT ON familiar
FOR EACH ROW
BEGIN
    DECLARE fecha_nac_empleado DATE;
    SELECT p.fecha_nacimiento INTO fecha_nac_empleado
    FROM personas p
    WHERE p.id_persona = NEW.id_persona;
    IF NEW.relacion_empleado = 'hijos' AND NEW.fecha_nac_familiar <= fecha_nac_empleado THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un hijo debe ser menor que el empleado.';
    END IF;
    IF NEW.relacion_empleado IN ('padres', 'suegros') AND NEW.fecha_nac_familiar >= fecha_nac_empleado THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Padres y suegros deben ser mayores que el empleado.';
    END IF;
END//

CREATE TRIGGER trg_val_edad_familiar_update
BEFORE UPDATE ON familiar
FOR EACH ROW
BEGIN
    DECLARE fecha_nac_empleado DATE;
    SELECT p.fecha_nacimiento INTO fecha_nac_empleado
    FROM personas p
    WHERE p.id_persona = NEW.id_persona;
    IF NEW.relacion_empleado = 'hijos' AND NEW.fecha_nac_familiar <= fecha_nac_empleado THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Un hijo debe ser menor que el empleado.';
    END IF;
    IF NEW.relacion_empleado IN ('padres', 'suegros') AND NEW.fecha_nac_familiar >= fecha_nac_empleado THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Padres y suegros deben ser mayores que el empleado.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_familiar_insert
BEFORE INSERT ON familiar
FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_persona = NEW.id_persona;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede agregar un familiar a un legajo dado de baja.';
    END IF;
END//

-- ======================================================================
-- 8. TABLA: antecedente_laboral
-- ======================================================================

CREATE TRIGGER trg_val_fechas_antecedente_insert
BEFORE INSERT ON antecedente_laboral
FOR EACH ROW
BEGIN
    IF NEW.fecha_fin IS NOT NULL AND NEW.fecha_inicio IS NOT NULL THEN
        IF NEW.fecha_fin < NEW.fecha_inicio THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La fecha de fin del antecedente laboral no puede ser anterior a la fecha de inicio.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_fechas_antecedente_update
BEFORE UPDATE ON antecedente_laboral
FOR EACH ROW
BEGIN
    IF NEW.fecha_fin IS NOT NULL AND NEW.fecha_inicio IS NOT NULL THEN
        IF NEW.fecha_fin < NEW.fecha_inicio THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La fecha de fin del antecedente laboral no puede ser anterior a la fecha de inicio.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_antecedente_insert
BEFORE INSERT ON antecedente_laboral
FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_persona = NEW.id_persona;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede agregar un antecedente laboral a un legajo dado de baja.';
    END IF;
END//

-- ======================================================================
-- 9. TABLA: documentos
-- ======================================================================

CREATE TRIGGER trg_val_legajo_activo_documentos_insert
BEFORE INSERT ON documentos
FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_persona = NEW.id_persona;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede agregar un documento a un legajo dado de baja.';
    END IF;
END//

CREATE TRIGGER trg_val_hash_dup_documentos_insert
BEFORE INSERT ON documentos
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM documentos
    WHERE id_persona = NEW.id_persona AND hash_archivo = NEW.hash_archivo;
    IF cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe un documento con el mismo contenido cargado para este legajo.';
    END IF;
END//

CREATE TRIGGER trg_val_foto_perfil_unica_insert
BEFORE INSERT ON documentos
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM documentos
    WHERE id_persona = NEW.id_persona AND tipo_doc = 'FOTO_PERFIL' AND activo = 1;
    IF NEW.tipo_doc = 'FOTO_PERFIL' AND cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe una foto de perfil activa para este legajo.';
    END IF;
END//

CREATE TRIGGER trg_val_foto_perfil_unica_update
BEFORE UPDATE ON documentos
FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM documentos
    WHERE id_persona = NEW.id_persona AND tipo_doc = 'FOTO_PERFIL' AND activo = 1 AND id_documento != NEW.id_documento;
    IF NEW.tipo_doc = 'FOTO_PERFIL' AND NEW.activo = 1 AND cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe una foto de perfil activa para este legajo.';
    END IF;
END//

CREATE TRIGGER trg_val_mime_extension_insert
BEFORE INSERT ON documentos
FOR EACH ROW
BEGIN
    IF (NEW.mime_type = 'application/pdf' AND NEW.extension != 'pdf')
        OR (NEW.mime_type = 'image/jpeg' AND NEW.extension NOT IN ('jpg', 'jpeg'))
        OR (NEW.mime_type = 'image/png' AND NEW.extension != 'png')
        OR (NEW.mime_type = 'application/msword' AND NEW.extension != 'doc')
        OR (NEW.mime_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' AND NEW.extension != 'docx') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La extensión del archivo no es coherente con el tipo MIME indicado.';
    END IF;
END//

CREATE TRIGGER trg_val_mime_extension_update
BEFORE UPDATE ON documentos
FOR EACH ROW
BEGIN
    IF (NEW.mime_type = 'application/pdf' AND NEW.extension != 'pdf')
        OR (NEW.mime_type = 'image/jpeg' AND NEW.extension NOT IN ('jpg', 'jpeg'))
        OR (NEW.mime_type = 'image/png' AND NEW.extension != 'png')
        OR (NEW.mime_type = 'application/msword' AND NEW.extension != 'doc')
        OR (NEW.mime_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' AND NEW.extension != 'docx') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La extensión del archivo no es coherente con el tipo MIME indicado.';
    END IF;
END//

DELIMITER ;
