USE torres_corregida1;

-- ============================================================
-- SISTEMA TORRES_CORREGIDA1 — TRIGGERS (TIGERS)
-- ============================================================
--
-- INDICE COMPLETO DE TRIGGERS:
-- ============================================================
--
-- [GRUPO 1 — AUDITORIA: guarda estado anterior en tabla historica]
--   trg_historico_personas_insert          — AFTER INSERT ON personas
--   trg_historico_personas_update          — BEFORE UPDATE ON personas
--   trg_historico_legajos_insert           — AFTER INSERT ON legajos
--   trg_historico_legajos_update           — BEFORE UPDATE ON legajos
--   trg_historico_titulos_insert           — AFTER INSERT ON titulos
--   trg_historico_titulos_update           — BEFORE UPDATE ON titulos
--   trg_historico_cursos_insert            — AFTER INSERT ON cursos
--   trg_historico_cursos_update            — BEFORE UPDATE ON cursos
--   trg_historico_idiomas_insert           — AFTER INSERT ON idiomas
--   trg_historico_idiomas_update           — BEFORE UPDATE ON idiomas
--   trg_historico_familiar_insert          — AFTER INSERT ON familiar
--   trg_historico_familiar_update          — BEFORE UPDATE ON familiar
--   trg_historico_antecedente_insert       — AFTER INSERT ON antecedente_laboral
--   trg_historico_antecedente_update       — BEFORE UPDATE ON antecedente_laboral
--   trg_historico_historial_insert         — AFTER INSERT ON historial_legajos
--   trg_historico_historial_update         — BEFORE UPDATE ON historial_legajos
--   trg_historico_sumarios_insert          — AFTER INSERT ON sumarios
--   trg_historico_sumarios_update          — BEFORE UPDATE ON sumarios
--   trg_historico_documentos_insert        — AFTER INSERT ON documentos
--   trg_historico_documentos_update        — BEFORE UPDATE ON documentos
--   trg_historico_usuario_insert           — AFTER INSERT ON usuario
--   trg_historico_usuario_update           — BEFORE UPDATE ON usuario
--
-- [GRUPO 2 — BAJA LOGICA: desactiva registros relacionados]
--   tgr_borrado_logico_total               — AFTER UPDATE ON legajos
--                                            cuando estado cambia a 'de_baja':
--                                            desactiva usuario, titulos, cursos, idiomas,
--                                            familiar, antecedentes, historial, sumarios,
--                                            documentos, sesiones_activas
--
-- [GRUPO 3 — CREACION AUTOMATICA DE USUARIO]
--   trg_crear_usuario_al_crear_legajo      — AFTER INSERT ON legajos
--                                            usuario = CONVERT(id_legajo, CHAR)
--                                            pass = SHA2(dni_persona, 256)
--                                            tipo = 'empleado' (por defecto)
--
-- [GRUPO 4 — EVENTOS Y AUDITORIA DE USO (ip, usuario, tabla, horario)]
--   trg_evento_login_update                — AFTER UPDATE ON usuario
--                                            cuando cambia ultimo_login: registra en
--                                            log_sistema y eventos con IP, usuario, horario
--   trg_evento_cambio_pass                 — AFTER UPDATE ON usuario
--                                            cuando cambia la pass: registra en log_sistema
--   trg_evento_baja_logica                 — AFTER UPDATE ON legajos
--                                            cuando cambia a de_baja: registra en eventos
--
-- [GRUPO 5 — VALIDACIONES DE EDAD Y DATOS]
--   trg_val_edad_minima_personas_insert    — BEFORE INSERT ON personas (mayor de 18)
--   trg_val_edad_minima_personas_update    — BEFORE UPDATE ON personas
--   trg_val_fecha_nac_personas_insert      — BEFORE INSERT ON personas (fecha no futura)
--   trg_val_fecha_nac_personas_update      — BEFORE UPDATE ON personas
--   trg_val_dni_personas_insert            — BEFORE INSERT ON personas (8 digitos)
--   trg_val_dni_personas_update            — BEFORE UPDATE ON personas
--   trg_val_cuil_personas_insert           — BEFORE INSERT ON personas (formato XX-XXXXXXXX-X)
--   trg_val_cuil_personas_update           — BEFORE UPDATE ON personas
--   trg_val_email_personas_insert          — BEFORE INSERT ON personas (contiene @ y punto)
--   trg_val_email_personas_update          — BEFORE UPDATE ON personas
--   trg_val_telefono_personas_insert       — BEFORE INSERT ON personas (solo digitos)
--   trg_val_telefono_personas_update       — BEFORE UPDATE ON personas
--   trg_val_fecha_ingreso_legajos_insert   — BEFORE INSERT ON legajos (no futura)
--   trg_val_fecha_ingreso_legajos_update   — BEFORE UPDATE ON legajos
--   trg_val_fechas_legajos_insert          — BEFORE INSERT ON legajos (adm >= ingreso)
--   trg_val_fechas_legajos_update          — BEFORE UPDATE ON legajos
--   trg_val_edad_ingreso_legajos_insert    — BEFORE INSERT ON legajos (18+ al ingresar)
--   trg_val_edad_ingreso_legajos_update    — BEFORE UPDATE ON legajos
--   trg_val_legajo_activo_unico_insert     — BEFORE INSERT ON legajos (no 2 legajos activos)
--   trg_val_legajo_activo_unico_update     — BEFORE UPDATE ON legajos
--   trg_val_id_persona_legajos_update      — BEFORE UPDATE ON legajos (no cambiar persona)
--   trg_val_legajo_activo_usuario_insert   — BEFORE INSERT ON usuario (no crear si de_baja)
--   trg_val_reactivacion_usuario_update    — BEFORE UPDATE ON usuario (no reactivar si de_baja)
--
-- [GRUPO 6 — SINCRONIZACION Y REGLAS DE NEGOCIO]
--   trg_primer_ingreso_usuario             — AFTER UPDATE ON usuario
--   trg_bloqueo_admin_unico_update         — BEFORE UPDATE ON usuario (no quedar sin admin)
--   trg_bloqueo_admin_unico_delete         — BEFORE DELETE ON usuario
--   trg_sync_cantidad_hijos_insert         — AFTER INSERT ON familiar
--   trg_sync_cantidad_hijos_delete         — BEFORE DELETE ON familiar
--   trg_sync_estado_civil_conyuge          — AFTER UPDATE ON familiar (conyuge fallecido)
--   trg_validar_historial_sumario          — BEFORE INSERT ON historial_legajos
--   trg_validar_estado_legajo_historial    — BEFORE INSERT ON historial_legajos
--
-- ============================================================

