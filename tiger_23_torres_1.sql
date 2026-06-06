USE torres_corregida1;

-- LISTADO COMPLETO DE TRIGGERS PARA torres_corregida1
-- OBJETIVO: Mantener copia de los datos en el tiempo para futuras consultas

-- 1. TABLA: personas
-- TRIGGER #1:  trg_historico_personas_insert     - Guarda luego de un INSERT
-- TRIGGER #2:  trg_historico_personas_update     - Guarda antes de un UPDATE

-- 2. TABLA: legajos
-- TRIGGER #3:  trg_historico_legajos_insert      - Guarda luego de un INSERT
-- TRIGGER #4:  trg_historico_legajos_update      - Guarda antes de un UPDATE

-- 3. TABLA: titulos
-- TRIGGER #5:  trg_historico_titulos_insert      - Guarda luego de un INSERT
-- TRIGGER #6:  trg_historico_titulos_update      - Guarda antes de un UPDATE

-- 4. TABLA: cursos
-- TRIGGER #7:  trg_historico_cursos_insert       - Guarda luego de un INSERT
-- TRIGGER #8:  trg_historico_cursos_update       - Guarda antes de un UPDATE

-- 5. TABLA: idiomas
-- TRIGGER #9:  trg_historico_idiomas_insert      - Guarda luego de un INSERT
-- TRIGGER #10: trg_historico_idiomas_update      - Guarda antes de un UPDATE

-- 6. TABLA: familiar
-- TRIGGER #11: trg_historico_familiar_insert     - Guarda luego de un INSERT
-- TRIGGER #12: trg_historico_familiar_update     - Guarda antes de un UPDATE

-- 7. TABLA: antecedente_laboral
-- TRIGGER #13: trg_historico_antecedente_laboral_insert   - Guarda luego de un INSERT
-- TRIGGER #14: trg_historico_antecedente_laboral_update   - Guarda antes de un UPDATE

-- 8. TABLA: historial_legajos
-- TRIGGER #15: trg_historico_historial_legajos_insert     - Guarda luego de un INSERT
-- TRIGGER #16: trg_historico_historial_legajos_update     - Guarda antes de un UPDATE

-- 9. TABLA: sumarios
-- TRIGGER #17: trg_historico_sumarios_insert     - Guarda luego de un INSERT
-- TRIGGER #18: trg_historico_sumarios_update     - Guarda antes de un UPDATE

-- 10. TABLA: documentos
-- TRIGGER #19: trg_historico_documentos_insert   - Guarda luego de un INSERT
-- TRIGGER #20: trg_historico_documentos_update   - Guarda antes de un UPDATE

-- 11. TABLA: usuario
-- TRIGGER #21: trg_historico_usuario_insert      - Guarda luego de un INSERT
-- TRIGGER #22: trg_historico_usuario_update      - Guarda antes de un UPDATE

-- TRIGGER #23: tgr_borrado_logico_total          - Cuando un legajo se da de baja, desactiva TODOS los registros relacionados en todas las tablas (activo = 0), desactiva el usuario y crea un sumario con el detalle de la baja
--                                                   Si el legajo se reactiva, vuelve a activar todos los registros (activo = 1)

-- ======================================================================
-- LIMPIEZA PREVIA DE DISPARADORES
-- ======================================================================
DROP TRIGGER IF EXISTS trg_historico_personas_insert;
DROP TRIGGER IF EXISTS trg_historico_personas_update;
DROP TRIGGER IF EXISTS trg_historico_legajos_insert;
DROP TRIGGER IF EXISTS trg_historico_legajos_update;
DROP TRIGGER IF EXISTS trg_historico_titulos_insert;
DROP TRIGGER IF EXISTS trg_historico_titulos_update;
DROP TRIGGER IF EXISTS trg_historico_cursos_insert;
DROP TRIGGER IF EXISTS trg_historico_cursos_update;
DROP TRIGGER IF EXISTS trg_historico_idiomas_insert;
DROP TRIGGER IF EXISTS trg_historico_idiomas_update;
DROP TRIGGER IF EXISTS trg_historico_familiar_insert;
DROP TRIGGER IF EXISTS trg_historico_familiar_update;
DROP TRIGGER IF EXISTS trg_historico_antecedente_laboral_insert;
DROP TRIGGER IF EXISTS trg_historico_antecedente_laboral_update;
DROP TRIGGER IF EXISTS trg_historico_historial_legajos_insert;
DROP TRIGGER IF EXISTS trg_historico_historial_legajos_update;
DROP TRIGGER IF EXISTS trg_historico_sumarios_insert;
DROP TRIGGER IF EXISTS trg_historico_sumarios_update;
DROP TRIGGER IF EXISTS trg_historico_documentos_insert;
DROP TRIGGER IF EXISTS trg_historico_documentos_update;
DROP TRIGGER IF EXISTS trg_historico_usuario_insert;
DROP TRIGGER IF EXISTS trg_historico_usuario_update;
DROP TRIGGER IF EXISTS tgr_borrado_logico_total;