-- ============================================================
-- LIMPIEZA PREVIA
-- ============================================================
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
DROP TRIGGER IF EXISTS trg_crear_usuario_al_crear_legajo;
DROP TRIGGER IF EXISTS trg_evento_login_update;
DROP TRIGGER IF EXISTS trg_evento_cambio_pass;
DROP TRIGGER IF EXISTS trg_evento_baja_logica;
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
DROP TRIGGER IF EXISTS trg_primer_ingreso_usuario;
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

-- ============================================================
-- GRUPO 1: AUDITORIA — guarda estado anterior en historicas
-- ============================================================

-- personas INSERT → guarda el nuevo registro
CREATE TRIGGER trg_historico_personas_insert
AFTER INSERT ON personas FOR EACH ROW
BEGIN
    INSERT INTO historico_personas (id_persona, dni, apellido, nombre, genero,
        fecha_nacimiento, estado_civil, cantidad_hijos, provincia_residencia,
        ciudad_residencia, domicilio_datos, cuil, telefono, telefono_emergencia,
        email, usuario_accion, tipo_cambio)
    VALUES (NEW.id_persona, NEW.dni, NEW.apellido, NEW.nombre, NEW.genero,
        NEW.fecha_nacimiento, NEW.estado_civil, NEW.cantidad_hijos,
        NEW.provincia_residencia, NEW.ciudad_residencia, NEW.domicilio_datos,
        NEW.cuil, NEW.telefono, NEW.telefono_emergencia, NEW.email,
        USER(), 'INSERT');
END//

-- personas UPDATE → guarda el valor ANTERIOR
CREATE TRIGGER trg_historico_personas_update
BEFORE UPDATE ON personas FOR EACH ROW
BEGIN
    INSERT INTO historico_personas (id_persona, dni, apellido, nombre, genero,
        fecha_nacimiento, estado_civil, cantidad_hijos, provincia_residencia,
        ciudad_residencia, domicilio_datos, cuil, telefono, telefono_emergencia,
        email, usuario_accion, tipo_cambio)
    VALUES (OLD.id_persona, OLD.dni, OLD.apellido, OLD.nombre, OLD.genero,
        OLD.fecha_nacimiento, OLD.estado_civil, OLD.cantidad_hijos,
        OLD.provincia_residencia, OLD.ciudad_residencia, OLD.domicilio_datos,
        OLD.cuil, OLD.telefono, OLD.telefono_emergencia, OLD.email,
        USER(), 'UPDATE');
END//

-- legajos INSERT
CREATE TRIGGER trg_historico_legajos_insert
AFTER INSERT ON legajos FOR EACH ROW
BEGIN
    INSERT INTO historico_legajos (id_legajo, id_persona, fecha_ingreso,
        fecha_ingreso_administracion, id_cargo, id_categoria, id_oficina,
        tipo_contrato, estado, usuario_accion, tipo_cambio)
    VALUES (NEW.id_legajo, NEW.id_persona, NEW.fecha_ingreso,
        NEW.fecha_ingreso_administracion, NEW.id_cargo, NEW.id_categoria,
        NEW.id_oficina, NEW.tipo_contrato, NEW.estado, USER(), 'INSERT');
END//

-- legajos UPDATE → guarda valor ANTERIOR
CREATE TRIGGER trg_historico_legajos_update
BEFORE UPDATE ON legajos FOR EACH ROW
BEGIN
    INSERT INTO historico_legajos (id_legajo, id_persona, fecha_ingreso,
        fecha_ingreso_administracion, id_cargo, id_categoria, id_oficina,
        tipo_contrato, estado, usuario_accion, tipo_cambio)
    VALUES (OLD.id_legajo, OLD.id_persona, OLD.fecha_ingreso,
        OLD.fecha_ingreso_administracion, OLD.id_cargo, OLD.id_categoria,
        OLD.id_oficina, OLD.tipo_contrato, OLD.estado, USER(), 'UPDATE');
END//

-- titulos INSERT
CREATE TRIGGER trg_historico_titulos_insert
AFTER INSERT ON titulos FOR EACH ROW
BEGIN
    INSERT INTO historico_titulos (id_titulo, id_persona, nombre_titulo,
        institucion, fecha_inicio, fecha_fin, activo, usuario_accion, tipo_cambio)
    VALUES (NEW.id_titulo, NEW.id_persona, NEW.nombre_titulo,
        NEW.institucion, NEW.fecha_inicio, NEW.fecha_fin, NEW.activo,
        USER(), 'INSERT');
END//

-- titulos UPDATE
CREATE TRIGGER trg_historico_titulos_update
BEFORE UPDATE ON titulos FOR EACH ROW
BEGIN
    INSERT INTO historico_titulos (id_titulo, id_persona, nombre_titulo,
        institucion, fecha_inicio, fecha_fin, activo, usuario_accion, tipo_cambio)
    VALUES (OLD.id_titulo, OLD.id_persona, OLD.nombre_titulo,
        OLD.institucion, OLD.fecha_inicio, OLD.fecha_fin, OLD.activo,
        USER(), 'UPDATE');
END//

-- cursos INSERT
CREATE TRIGGER trg_historico_cursos_insert
AFTER INSERT ON cursos FOR EACH ROW
BEGIN
    INSERT INTO historico_cursos (id_curso, id_persona, nombre_curso,
        institucion, fecha_inicio, horas, activo, usuario_accion, tipo_cambio)
    VALUES (NEW.id_curso, NEW.id_persona, NEW.nombre_curso,
        NEW.institucion, NEW.fecha_inicio, NEW.horas, NEW.activo,
        USER(), 'INSERT');
END//

-- cursos UPDATE
CREATE TRIGGER trg_historico_cursos_update
BEFORE UPDATE ON cursos FOR EACH ROW
BEGIN
    INSERT INTO historico_cursos (id_curso, id_persona, nombre_curso,
        institucion, fecha_inicio, horas, activo, usuario_accion, tipo_cambio)
    VALUES (OLD.id_curso, OLD.id_persona, OLD.nombre_curso,
        OLD.institucion, OLD.fecha_inicio, OLD.horas, OLD.activo,
        USER(), 'UPDATE');
END//

-- idiomas INSERT
CREATE TRIGGER trg_historico_idiomas_insert
AFTER INSERT ON idiomas FOR EACH ROW
BEGIN
    INSERT INTO historico_idiomas (id_idioma, id_persona, nombre, nivel,
        activo, usuario_accion, tipo_cambio)
    VALUES (NEW.id_idioma, NEW.id_persona, NEW.nombre, NEW.nivel,
        NEW.activo, USER(), 'INSERT');
END//

-- idiomas UPDATE
CREATE TRIGGER trg_historico_idiomas_update
BEFORE UPDATE ON idiomas FOR EACH ROW
BEGIN
    INSERT INTO historico_idiomas (id_idioma, id_persona, nombre, nivel,
        activo, usuario_accion, tipo_cambio)
    VALUES (OLD.id_idioma, OLD.id_persona, OLD.nombre, OLD.nivel,
        OLD.activo, USER(), 'UPDATE');
END//

-- familiar INSERT
CREATE TRIGGER trg_historico_familiar_insert
AFTER INSERT ON familiar FOR EACH ROW
BEGIN
    INSERT INTO historico_familiar (id_familiar, id_persona, relacion_empleado,
        apellido_familiar, nombre_familiar, dni_familiar, fecha_nacimiento_familiar,
        activo, usuario_accion, tipo_cambio)
    VALUES (NEW.id_familiar, NEW.id_persona, NEW.relacion_empleado,
        NEW.apellido_familiar, NEW.nombre_familiar, NEW.dni_familiar,
        NEW.fecha_nacimiento_familiar, NEW.activo, USER(), 'INSERT');
END//

-- familiar UPDATE
CREATE TRIGGER trg_historico_familiar_update
BEFORE UPDATE ON familiar FOR EACH ROW
BEGIN
    INSERT INTO historico_familiar (id_familiar, id_persona, relacion_empleado,
        apellido_familiar, nombre_familiar, dni_familiar, fecha_nacimiento_familiar,
        activo, usuario_accion, tipo_cambio)
    VALUES (OLD.id_familiar, OLD.id_persona, OLD.relacion_empleado,
        OLD.apellido_familiar, OLD.nombre_familiar, OLD.dni_familiar,
        OLD.fecha_nacimiento_familiar, OLD.activo, USER(), 'UPDATE');
END//

-- antecedente_laboral INSERT
CREATE TRIGGER trg_historico_antecedente_laboral_insert
AFTER INSERT ON antecedente_laboral FOR EACH ROW
BEGIN
    INSERT INTO historico_antecedente_laboral (id_antecedente, id_persona,
        empresa, cargo_ocupado, fecha_inicio, fecha_fin, activo,
        usuario_accion, tipo_cambio)
    VALUES (NEW.id_antecedente, NEW.id_persona, NEW.empresa,
        NEW.cargo_ocupado, NEW.fecha_inicio, NEW.fecha_fin, NEW.activo,
        USER(), 'INSERT');
END//

-- antecedente_laboral UPDATE
CREATE TRIGGER trg_historico_antecedente_laboral_update
BEFORE UPDATE ON antecedente_laboral FOR EACH ROW
BEGIN
    INSERT INTO historico_antecedente_laboral (id_antecedente, id_persona,
        empresa, cargo_ocupado, fecha_inicio, fecha_fin, activo,
        usuario_accion, tipo_cambio)
    VALUES (OLD.id_antecedente, OLD.id_persona, OLD.empresa,
        OLD.cargo_ocupado, OLD.fecha_inicio, OLD.fecha_fin, OLD.activo,
        USER(), 'UPDATE');
END//

-- historial_legajos INSERT
CREATE TRIGGER trg_historico_historial_legajos_insert
AFTER INSERT ON historial_legajos FOR EACH ROW
BEGIN
    INSERT INTO historico_historial_legajos (id_historial, id_legajo,
        accion, detalle, activo, usuario_accion, tipo_cambio)
    VALUES (NEW.id_historial, NEW.id_legajo, NEW.accion, NEW.detalle,
        NEW.activo, USER(), 'INSERT');
END//

-- historial_legajos UPDATE
CREATE TRIGGER trg_historico_historial_legajos_update
BEFORE UPDATE ON historial_legajos FOR EACH ROW
BEGIN
    INSERT INTO historico_historial_legajos (id_historial, id_legajo,
        accion, detalle, activo, usuario_accion, tipo_cambio)
    VALUES (OLD.id_historial, OLD.id_legajo, OLD.accion, OLD.detalle,
        OLD.activo, USER(), 'UPDATE');
END//

-- sumarios INSERT
CREATE TRIGGER trg_historico_sumarios_insert
AFTER INSERT ON sumarios FOR EACH ROW
BEGIN
    INSERT INTO historico_sumarios (id_sumario, id_legajo, detalle, activo,
        usuario_accion, tipo_cambio)
    VALUES (NEW.id_sumario, NEW.id_legajo, NEW.detalle, NEW.activo,
        USER(), 'INSERT');
END//

-- sumarios UPDATE
CREATE TRIGGER trg_historico_sumarios_update
BEFORE UPDATE ON sumarios FOR EACH ROW
BEGIN
    INSERT INTO historico_sumarios (id_sumario, id_legajo, detalle, activo,
        usuario_accion, tipo_cambio)
    VALUES (OLD.id_sumario, OLD.id_legajo, OLD.detalle, OLD.activo,
        USER(), 'UPDATE');
END//

-- documentos INSERT
CREATE TRIGGER trg_historico_documentos_insert
AFTER INSERT ON documentos FOR EACH ROW
BEGIN
    INSERT INTO historico_documentos (id_documento, id_persona, tipo_doc,
        nombre_archivo, ruta_archivo, activo, usuario_accion, tipo_cambio)
    VALUES (NEW.id_documento, NEW.id_persona, NEW.tipo_doc,
        NEW.nombre_archivo, NEW.ruta_archivo, NEW.activo, USER(), 'INSERT');
END//

-- documentos UPDATE
CREATE TRIGGER trg_historico_documentos_update
BEFORE UPDATE ON documentos FOR EACH ROW
BEGIN
    INSERT INTO historico_documentos (id_documento, id_persona, tipo_doc,
        nombre_archivo, ruta_archivo, activo, usuario_accion, tipo_cambio)
    VALUES (OLD.id_documento, OLD.id_persona, OLD.tipo_doc,
        OLD.nombre_archivo, OLD.ruta_archivo, OLD.activo, USER(), 'UPDATE');
END//

-- usuario INSERT
CREATE TRIGGER trg_historico_usuario_insert
AFTER INSERT ON usuario FOR EACH ROW
BEGIN
    INSERT INTO historico_usuario (id_usuario, id_legajo, usuario, tipo,
        activo, fecha_creacion, ultimo_login, usuario_accion, tipo_cambio)
    VALUES (NEW.id_usuario, NEW.id_legajo, NEW.usuario, NEW.tipo,
        NEW.activo, DATE(NEW.fecha_creacion), NEW.ultimo_login,
        USER(), 'INSERT');