DELIMITER //

-- ======================================================================
-- 1. TABLA: personas
-- ======================================================================

CREATE TRIGGER trg_historico_personas_insert
AFTER INSERT ON personas
FOR EACH ROW
BEGIN
    INSERT INTO historico_personas (
        id_persona, dni, apellido, nombre, genero, fecha_nacimiento,
        estado_civil, cantidad_hijos, provincia_residencia, ciudad_residencia,
        domicilio_datos, cuil, telefono, telefono_emergencia, email,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_persona, NEW.dni, NEW.apellido, NEW.nombre, NEW.genero, NEW.fecha_nacimiento,
        NEW.estado_civil, NEW.cantidad_hijos, NEW.provincia_residencia, NEW.ciudad_residencia,
        NEW.domicilio_datos, NEW.cuil, NEW.telefono, NEW.telefono_emergencia, NEW.email,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_personas_update
BEFORE UPDATE ON personas
FOR EACH ROW
BEGIN
    INSERT INTO historico_personas (
        id_persona, dni, apellido, nombre, genero, fecha_nacimiento,
        estado_civil, cantidad_hijos, provincia_residencia, ciudad_residencia,
        domicilio_datos, cuil, telefono, telefono_emergencia, email,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_persona, OLD.dni, OLD.apellido, OLD.nombre, OLD.genero, OLD.fecha_nacimiento,
        OLD.estado_civil, OLD.cantidad_hijos, OLD.provincia_residencia, OLD.ciudad_residencia,
        OLD.domicilio_datos, OLD.cuil, OLD.telefono, OLD.telefono_emergencia, OLD.email,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 2. TABLA: legajos
-- ======================================================================

CREATE TRIGGER trg_historico_legajos_insert
AFTER INSERT ON legajos
FOR EACH ROW
BEGIN
    INSERT INTO historico_legajos (
        id_legajo, fecha_registro, fecha_ingreso, fecha_ingreso_administracion,
        id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_legajo, DATE(NEW.fecha_registro), NEW.fecha_ingreso, NEW.fecha_ingreso_administracion,
        NEW.id_cargo, NEW.id_categoria, NEW.id_oficina, NEW.tipo_contrato, NEW.estado, NEW.id_persona,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_legajos_update
BEFORE UPDATE ON legajos
FOR EACH ROW
BEGIN
    INSERT INTO historico_legajos (
        id_legajo, fecha_registro, fecha_ingreso, fecha_ingreso_administracion,
        id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_legajo, DATE(OLD.fecha_registro), OLD.fecha_ingreso, OLD.fecha_ingreso_administracion,
        OLD.id_cargo, OLD.id_categoria, OLD.id_oficina, OLD.tipo_contrato, OLD.estado, OLD.id_persona,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 3. TABLA: titulos
-- ======================================================================

CREATE TRIGGER trg_historico_titulos_insert
AFTER INSERT ON titulos
FOR EACH ROW
BEGIN
    INSERT INTO historico_titulos (
        id_titulo, titulo, nivel_estudio, institucion, fecha_inicio, fecha_fin, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_titulo, NEW.titulo, NEW.nivel_estudio, NEW.institucion, NEW.fecha_inicio, NEW.fecha_fin, NEW.id_legajo,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_titulos_update
BEFORE UPDATE ON titulos
FOR EACH ROW
BEGIN
    INSERT INTO historico_titulos (
        id_titulo, titulo, nivel_estudio, institucion, fecha_inicio, fecha_fin, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_titulo, OLD.titulo, OLD.nivel_estudio, OLD.institucion, OLD.fecha_inicio, OLD.fecha_fin, OLD.id_legajo,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 4. TABLA: cursos
-- ======================================================================

CREATE TRIGGER trg_historico_cursos_insert
AFTER INSERT ON cursos
FOR EACH ROW
BEGIN
    INSERT INTO historico_cursos (
        id_curso, nombre, institucion, fecha_inicio, horas, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_curso, NEW.nombre, NEW.institucion, NEW.fecha_inicio, NEW.horas, NEW.id_legajo,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_cursos_update
BEFORE UPDATE ON cursos
FOR EACH ROW
BEGIN
    INSERT INTO historico_cursos (
        id_curso, nombre, institucion, fecha_inicio, horas, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_curso, OLD.nombre, OLD.institucion, OLD.fecha_inicio, OLD.horas, OLD.id_legajo,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 5. TABLA: idiomas
-- ======================================================================

CREATE TRIGGER trg_historico_idiomas_insert
AFTER INSERT ON idiomas
FOR EACH ROW
BEGIN
    INSERT INTO historico_idiomas (
        id_idioma, nombre, nivel, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_idioma, NEW.nombre, NEW.nivel, NEW.id_legajo,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_idiomas_update
BEFORE UPDATE ON idiomas
FOR EACH ROW
BEGIN
    INSERT INTO historico_idiomas (
        id_idioma, nombre, nivel, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_idioma, OLD.nombre, OLD.nivel, OLD.id_legajo,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 6. TABLA: familiar
-- ======================================================================

CREATE TRIGGER trg_historico_familiar_insert
AFTER INSERT ON familiar
FOR EACH ROW
BEGIN
    INSERT INTO historico_familiar (
        id_familiar, nombre_familiar, apellido_familiar, dni_familiar,
        fecha_nac_familiar, estado, relacion_empleado, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_familiar, NEW.nombre_familiar, NEW.apellido_familiar, NEW.dni_familiar,
        NEW.fecha_nac_familiar, NEW.estado, NEW.relacion_empleado, NEW.id_legajo,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_familiar_update
BEFORE UPDATE ON familiar
FOR EACH ROW
BEGIN
    INSERT INTO historico_familiar (
        id_familiar, nombre_familiar, apellido_familiar, dni_familiar,
        fecha_nac_familiar, estado, relacion_empleado, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_familiar, OLD.nombre_familiar, OLD.apellido_familiar, OLD.dni_familiar,
        OLD.fecha_nac_familiar, OLD.estado, OLD.relacion_empleado, OLD.id_legajo,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 7. TABLA: antecedente_laboral
-- ======================================================================

CREATE TRIGGER trg_historico_antecedente_laboral_insert
AFTER INSERT ON antecedente_laboral
FOR EACH ROW
BEGIN
    INSERT INTO historico_antecedente_laboral (
        id_antecedente, empresa, cargo, fecha_inicio, fecha_fin, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_antecedente, NEW.empresa, NEW.cargo, NEW.fecha_inicio, NEW.fecha_fin, NEW.id_legajo,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_antecedente_laboral_update
BEFORE UPDATE ON antecedente_laboral
FOR EACH ROW
BEGIN
    INSERT INTO historico_antecedente_laboral (
        id_antecedente, empresa, cargo, fecha_inicio, fecha_fin, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_antecedente, OLD.empresa, OLD.cargo, OLD.fecha_inicio, OLD.fecha_fin, OLD.id_legajo,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 8. TABLA: historial_legajos
-- ======================================================================

CREATE TRIGGER trg_historico_historial_legajos_insert
AFTER INSERT ON historial_legajos
FOR EACH ROW
BEGIN
    INSERT INTO historico_historial_legajos (
        id_historial, fecha_registro, accion, detalle, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_historial, DATE(NEW.fecha_registro), NEW.accion, NEW.detalle, NEW.id_legajo,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_historial_legajos_update
BEFORE UPDATE ON historial_legajos
FOR EACH ROW
BEGIN
    INSERT INTO historico_historial_legajos (
        id_historial, fecha_registro, accion, detalle, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_historial, DATE(OLD.fecha_registro), OLD.accion, OLD.detalle, OLD.id_legajo,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 9. TABLA: sumarios
-- ======================================================================

CREATE TRIGGER trg_historico_sumarios_insert
AFTER INSERT ON sumarios
FOR EACH ROW
BEGIN
    INSERT INTO historico_sumarios (
        id_sumario, fecha_registro, detalle, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_sumario, DATE(NEW.fecha_registro), NEW.detalle, NEW.id_legajo,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_sumarios_update
BEFORE UPDATE ON sumarios
FOR EACH ROW
BEGIN
    INSERT INTO historico_sumarios (
        id_sumario, fecha_registro, detalle, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_sumario, DATE(OLD.fecha_registro), OLD.detalle, OLD.id_legajo,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 10. TABLA: documentos
-- ======================================================================

CREATE TRIGGER trg_historico_documentos_insert
AFTER INSERT ON documentos
FOR EACH ROW
BEGIN
    INSERT INTO historico_documentos (
        id_documento, tipo_doc, creado_en, descripcion, nombre_archivo,
        ruta_archivo, tamano_archivo, mime_type, extension, hash_archivo, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_documento, NEW.tipo_doc, DATE(NEW.creado_en), NEW.descripcion, NEW.nombre_archivo,
        NEW.ruta_archivo, NEW.tamano_archivo, NEW.mime_type, NEW.extension, NEW.hash_archivo, NEW.id_legajo,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_documentos_update
BEFORE UPDATE ON documentos
FOR EACH ROW
BEGIN
    INSERT INTO historico_documentos (
        id_documento, tipo_doc, creado_en, descripcion, nombre_archivo,
        ruta_archivo, tamano_archivo, mime_type, extension, hash_archivo, id_legajo,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_documento, OLD.tipo_doc, DATE(OLD.creado_en), OLD.descripcion, OLD.nombre_archivo,
        OLD.ruta_archivo, OLD.tamano_archivo, OLD.mime_type, OLD.extension, OLD.hash_archivo, OLD.id_legajo,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 11. TABLA: usuario
-- ======================================================================

CREATE TRIGGER trg_historico_usuario_insert
AFTER INSERT ON usuario
FOR EACH ROW
BEGIN
    INSERT INTO historico_usuario (
        id_usuario, usuario, pass, tipo, id_legajo, primer_ingreso, activo, fecha_creacion, ultimo_login,
        usuario_accion, tipo_cambio
    ) VALUES (
        NEW.id_usuario, NEW.usuario, NEW.pass, NEW.tipo, NEW.id_legajo, NEW.primer_ingreso, NEW.activo, DATE(NEW.fecha_creacion), NEW.ultimo_login,
        USER(), 'INSERT'
    );
END//

CREATE TRIGGER trg_historico_usuario_update
BEFORE UPDATE ON usuario
FOR EACH ROW
BEGIN
    INSERT INTO historico_usuario (
        id_usuario, usuario, pass, tipo, id_legajo, primer_ingreso, activo, fecha_creacion, ultimo_login,
        usuario_accion, tipo_cambio
    ) VALUES (
        OLD.id_usuario, OLD.usuario, OLD.pass, OLD.tipo, OLD.id_legajo, OLD.primer_ingreso, OLD.activo, DATE(OLD.fecha_creacion), OLD.ultimo_login,
        USER(), 'UPDATE'
    );
END//

-- ======================================================================
-- 12. TRIGGER #23: BORRADO Y REACTIVACIÓN LÓGICA GLOBAL
-- ======================================================================

CREATE TRIGGER tgr_borrado_logico_total
AFTER UPDATE ON legajos
FOR EACH ROW
BEGIN
    IF NEW.estado = 'de_baja' AND OLD.estado != 'de_baja' THEN
        UPDATE usuario SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE titulos SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE cursos SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE idiomas SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE familiar SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE antecedente_laboral SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE historial_legajos SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE sumarios SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE documentos SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        INSERT INTO sumarios (detalle, id_legajo, activo)
        VALUES (CONCAT('Baja de legajo registrada por el sistema. Estado anterior: ', OLD.estado, '. Usuario: ', USER(), '.'), NEW.id_legajo, 0);

    ELSEIF NEW.estado = 'activo' AND OLD.estado = 'de_baja' THEN
        UPDATE usuario SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE titulos SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE cursos SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE idiomas SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE familiar SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE antecedente_laboral SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE historial_legajos SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE sumarios SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE documentos SET activo = 1 WHERE id_legajo = NEW.id_legajo;
    END IF;
END//

DELIMITER ;