END//

-- usuario UPDATE
CREATE TRIGGER trg_historico_usuario_update
BEFORE UPDATE ON usuario FOR EACH ROW
BEGIN
    INSERT INTO historico_usuario (id_usuario, id_legajo, usuario, tipo,
        activo, fecha_creacion, ultimo_login, usuario_accion, tipo_cambio)
    VALUES (OLD.id_usuario, OLD.id_legajo, OLD.usuario, OLD.tipo,
        OLD.activo, DATE(OLD.fecha_creacion), OLD.ultimo_login,
        USER(), 'UPDATE');
END//

-- ============================================================
-- GRUPO 2: BAJA LOGICA TOTAL
-- Cuando legajo pasa a de_baja: desactiva TODO lo relacionado
-- incluyendo sesiones activas del usuario
-- ============================================================

CREATE TRIGGER tgr_borrado_logico_total
AFTER UPDATE ON legajos FOR EACH ROW
BEGIN
    IF NEW.estado = 'de_baja' AND OLD.estado != 'de_baja' THEN
        -- Desactiva usuario y cierra sesiones
        UPDATE usuario SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE sesiones_activas SET activa = 0
        WHERE id_usuario = (SELECT id_usuario FROM usuario WHERE id_legajo = NEW.id_legajo LIMIT 1);
        -- Desactiva tablas relacionadas a la persona
        UPDATE titulos SET activo = 0 WHERE id_persona = NEW.id_persona;
        UPDATE cursos SET activo = 0 WHERE id_persona = NEW.id_persona;
        UPDATE idiomas SET activo = 0 WHERE id_persona = NEW.id_persona;
        UPDATE familiar SET activo = 0 WHERE id_persona = NEW.id_persona;
        UPDATE antecedente_laboral SET activo = 0 WHERE id_persona = NEW.id_persona;
        UPDATE documentos SET activo = 0 WHERE id_persona = NEW.id_persona;
        -- Desactiva historial y sumarios del legajo
        UPDATE historial_legajos SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        UPDATE sumarios SET activo = 0 WHERE id_legajo = NEW.id_legajo;
        -- Registra en log
        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
            accion, tabla_afectada, id_registro, resultado, detalle)
        SELECT u.id_usuario, u.usuario, u.tipo,
            'BAJA_LOGICA_TOTAL', 'legajos', NEW.id_legajo, 'exitoso',
            CONCAT('Baja logica total del legajo ', NEW.id_legajo,
                   '. Todos los registros relacionados fueron desactivados.')
        FROM usuario u WHERE u.id_legajo = NEW.id_legajo LIMIT 1;
    END IF;
    -- Reactivacion: vuelve a activar todo
    IF NEW.estado = 'activo' AND OLD.estado = 'de_baja' THEN
        UPDATE usuario SET activo = 1 WHERE id_legajo = NEW.id_legajo;
        UPDATE titulos SET activo = 1 WHERE id_persona = NEW.id_persona;
        UPDATE cursos SET activo = 1 WHERE id_persona = NEW.id_persona;
        UPDATE idiomas SET activo = 1 WHERE id_persona = NEW.id_persona;
        UPDATE familiar SET activo = 1 WHERE id_persona = NEW.id_persona;
        UPDATE antecedente_laboral SET activo = 1 WHERE id_persona = NEW.id_persona;
        UPDATE documentos SET activo = 1 WHERE id_persona = NEW.id_persona;
    END IF;
END//

-- ============================================================
-- GRUPO 3: CREACION AUTOMATICA DE USUARIO AL CREAR LEGAJO
-- usuario = CONVERT(id_legajo, CHAR)
-- pass    = SHA2(dni_persona, 256)
-- tipo    = 'empleado' (el admin puede cambiar el tipo despues)
-- ============================================================

CREATE TRIGGER trg_crear_usuario_al_crear_legajo
AFTER INSERT ON legajos FOR EACH ROW
BEGIN
    DECLARE v_dni CHAR(8);
    -- Solo crear usuario si no existe ya uno para este legajo
    IF NOT EXISTS (SELECT 1 FROM usuario WHERE id_legajo = NEW.id_legajo) THEN
        SELECT dni INTO v_dni FROM personas WHERE id_persona = NEW.id_persona;
        INSERT INTO usuario (usuario, pass, tipo, id_legajo, primer_ingreso, activo)
        VALUES (
            CONVERT(NEW.id_legajo, CHAR),
            SHA2(v_dni, 256),
            'empleado',
            NEW.id_legajo,
            1,
            1
        );
    END IF;
END//

-- ============================================================
-- GRUPO 4: EVENTOS — registra ip, usuario, tabla, horario
-- ============================================================

-- Registra login exitoso: guarda IP, usuario, tipo, horario
CREATE TRIGGER trg_evento_login_update
AFTER UPDATE ON usuario FOR EACH ROW
BEGIN
    IF NOT (NEW.ultimo_login <=> OLD.ultimo_login) THEN
        INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre,
            tipo_usuario, resultado, detalle)
        VALUES ('login', NEW.id_usuario, NEW.usuario, NEW.tipo,
            'exitoso',
            CONCAT('Ultimo login: ', NEW.ultimo_login));
        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
            accion, tabla_afectada, id_registro, resultado, detalle)
        VALUES (NEW.id_usuario, NEW.usuario, NEW.tipo,
            'LOGIN', 'usuario', NEW.id_usuario, 'exitoso',
            CONCAT('Login exitoso. Ultimo login anterior: ', IFNULL(OLD.ultimo_login, 'nunca')));
    END IF;
END//

-- Registra cambio de contraseña
CREATE TRIGGER trg_evento_cambio_pass
AFTER UPDATE ON usuario FOR EACH ROW
BEGIN
    IF NEW.pass != OLD.pass THEN
        INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre,
            tipo_usuario, resultado, detalle)
        VALUES ('cambio_pass', NEW.id_usuario, NEW.usuario, NEW.tipo,
            'exitoso', 'Contrasena actualizada');
        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
            accion, tabla_afectada, id_registro, resultado, detalle)
        VALUES (NEW.id_usuario, NEW.usuario, NEW.tipo,
            'CAMBIO_PASS', 'usuario', NEW.id_usuario, 'exitoso',
            'El usuario cambio su contrasena');
    END IF;
END//

-- Registra baja logica en eventos
CREATE TRIGGER trg_evento_baja_logica
AFTER UPDATE ON legajos FOR EACH ROW
BEGIN
    IF NEW.estado = 'de_baja' AND OLD.estado != 'de_baja' THEN
        INSERT INTO eventos (tipo_evento, tabla_afectada, id_registro, resultado, detalle)
        VALUES ('baja_legajo', 'legajos', NEW.id_legajo, 'exitoso',
            CONCAT('Baja logica del legajo ', NEW.id_legajo));
    END IF;
    IF NEW.estado = 'activo' AND OLD.estado = 'de_baja' THEN
        INSERT INTO eventos (tipo_evento, tabla_afectada, id_registro, resultado, detalle)
        VALUES ('reactivacion_legajo', 'legajos', NEW.id_legajo, 'exitoso',
            CONCAT('Reactivacion del legajo ', NEW.id_legajo));
    END IF;
END//

-- ============================================================
-- GRUPO 5: VALIDACIONES DE DATOS
-- ============================================================

-- Validar edad minima 18 años
CREATE TRIGGER trg_val_edad_minima_personas_insert
BEFORE INSERT ON personas FOR EACH ROW
BEGIN
    IF TIMESTAMPDIFF(YEAR, NEW.fecha_nacimiento, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La persona debe ser mayor de 18 años para ser registrada.';
    END IF;
END//

CREATE TRIGGER trg_val_edad_minima_personas_update
BEFORE UPDATE ON personas FOR EACH ROW
BEGIN
    IF TIMESTAMPDIFF(YEAR, NEW.fecha_nacimiento, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La persona debe ser mayor de 18 años.';
    END IF;
END//

-- Validar fecha de nacimiento no futura
CREATE TRIGGER trg_val_fecha_nac_personas_insert
BEFORE INSERT ON personas FOR EACH ROW
BEGIN
    IF NEW.fecha_nacimiento > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de nacimiento no puede ser una fecha futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_nac_personas_update
BEFORE UPDATE ON personas FOR EACH ROW
BEGIN
    IF NEW.fecha_nacimiento > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de nacimiento no puede ser una fecha futura.';
    END IF;
END//

-- Validar DNI (8 digitos)
CREATE TRIGGER trg_val_dni_personas_insert
BEFORE INSERT ON personas FOR EACH ROW
BEGIN
    IF NEW.dni NOT REGEXP '^[0-9]{8}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El DNI debe contener exactamente 8 digitos numericos.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_personas_update
BEFORE UPDATE ON personas FOR EACH ROW
BEGIN
    IF NEW.dni NOT REGEXP '^[0-9]{8}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El DNI debe contener exactamente 8 digitos numericos.';
    END IF;
END//

-- Validar CUIL (formato XX-XXXXXXXX-X y debe contener el DNI)
CREATE TRIGGER trg_val_cuil_personas_insert
BEFORE INSERT ON personas FOR EACH ROW
BEGIN
    IF CHAR_LENGTH(NEW.cuil) != 13
        OR NEW.cuil NOT REGEXP '^[0-9]{2}-[0-9]{8}-[0-9]{1}$'
        OR LOCATE(NEW.dni, NEW.cuil) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El CUIL debe tener formato XX-XXXXXXXX-X y contener el DNI.';
    END IF;
END//

CREATE TRIGGER trg_val_cuil_personas_update
BEFORE UPDATE ON personas FOR EACH ROW
BEGIN
    IF CHAR_LENGTH(NEW.cuil) != 13
        OR NEW.cuil NOT REGEXP '^[0-9]{2}-[0-9]{8}-[0-9]{1}$'
        OR LOCATE(NEW.dni, NEW.cuil) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El CUIL debe tener formato XX-XXXXXXXX-X y contener el DNI.';
    END IF;
END//

-- Validar email (si se proporciona, debe tener @ y punto)
CREATE TRIGGER trg_val_email_personas_insert
BEFORE INSERT ON personas FOR EACH ROW
BEGIN
    IF NEW.email IS NOT NULL AND NEW.email != ''
        AND (LOCATE('@', NEW.email) = 0 OR LOCATE('.', NEW.email) = 0) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El email ingresado no tiene un formato valido.';
    END IF;
END//

CREATE TRIGGER trg_val_email_personas_update
BEFORE UPDATE ON personas FOR EACH ROW
BEGIN
    IF NEW.email IS NOT NULL AND NEW.email != ''
        AND (LOCATE('@', NEW.email) = 0 OR LOCATE('.', NEW.email) = 0) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El email ingresado no tiene un formato valido.';
    END IF;
END//

-- Validar telefono (solo digitos)
CREATE TRIGGER trg_val_telefono_personas_insert
BEFORE INSERT ON personas FOR EACH ROW
BEGIN
    IF NEW.telefono NOT REGEXP '^[0-9]+$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El telefono debe contener solo digitos numericos.';
    END IF;
END//

CREATE TRIGGER trg_val_telefono_personas_update
BEFORE UPDATE ON personas FOR EACH ROW
BEGIN
    IF NEW.telefono NOT REGEXP '^[0-9]+$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El telefono debe contener solo digitos numericos.';
    END IF;
END//

-- Validar fecha de ingreso no futura
CREATE TRIGGER trg_val_fecha_ingreso_legajos_insert
BEFORE INSERT ON legajos FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso IS NOT NULL AND NEW.fecha_ingreso > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de ingreso no puede ser una fecha futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_ingreso_legajos_update
BEFORE UPDATE ON legajos FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso IS NOT NULL AND NEW.fecha_ingreso > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de ingreso no puede ser una fecha futura.';
    END IF;
END//

-- Validar que fecha_ingreso_administracion >= fecha_ingreso
CREATE TRIGGER trg_val_fechas_legajos_insert
BEFORE INSERT ON legajos FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso IS NOT NULL
        AND NEW.fecha_ingreso_administracion IS NOT NULL
        AND NEW.fecha_ingreso_administracion < NEW.fecha_ingreso THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de ingreso a administracion no puede ser anterior a la fecha de ingreso.';
    END IF;
END//

CREATE TRIGGER trg_val_fechas_legajos_update
BEFORE UPDATE ON legajos FOR EACH ROW
BEGIN
    IF NEW.fecha_ingreso IS NOT NULL
        AND NEW.fecha_ingreso_administracion IS NOT NULL
        AND NEW.fecha_ingreso_administracion < NEW.fecha_ingreso THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de ingreso a administracion no puede ser anterior a la fecha de ingreso.';
    END IF;
END//

-- Validar edad minima al ingreso (18 años al momento del ingreso)
CREATE TRIGGER trg_val_edad_ingreso_legajos_insert
BEFORE INSERT ON legajos FOR EACH ROW
BEGIN
    DECLARE v_fecha_nac DATE;
    SELECT fecha_nacimiento INTO v_fecha_nac FROM personas WHERE id_persona = NEW.id_persona;
    IF NEW.fecha_ingreso IS NOT NULL
        AND TIMESTAMPDIFF(YEAR, v_fecha_nac, NEW.fecha_ingreso) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La persona debe tener al menos 18 años al momento del ingreso.';
    END IF;
END//

CREATE TRIGGER trg_val_edad_ingreso_legajos_update
BEFORE UPDATE ON legajos FOR EACH ROW
BEGIN
    DECLARE v_fecha_nac DATE;
    SELECT fecha_nacimiento INTO v_fecha_nac FROM personas WHERE id_persona = NEW.id_persona;
    IF NEW.fecha_ingreso IS NOT NULL
        AND TIMESTAMPDIFF(YEAR, v_fecha_nac, NEW.fecha_ingreso) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La persona debe tener al menos 18 años al momento del ingreso.';
    END IF;
END//

-- Validar que una persona no tenga dos legajos activos
CREATE TRIGGER trg_val_legajo_activo_unico_insert
BEFORE INSERT ON legajos FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM legajos
    WHERE id_persona = NEW.id_persona AND estado = 'activo';
    IF NEW.estado = 'activo' AND cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La persona ya tiene un legajo activo. No se permiten dos legajos activos simultaneos.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_unico_update
BEFORE UPDATE ON legajos FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM legajos
    WHERE id_persona = NEW.id_persona AND estado = 'activo' AND id_legajo != NEW.id_legajo;
    IF NEW.estado = 'activo' AND cantidad > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La persona ya tiene otro legajo activo. No se permiten dos legajos activos simultaneos.';
    END IF;
END//

-- Validar que no se cambie la persona de un legajo
CREATE TRIGGER trg_val_id_persona_legajos_update
BEFORE UPDATE ON legajos FOR EACH ROW
BEGIN
    IF NEW.id_persona != OLD.id_persona THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se puede cambiar la persona asociada a un legajo existente.';
    END IF;
END//

-- Validar que no se cree usuario para legajo de baja
CREATE TRIGGER trg_val_legajo_activo_usuario_insert
BEFORE INSERT ON usuario FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_legajo = NEW.id_legajo;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se puede crear un usuario para un legajo dado de baja.';
    END IF;
END//

-- Validar reactivacion de usuario con legajo de baja
CREATE TRIGGER trg_val_reactivacion_usuario_update
BEFORE UPDATE ON usuario FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos WHERE id_legajo = NEW.id_legajo;
    IF NEW.activo = 1 AND OLD.activo = 0 AND estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se puede reactivar un usuario cuyo legajo esta dado de baja.';
    END IF;
END//

-- Validar fechas en titulos
CREATE TRIGGER trg_val_fechas_titulos_insert
BEFORE INSERT ON titulos FOR EACH ROW
BEGIN
    IF NEW.fecha_inicio IS NOT NULL AND NEW.fecha_fin IS NOT NULL
        AND NEW.fecha_fin < NEW.fecha_inicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de fin del titulo no puede ser anterior a la fecha de inicio.';
    END IF;
END//

CREATE TRIGGER trg_val_fechas_titulos_update
BEFORE UPDATE ON titulos FOR EACH ROW
BEGIN
    IF NEW.fecha_inicio IS NOT NULL AND NEW.fecha_fin IS NOT NULL
        AND NEW.fecha_fin < NEW.fecha_inicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de fin del titulo no puede ser anterior a la fecha de inicio.';
    END IF;
END//

-- Validar que legajo este activo antes de agregar titulo
CREATE TRIGGER trg_val_legajo_activo_titulos_insert
BEFORE INSERT ON titulos FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos
    WHERE id_persona = NEW.id_persona ORDER BY id_legajo DESC LIMIT 1;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se pueden agregar titulos a una persona con legajo dado de baja.';
    END IF;
END//

-- Validar horas de cursos
CREATE TRIGGER trg_val_horas_cursos_insert
BEFORE INSERT ON cursos FOR EACH ROW
BEGIN
    IF NEW.horas IS NOT NULL AND NEW.horas <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: Las horas del curso deben ser un valor positivo mayor a cero.';
    END IF;
END//

CREATE TRIGGER trg_val_horas_cursos_update
BEFORE UPDATE ON cursos FOR EACH ROW
BEGIN
    IF NEW.horas IS NOT NULL AND NEW.horas <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: Las horas del curso deben ser un valor positivo mayor a cero.';
    END IF;
END//

-- Validar antecedentes laborales: fecha_fin >= fecha_inicio
CREATE TRIGGER trg_val_fechas_antecedente_insert
BEFORE INSERT ON antecedente_laboral FOR EACH ROW
BEGIN
    IF NEW.fecha_fin IS NOT NULL AND NEW.fecha_fin < NEW.fecha_inicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de fin del antecedente laboral no puede ser anterior a la fecha de inicio.';
    END IF;
END//

CREATE TRIGGER trg_val_fechas_antecedente_update
BEFORE UPDATE ON antecedente_laboral FOR EACH ROW
BEGIN
    IF NEW.fecha_fin IS NOT NULL AND NEW.fecha_fin < NEW.fecha_inicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de fin del antecedente laboral no puede ser anterior a la fecha de inicio.';
    END IF;
END//

-- ============================================================
-- GRUPO 6: SINCRONIZACION Y REGLAS DE NEGOCIO
-- ============================================================

-- Marca primer_ingreso = 0 despues del primer UPDATE activo
CREATE TRIGGER trg_primer_ingreso_usuario
AFTER UPDATE ON usuario FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 AND NEW.activo = 1 AND NEW.ultimo_login IS NOT NULL THEN
        UPDATE usuario SET primer_ingreso = 0 WHERE id_usuario = NEW.id_usuario;
    END IF;
END//

-- Primer ingreso en tablas secundarias
CREATE TRIGGER trg_primer_ingreso_titulos
AFTER UPDATE ON titulos FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE titulos SET primer_ingreso = 0 WHERE id_titulo = NEW.id_titulo;
    END IF;
END//

CREATE TRIGGER trg_primer_ingreso_cursos
AFTER UPDATE ON cursos FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE cursos SET primer_ingreso = 0 WHERE id_curso = NEW.id_curso;
    END IF;
END//

CREATE TRIGGER trg_primer_ingreso_idiomas
AFTER UPDATE ON idiomas FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE idiomas SET primer_ingreso = 0 WHERE id_idioma = NEW.id_idioma;
    END IF;
END//

CREATE TRIGGER trg_primer_ingreso_familiar
AFTER UPDATE ON familiar FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE familiar SET primer_ingreso = 0 WHERE id_familiar = NEW.id_familiar;
    END IF;
END//

CREATE TRIGGER trg_primer_ingreso_antecedente
AFTER UPDATE ON antecedente_laboral FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE antecedente_laboral SET primer_ingreso = 0 WHERE id_antecedente = NEW.id_antecedente;
    END IF;
END//

CREATE TRIGGER trg_primer_ingreso_documentos
AFTER UPDATE ON documentos FOR EACH ROW
BEGIN
    IF OLD.primer_ingreso = 1 THEN
        UPDATE documentos SET primer_ingreso = 0 WHERE id_documento = NEW.id_documento;
    END IF;
END//

-- Bloquea quedar sin administrador activo
CREATE TRIGGER trg_bloqueo_admin_unico_update
BEFORE UPDATE ON usuario FOR EACH ROW
BEGIN
    DECLARE cant_admins INT;
    IF OLD.tipo = 'administrador' AND (NEW.activo = 0 OR NEW.tipo != 'administrador') THEN
        SELECT COUNT(*) INTO cant_admins FROM usuario
        WHERE tipo = 'administrador' AND activo = 1 AND id_usuario != OLD.id_usuario;
        IF cant_admins = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: No se puede desactivar o cambiar el tipo del unico administrador activo del sistema.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_bloqueo_admin_unico_delete
BEFORE DELETE ON usuario FOR EACH ROW
BEGIN
    DECLARE cant_admins INT;
    IF OLD.tipo = 'administrador' THEN
        SELECT COUNT(*) INTO cant_admins FROM usuario
        WHERE tipo = 'administrador' AND activo = 1 AND id_usuario != OLD.id_usuario;
        IF cant_admins = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: No se puede eliminar el unico administrador activo del sistema.';
        END IF;
    END IF;
END//

-- Sincroniza cantidad_hijos
CREATE TRIGGER trg_sync_cantidad_hijos_insert
AFTER INSERT ON familiar FOR EACH ROW
BEGIN
    IF NEW.relacion_empleado = 'hijo/a' AND NEW.activo = 1 THEN
        UPDATE personas SET cantidad_hijos = cantidad_hijos + 1
        WHERE id_persona = NEW.id_persona;
    END IF;
END//

CREATE TRIGGER trg_sync_cantidad_hijos_delete
BEFORE DELETE ON familiar FOR EACH ROW
BEGIN
    IF OLD.relacion_empleado = 'hijo/a' AND OLD.activo = 1 THEN
        UPDATE personas SET cantidad_hijos = GREATEST(cantidad_hijos - 1, 0)
        WHERE id_persona = OLD.id_persona;
    END IF;
END//

-- Sincroniza estado_civil a 'viudo/a' si fallece el conyuge
CREATE TRIGGER trg_sync_estado_civil_conyuge
AFTER UPDATE ON familiar FOR EACH ROW
BEGIN
    IF NEW.relacion_empleado = 'conyuge' AND NEW.activo = 0 AND OLD.activo = 1 THEN
        UPDATE personas SET estado_civil = 'viudo/a' WHERE id_persona = NEW.id_persona;
    END IF;
END//

-- Valida historial con sumario
CREATE TRIGGER trg_validar_historial_sumario
BEFORE INSERT ON historial_legajos FOR EACH ROW
BEGIN
    IF NEW.accion = 'sumario' THEN
        IF NOT EXISTS (SELECT 1 FROM sumarios WHERE id_legajo = NEW.id_legajo AND activo = 1) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: No existe un sumario activo para este legajo. Cree el sumario primero.';
        END IF;
    END IF;
END//

-- Valida estado del legajo antes de historial
CREATE TRIGGER trg_validar_estado_legajo_historial
BEFORE INSERT ON historial_legajos FOR EACH ROW
BEGIN
    DECLARE v_estado VARCHAR(20);
    SELECT estado INTO v_estado FROM legajos WHERE id_legajo = NEW.id_legajo;
    IF v_estado = 'de_baja' AND NEW.accion NOT IN ('jubilacion','renuncia','difunto','incapacidad') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se puede registrar historial en un legajo dado de baja.';
    END IF;
END//

-- Validaciones familiares
CREATE TRIGGER trg_val_dni_familiar_insert
BEFORE INSERT ON familiar FOR EACH ROW
BEGIN
    DECLARE v_dni_empleado CHAR(8);
    SELECT p.dni INTO v_dni_empleado FROM personas p
    INNER JOIN legajos l ON l.id_persona = p.id_persona
    WHERE p.id_persona = NEW.id_persona LIMIT 1;
    IF NEW.dni_familiar IS NOT NULL AND NEW.dni_familiar = v_dni_empleado THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El DNI del familiar no puede ser el mismo que el del empleado.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_familiar_update
BEFORE UPDATE ON familiar FOR EACH ROW
BEGIN
    DECLARE v_dni_empleado CHAR(8);
    SELECT p.dni INTO v_dni_empleado FROM personas p
    WHERE p.id_persona = NEW.id_persona LIMIT 1;
    IF NEW.dni_familiar IS NOT NULL AND NEW.dni_familiar = v_dni_empleado THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El DNI del familiar no puede ser el mismo que el del empleado.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_digitos_familiar_insert
BEFORE INSERT ON familiar FOR EACH ROW
BEGIN
    IF NEW.dni_familiar IS NOT NULL AND NEW.dni_familiar NOT REGEXP '^[0-9]{8}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El DNI del familiar debe contener exactamente 8 digitos.';
    END IF;
END//

CREATE TRIGGER trg_val_dni_digitos_familiar_update
BEFORE UPDATE ON familiar FOR EACH ROW
BEGIN
    IF NEW.dni_familiar IS NOT NULL AND NEW.dni_familiar NOT REGEXP '^[0-9]{8}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El DNI del familiar debe contener exactamente 8 digitos.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_nac_familiar_insert
BEFORE INSERT ON familiar FOR EACH ROW
BEGIN
    IF NEW.fecha_nacimiento_familiar IS NOT NULL AND NEW.fecha_nacimiento_familiar > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de nacimiento del familiar no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fecha_nac_familiar_update
BEFORE UPDATE ON familiar FOR EACH ROW
BEGIN
    IF NEW.fecha_nacimiento_familiar IS NOT NULL AND NEW.fecha_nacimiento_familiar > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de nacimiento del familiar no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_conyuge_unico_insert
BEFORE INSERT ON familiar FOR EACH ROW
BEGIN
    DECLARE cant INT;
    IF NEW.relacion_empleado = 'conyuge' AND NEW.activo = 1 THEN
        SELECT COUNT(*) INTO cant FROM familiar
        WHERE id_persona = NEW.id_persona AND relacion_empleado = 'conyuge' AND activo = 1;
        IF cant > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: Ya existe un conyuge activo para este empleado.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_conyuge_unico_update
BEFORE UPDATE ON familiar FOR EACH ROW
BEGIN
    DECLARE cant INT;
    IF NEW.relacion_empleado = 'conyuge' AND NEW.activo = 1 THEN
        SELECT COUNT(*) INTO cant FROM familiar
        WHERE id_persona = NEW.id_persona AND relacion_empleado = 'conyuge'
        AND activo = 1 AND id_familiar != NEW.id_familiar;
        IF cant > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: Ya existe un conyuge activo para este empleado.';
        END IF;
    END IF;
END//

-- Validaciones de legajo activo para tablas secundarias
CREATE TRIGGER trg_val_legajo_activo_cursos_insert
BEFORE INSERT ON cursos FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos
    WHERE id_persona = NEW.id_persona ORDER BY id_legajo DESC LIMIT 1;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se pueden agregar cursos a una persona con legajo dado de baja.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_idiomas_insert
BEFORE INSERT ON idiomas FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos
    WHERE id_persona = NEW.id_persona ORDER BY id_legajo DESC LIMIT 1;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se pueden agregar idiomas a una persona con legajo dado de baja.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_familiar_insert
BEFORE INSERT ON familiar FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos
    WHERE id_persona = NEW.id_persona ORDER BY id_legajo DESC LIMIT 1;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se pueden agregar familiares a una persona con legajo dado de baja.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_antecedente_insert
BEFORE INSERT ON antecedente_laboral FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos
    WHERE id_persona = NEW.id_persona ORDER BY id_legajo DESC LIMIT 1;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se pueden agregar antecedentes a una persona con legajo dado de baja.';
    END IF;
END//

CREATE TRIGGER trg_val_legajo_activo_documentos_insert
BEFORE INSERT ON documentos FOR EACH ROW
BEGIN
    DECLARE estado_legajo VARCHAR(20);
    SELECT estado INTO estado_legajo FROM legajos
    WHERE id_persona = NEW.id_persona ORDER BY id_legajo DESC LIMIT 1;
    IF estado_legajo = 'de_baja' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No se pueden agregar documentos a una persona con legajo dado de baja.';
    END IF;
END//

-- Validaciones de titulos
CREATE TRIGGER trg_val_dup_titulo_insert
BEFORE INSERT ON titulos FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM titulos WHERE id_persona = NEW.id_persona
        AND nombre_titulo = NEW.nombre_titulo AND activo = 1) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: Ya existe ese titulo activo para este empleado.';
    END IF;
END//

-- Validaciones de idiomas
CREATE TRIGGER trg_val_dup_idioma_insert
BEFORE INSERT ON idiomas FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM idiomas WHERE id_persona = NEW.id_persona
        AND nombre = NEW.nombre AND activo = 1) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: Ya existe ese idioma activo para este empleado.';
    END IF;
END//

-- Validaciones de fechas de cursos
CREATE TRIGGER trg_val_fechas_cursos_insert
BEFORE INSERT ON cursos FOR EACH ROW
BEGIN
    IF NEW.fecha_inicio IS NOT NULL AND NEW.fecha_inicio > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de inicio del curso no puede ser futura.';
    END IF;
END//

CREATE TRIGGER trg_val_fechas_cursos_update
BEFORE UPDATE ON cursos FOR EACH ROW
BEGIN
    IF NEW.fecha_inicio IS NOT NULL AND NEW.fecha_inicio > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: La fecha de inicio del curso no puede ser futura.';
    END IF;
END//

-- Validacion foto de perfil unica
CREATE TRIGGER trg_val_foto_perfil_unica_insert
BEFORE INSERT ON documentos FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    IF NEW.tipo_doc = 'FOTO_PERFIL' AND NEW.activo = 1 THEN
        SELECT COUNT(*) INTO cantidad FROM documentos
        WHERE id_persona = NEW.id_persona AND tipo_doc = 'FOTO_PERFIL' AND activo = 1;
        IF cantidad > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: Ya existe una foto de perfil activa. Desactive la anterior primero.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_foto_perfil_unica_update
BEFORE UPDATE ON documentos FOR EACH ROW
BEGIN
    DECLARE cantidad INT;
    IF NEW.tipo_doc = 'FOTO_PERFIL' AND NEW.activo = 1 THEN
        SELECT COUNT(*) INTO cantidad FROM documentos
        WHERE id_persona = NEW.id_persona AND tipo_doc = 'FOTO_PERFIL'
        AND activo = 1 AND id_documento != NEW.id_documento;
        IF cantidad > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: Ya existe una foto de perfil activa para este empleado.';
        END IF;
    END IF;
END//

-- Validacion hash duplicado en documentos
CREATE TRIGGER trg_val_hash_dup_documentos_insert
BEFORE INSERT ON documentos FOR EACH ROW
BEGIN
    IF NEW.hash_archivo IS NOT NULL THEN
        IF EXISTS (SELECT 1 FROM documentos WHERE id_persona = NEW.id_persona
            AND hash_archivo = NEW.hash_archivo AND activo = 1) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: Ya existe un documento identico (mismo hash) activo para este empleado.';
        END IF;
    END IF;
END//

-- Validacion extension/mime de documentos
CREATE TRIGGER trg_val_mime_extension_insert
BEFORE INSERT ON documentos FOR EACH ROW
BEGIN
    IF NEW.mime_type IS NOT NULL AND NEW.extension IS NOT NULL THEN
        IF (NEW.mime_type = 'application/pdf' AND NEW.extension != 'pdf')
        OR (NEW.mime_type LIKE 'image/jpeg' AND NEW.extension NOT IN ('jpg','jpeg'))
        OR (NEW.mime_type = 'image/png' AND NEW.extension != 'png') THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: La extension del archivo no coincide con su tipo MIME.';
        END IF;
    END IF;
END//

CREATE TRIGGER trg_val_mime_extension_update
BEFORE UPDATE ON documentos FOR EACH ROW
BEGIN
    IF NEW.mime_type IS NOT NULL AND NEW.extension IS NOT NULL THEN
        IF (NEW.mime_type = 'application/pdf' AND NEW.extension != 'pdf')
        OR (NEW.mime_type LIKE 'image/jpeg' AND NEW.extension NOT IN ('jpg','jpeg'))
        OR (NEW.mime_type = 'image/png' AND NEW.extension != 'png') THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'ERROR: La extension del archivo no coincide con su tipo MIME.';
        END IF;
    END IF;
END//

DELIMITER ;
