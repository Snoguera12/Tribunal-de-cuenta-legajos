USE torres_corregida1;

-- ============================================================
-- SISTEMA TORRES_CORREGIDA1 — FUNCIONES / STORED PROCEDURES
-- ============================================================
--
-- INDICE COMPLETO DE STORED PROCEDURES:
-- ============================================================
--
-- [SEGURIDAD Y LOGIN]
--   sp_login                        — Login con contador de intentos y bloqueo automatico
--                                     Aviso en intento 3: "quedan X intentos"
--                                     Bloqueo en intento 5: aviso de comunicarse con RRHH
--                                     3 intentos fallidos: espera 1 hora para reiniciar contador
--   sp_cambiar_pass                 — Cambia contraseña validando la actual
--   sp_desbloquear_usuario          — Solo admin: desbloquea un usuario bloqueado
--   sp_solicitar_recuperacion       — Usuario solicita recuperacion de clave a RRHH/admin
--   sp_atender_recuperacion         — Admin/RRHH atiende la solicitud de recuperacion
--
-- [ALTA DE PERSONAL — admin y rrhh]
--   sp_alta_completa                — Crea persona + legajo en una transaccion
--                                     El trigger crea el usuario automaticamente
--                                     (usuario=id_legajo, pass=SHA2(dni))
--   sp_modificar_persona            — Modifica datos de una persona existente
--   sp_modificar_legajo             — Modifica datos de un legajo existente
--   sp_dar_baja_legajo              — Baja logica del legajo con validaciones
--   sp_reactivar_legajo             — Reactiva un legajo dado de baja
--   sp_cambiar_tipo_usuario         — Solo admin: cambia el tipo de un usuario
--                                     (funcionario / rrhh / empleado / administrador)
--
-- [CONSULTA DE DATOS — admin, rrhh, funcionario]
--   sp_listado_empleados            — Lista id_legajo, dni, nombre, apellido, estado,
--                                     genero, categoria, mail, telefono
--   sp_consulta_por_legajo          — Toda la info de las tablas principales por id_legajo
--   sp_consulta_por_dni             — Toda la info de las tablas principales por dni
--
-- [CONSULTA PROPIA — empleado]
--   sp_mi_informacion               — El empleado consulta su propia informacion
--
-- [INSERTAR / MODIFICAR DATOS SECUNDARIOS — admin y rrhh]
--   sp_insertar_titulo              — Agrega titulo a una persona
--   sp_insertar_curso               — Agrega curso a una persona
--   sp_insertar_idioma              — Agrega idioma a una persona
--   sp_insertar_familiar            — Agrega familiar a una persona
--   sp_insertar_antecedente         — Agrega antecedente laboral a una persona
--   sp_insertar_sumario             — Agrega sumario a un legajo
--   sp_insertar_historial           — Agrega historial a un legajo
--
-- [BACKUP]
--   sp_backup_generar_diario        — Genera nombre/comando de mysqldump diario
--   sp_backup_generar_semanal       — Genera nombre/comando de mysqldump semanal
--   sp_backup_listar                — Lista historial de backups
--
-- ============================================================

-- ============================================================
-- LIMPIEZA PREVIA
-- ============================================================
DROP PROCEDURE IF EXISTS sp_login;
DROP PROCEDURE IF EXISTS sp_cambiar_pass;
DROP PROCEDURE IF EXISTS sp_desbloquear_usuario;
DROP PROCEDURE IF EXISTS sp_solicitar_recuperacion;
DROP PROCEDURE IF EXISTS sp_atender_recuperacion;
DROP PROCEDURE IF EXISTS sp_alta_completa;
DROP PROCEDURE IF EXISTS sp_modificar_persona;
DROP PROCEDURE IF EXISTS sp_modificar_legajo;
DROP PROCEDURE IF EXISTS sp_dar_baja_legajo;
DROP PROCEDURE IF EXISTS sp_reactivar_legajo;
DROP PROCEDURE IF EXISTS sp_cambiar_tipo_usuario;
DROP PROCEDURE IF EXISTS sp_listado_empleados;
DROP PROCEDURE IF EXISTS sp_consulta_por_legajo;
DROP PROCEDURE IF EXISTS sp_consulta_por_dni;
DROP PROCEDURE IF EXISTS sp_mi_informacion;
DROP PROCEDURE IF EXISTS sp_insertar_titulo;
DROP PROCEDURE IF EXISTS sp_insertar_curso;
DROP PROCEDURE IF EXISTS sp_insertar_idioma;
DROP PROCEDURE IF EXISTS sp_insertar_familiar;
DROP PROCEDURE IF EXISTS sp_insertar_antecedente;
DROP PROCEDURE IF EXISTS sp_insertar_sumario;
DROP PROCEDURE IF EXISTS sp_insertar_historial;
DROP PROCEDURE IF EXISTS sp_backup_generar_diario;
DROP PROCEDURE IF EXISTS sp_backup_generar_semanal;
DROP PROCEDURE IF EXISTS sp_backup_listar;

DELIMITER //

-- ============================================================
-- LOGIN CON BLOQUEO AUTOMATICO
-- Logica:
--   - Intento 3 fallido: aviso "quedan 2 intentos antes del bloqueo"
--   - Si hay 3 intentos fallidos consecutivos: espera 1 hora para reiniciar
--   - Intento 5 fallido: bloqueo definitivo, avisa comunicarse con RRHH
--   - Bloqueo definitivo requiere sp_desbloquear_usuario (admin)
-- ============================================================
CREATE PROCEDURE sp_login(
    IN  p_usuario   VARCHAR(50),
    IN  p_pass      VARCHAR(255),
    IN  p_ip        VARCHAR(45),
    OUT p_id_usuario INT,
    OUT p_tipo       VARCHAR(20),
    OUT p_id_legajo  INT,
    OUT p_primer_ingreso TINYINT(1),
    OUT p_resultado  VARCHAR(200)
)
sp_login_proc: BEGIN
    DECLARE v_id_usuario    INT;
    DECLARE v_pass_db       VARCHAR(255);
    DECLARE v_activo        TINYINT(1);
    DECLARE v_tipo          VARCHAR(20);
    DECLARE v_id_legajo     INT;
    DECLARE v_primer_ingreso TINYINT(1);
    DECLARE v_intentos      INT DEFAULT 0;
    DECLARE v_bloqueado     TINYINT(1) DEFAULT 0;
    DECLARE v_fecha_bloqueo DATETIME;
    DECLARE v_ultimo_intento DATETIME;

    -- Inicializar OUT
    SET p_id_usuario = NULL;
    SET p_tipo = NULL;
    SET p_id_legajo = NULL;
    SET p_primer_ingreso = NULL;

    -- Obtener datos del usuario
    SELECT id_usuario, pass, activo, tipo, id_legajo, primer_ingreso
    INTO v_id_usuario, v_pass_db, v_activo, v_tipo, v_id_legajo, v_primer_ingreso
    FROM usuario WHERE usuario = p_usuario;

    -- Usuario no existe
    IF v_id_usuario IS NULL THEN
        SET p_resultado = 'ERROR: Usuario no encontrado. Verifique el nombre de usuario.';
        INSERT INTO log_sistema (usuario_nombre, ip, accion, resultado, detalle)
        VALUES (p_usuario, p_ip, 'LOGIN', 'fallido', 'Usuario no existe');
        LEAVE sp_login_proc;
    END IF;

    -- Verificar bloqueo definitivo
    SELECT intentos_fallidos, bloqueado, fecha_bloqueo, fecha_hora
    INTO v_intentos, v_bloqueado, v_fecha_bloqueo, v_ultimo_intento
    FROM intentos_login
    WHERE usuario = p_usuario
    ORDER BY fecha_hora DESC LIMIT 1;

    IF v_bloqueado = 1 THEN
        SET p_resultado = 'BLOQUEADO: Su usuario ha sido bloqueado por multiples intentos fallidos. Debe comunicarse con la oficina de RRHH para recuperar el acceso.';
        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
            accion, resultado, detalle)
        VALUES (v_id_usuario, p_usuario, v_tipo, p_ip, 'LOGIN', 'bloqueado',
            'Intento de login con usuario bloqueado');
        LEAVE sp_login_proc;
    END IF;

    -- Verificar periodo de espera de 1 hora (si hay 3 intentos fallidos recientes)
    IF v_intentos >= 3 AND v_fecha_bloqueo IS NULL THEN
        IF v_ultimo_intento IS NOT NULL
            AND TIMESTAMPDIFF(MINUTE, v_ultimo_intento, NOW()) < 60 THEN
            SET p_resultado = CONCAT('ESPERA: Demasiados intentos fallidos. ',
                'Debe esperar 1 hora desde el ultimo intento. ',
                'Tiempo restante: ',
                60 - TIMESTAMPDIFF(MINUTE, v_ultimo_intento, NOW()),
                ' minutos.');
            LEAVE sp_login_proc;
        ELSE
            -- Paso 1 hora: reiniciar contador
            UPDATE intentos_login
            SET intentos_fallidos = 0, fecha_hora = NOW()
            WHERE usuario = p_usuario ORDER BY fecha_hora DESC LIMIT 1;
            SET v_intentos = 0;
        END IF;
    END IF;

    -- Verificar si usuario esta inactivo (sin bloqueo)
    IF v_activo = 0 THEN
        SET p_resultado = 'ERROR: Su usuario se encuentra inactivo. Comuniquese con RRHH.';
        LEAVE sp_login_proc;
    END IF;

    -- Verificar contraseña
    IF v_pass_db != SHA2(p_pass, 256) THEN
        SET v_intentos = v_intentos + 1;

        -- Guardar intento fallido
        INSERT INTO intentos_login (usuario, ip, resultado, intentos_fallidos)
        VALUES (p_usuario, p_ip, 'fallido', v_intentos);

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
            accion, resultado, detalle)
        VALUES (v_id_usuario, p_usuario, v_tipo, p_ip, 'LOGIN', 'fallido',
            CONCAT('Intento fallido nro ', v_intentos));

        -- Aviso en intento 3
        IF v_intentos = 3 THEN
            SET p_resultado = CONCAT('ADVERTENCIA: Contrasena incorrecta. ',
                'Este es el intento numero 3. ',
                'Quedan solo 2 intentos mas antes del bloqueo definitivo. ',
                'Si olvido su contrasena, puede intentar nuevamente en 1 hora ',
                'o comunicarse con RRHH.');
        -- Bloqueo en intento 5
        ELSEIF v_intentos >= 5 THEN
            UPDATE intentos_login
            SET bloqueado = 1, fecha_bloqueo = NOW()
            WHERE usuario = p_usuario ORDER BY id_intento DESC LIMIT 1;
            UPDATE usuario SET activo = 0 WHERE id_usuario = v_id_usuario;
            INSERT INTO incidentes (tipo_incidente, nivel_severidad,
                id_usuario, usuario_nombre, ip, descripcion)
            VALUES ('multiples_intentos_fallidos', 'alto',
                v_id_usuario, p_usuario, p_ip,
                CONCAT('Usuario bloqueado automaticamente tras 5 intentos fallidos de login'));
            SET p_resultado = 'BLOQUEADO: Ha superado el limite de 5 intentos fallidos. Su cuenta ha sido bloqueada. Debe comunicarse con la oficina de RRHH para recuperar el acceso.';
        ELSE
            SET p_resultado = CONCAT('ERROR: Contrasena incorrecta. ',
                'Intento ', v_intentos, ' de 5. ',
                'Quedan ', 5 - v_intentos, ' intentos antes del bloqueo.');
        END IF;
        LEAVE sp_login_proc;
    END IF;

    -- Login exitoso
    UPDATE usuario SET ultimo_login = NOW() WHERE id_usuario = v_id_usuario;

    -- Reiniciar contador de intentos
    UPDATE intentos_login SET intentos_fallidos = 0
    WHERE usuario = p_usuario ORDER BY fecha_hora DESC LIMIT 1;

    SET p_id_usuario    = v_id_usuario;
    SET p_tipo          = v_tipo;
    SET p_id_legajo     = v_id_legajo;
    SET p_primer_ingreso = v_primer_ingreso;
    SET p_resultado     = 'OK';

    INSERT INTO intentos_login (usuario, ip, resultado, intentos_fallidos)
    VALUES (p_usuario, p_ip, 'exitoso', 0);

    -- El trigger trg_evento_login_update registra en log y eventos
END//

-- ============================================================
-- CAMBIAR CONTRASEÑA
-- ============================================================
CREATE PROCEDURE sp_cambiar_pass(
    IN  p_id_usuario  INT,
    IN  p_pass_actual VARCHAR(255),
    IN  p_pass_nueva  VARCHAR(255),
    IN  p_ip          VARCHAR(45),
    OUT p_resultado   VARCHAR(100)
)
BEGIN
    DECLARE v_pass_db    VARCHAR(255);
    DECLARE v_activo     TINYINT(1);
    DECLARE v_usuario    VARCHAR(50);
    DECLARE v_tipo       VARCHAR(20);

    SELECT pass, activo, usuario, tipo
    INTO v_pass_db, v_activo, v_usuario, v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario;

    IF v_pass_db IS NULL THEN
        SET p_resultado = 'ERROR: Usuario no encontrado.';
    ELSEIF v_activo = 0 THEN
        SET p_resultado = 'ERROR: El usuario se encuentra inactivo.';
    ELSEIF v_pass_db != SHA2(p_pass_actual, 256) THEN
        SET p_resultado = 'ERROR: La contrasena actual ingresada es incorrecta.';
        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
            accion, resultado, detalle)
        VALUES (p_id_usuario, v_usuario, v_tipo, p_ip,
            'CAMBIO_PASS', 'fallido', 'Contrasena actual incorrecta');
    ELSEIF SHA2(p_pass_nueva, 256) = v_pass_db THEN
        SET p_resultado = 'ERROR: La nueva contrasena no puede ser igual a la actual.';
    ELSEIF CHAR_LENGTH(p_pass_nueva) < 6 THEN
        SET p_resultado = 'ERROR: La nueva contrasena debe tener al menos 6 caracteres.';
    ELSE
        UPDATE usuario
        SET pass = SHA2(p_pass_nueva, 256), primer_ingreso = 0
        WHERE id_usuario = p_id_usuario;
        SET p_resultado = 'OK';
        -- El trigger trg_evento_cambio_pass registra en log y eventos
    END IF;
END//

-- ============================================================
-- DESBLOQUEAR USUARIO (solo administrador)
-- ============================================================
CREATE PROCEDURE sp_desbloquear_usuario(
    IN  p_id_admin    INT,
    IN  p_id_usuario  INT,
    IN  p_ip          VARCHAR(45),
    OUT p_resultado   VARCHAR(100)
)
sp_desbloq: BEGIN
    DECLARE v_tipo_admin VARCHAR(20);
    DECLARE v_usuario    VARCHAR(50);

    SELECT tipo INTO v_tipo_admin
    FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;

    IF v_tipo_admin != 'administrador' THEN
        SET p_resultado = 'ERROR: Solo el administrador puede desbloquear usuarios.';
        LEAVE sp_desbloq;
    END IF;

    SELECT usuario INTO v_usuario FROM usuario WHERE id_usuario = p_id_usuario;

    UPDATE usuario SET activo = 1 WHERE id_usuario = p_id_usuario;
    UPDATE intentos_login
    SET bloqueado = 0, intentos_fallidos = 0, fecha_desbloqueo = NOW()
    WHERE usuario = v_usuario AND bloqueado = 1;

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
        accion, tabla_afectada, id_registro, resultado, detalle)
    SELECT p_id_admin, u.usuario, 'administrador', p_ip,
        'DESBLOQUEO_USUARIO', 'usuario', p_id_usuario, 'exitoso',
        CONCAT('Se desbloqueo al usuario: ', v_usuario)
    FROM usuario u WHERE u.id_usuario = p_id_admin;

    INSERT INTO eventos (tipo_evento, id_usuario, usuario_nombre, tipo_usuario,
        ip, tabla_afectada, id_registro, resultado)
    SELECT 'desbloqueo_usuario', p_id_admin, u.usuario, 'administrador',
        p_ip, 'usuario', p_id_usuario, 'exitoso'
    FROM usuario u WHERE u.id_usuario = p_id_admin;

    SET p_resultado = 'OK';
END//

-- ============================================================
-- SOLICITAR RECUPERACION DE CLAVE
-- ============================================================
CREATE PROCEDURE sp_solicitar_recuperacion(
    IN  p_usuario   VARCHAR(50),
    OUT p_resultado VARCHAR(100)
)
BEGIN
    DECLARE v_id_usuario INT;
    SELECT id_usuario INTO v_id_usuario FROM usuario WHERE usuario = p_usuario;
    IF v_id_usuario IS NULL THEN
        SET p_resultado = 'ERROR: Usuario no encontrado.';
    ELSE
        INSERT INTO solicitudes_recuperacion (id_usuario, usuario)
        VALUES (v_id_usuario, p_usuario);
        SET p_resultado = 'OK: Solicitud registrada. Comuniquese con la oficina de RRHH para continuar.';
    END IF;
END//

-- ============================================================
-- ATENDER RECUPERACION (admin o rrhh)
-- ============================================================
CREATE PROCEDURE sp_atender_recuperacion(
    IN  p_id_admin    INT,
    IN  p_id_solicitud INT,
    IN  p_accion      ENUM('atendida','rechazada'),
    IN  p_ip          VARCHAR(45),
    OUT p_pass_nueva  VARCHAR(50),
    OUT p_resultado   VARCHAR(100)
)
sp_atender: BEGIN
    DECLARE v_tipo_admin VARCHAR(20);
    DECLARE v_id_usuario INT;
    DECLARE v_usuario    VARCHAR(50);
    DECLARE v_apellido   VARCHAR(50);
    DECLARE v_id_legajo  INT;
    DECLARE v_error      VARCHAR(200);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error = MESSAGE_TEXT;
        ROLLBACK;
        SET p_resultado = CONCAT('ERROR: ', v_error);
        SET p_pass_nueva = NULL;
    END;

    SELECT tipo INTO v_tipo_admin
    FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;

    IF v_tipo_admin NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo el administrador o RRHH puede atender recuperaciones.';
        LEAVE sp_atender;
    END IF;

    SELECT id_usuario, usuario
    INTO v_id_usuario, v_usuario
    FROM solicitudes_recuperacion
    WHERE id_solicitud = p_id_solicitud AND estado = 'pendiente';

    IF v_id_usuario IS NULL THEN
        SET p_resultado = 'ERROR: Solicitud no encontrada o ya atendida.';
        LEAVE sp_atender;
    END IF;

    START TRANSACTION;

    IF p_accion = 'rechazada' THEN
        UPDATE solicitudes_recuperacion
        SET estado = 'rechazada', id_admin_atiende = p_id_admin, fecha_atencion = NOW()
        WHERE id_solicitud = p_id_solicitud;
        COMMIT;
        SET p_pass_nueva = NULL;
        SET p_resultado = 'OK: Solicitud rechazada.';
    ELSE
        -- Generar nueva clave temporal: apellido + numero aleatorio
        SELECT l.id_legajo INTO v_id_legajo FROM usuario u
        INNER JOIN legajos l ON l.id_legajo = u.id_legajo
        WHERE u.id_usuario = v_id_usuario;

        SELECT p.apellido INTO v_apellido FROM personas p
        INNER JOIN legajos l ON l.id_persona = p.id_persona
        WHERE l.id_legajo = v_id_legajo;

        SET p_pass_nueva = CONCAT(
            UPPER(LEFT(v_apellido, 1)),
            LOWER(SUBSTRING(v_apellido, 2, 4)),
            FLOOR(RAND() * 90 + 10)
        );

        UPDATE usuario
        SET pass = SHA2(p_pass_nueva, 256), primer_ingreso = 1, activo = 1
        WHERE id_usuario = v_id_usuario;

        UPDATE intentos_login
        SET bloqueado = 0, intentos_fallidos = 0, fecha_desbloqueo = NOW()
        WHERE usuario = v_usuario AND bloqueado = 1;

        UPDATE solicitudes_recuperacion
        SET estado = 'atendida', id_admin_atiende = p_id_admin, fecha_atencion = NOW()
        WHERE id_solicitud = p_id_solicitud;

        INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
            accion, tabla_afectada, id_registro, resultado, detalle)
        SELECT p_id_admin, u.usuario, v_tipo_admin, p_ip,
            'RECUPERACION_PASS', 'usuario', v_id_usuario, 'exitoso',
            CONCAT('Contrasena temporal generada para: ', v_usuario)
        FROM usuario u WHERE u.id_usuario = p_id_admin;

        COMMIT;
        SET p_resultado = 'OK';
    END IF;
END//

-- ============================================================
-- ALTA COMPLETA DE PERSONAL (admin y rrhh)
-- Crea persona + legajo en una transaccion.
-- El trigger trg_crear_usuario_al_crear_legajo crea el usuario
-- automaticamente: usuario=id_legajo, pass=SHA2(dni)
-- ============================================================
CREATE PROCEDURE sp_alta_completa(
    IN  p_id_usuario_op  INT,
    -- Datos persona
    IN  p_dni            CHAR(8),
    IN  p_apellido       VARCHAR(50),
    IN  p_nombre         VARCHAR(50),
    IN  p_genero         ENUM('masculino','femenino','sin_determinar'),
    IN  p_fecha_nac      DATE,
    IN  p_estado_civil   ENUM('soltero/a','viudo/a','casado/a','concubinato'),
    IN  p_cantidad_hijos INT,
    IN  p_provincia      VARCHAR(50),
    IN  p_ciudad         VARCHAR(50),
    IN  p_domicilio      VARCHAR(300),
    IN  p_cuil           VARCHAR(13),
    IN  p_telefono       VARCHAR(20),
    IN  p_tel_emergencia VARCHAR(20),
    IN  p_email          VARCHAR(50),
    -- Datos legajo
    IN  p_fecha_ingreso  DATE,
    IN  p_fecha_adm      DATE,
    IN  p_id_cargo       INT,
    IN  p_id_categoria   INT,
    IN  p_id_oficina     INT,
    IN  p_tipo_contrato  ENUM('locacion','permanente','funcionario'),
    -- OUT
    OUT p_id_persona_nuevo INT,
    OUT p_id_legajo_nuevo  INT,
    OUT p_usuario_creado   VARCHAR(50),
    OUT p_resultado        VARCHAR(200)
)
sp_alta: BEGIN
    DECLARE v_tipo       VARCHAR(20);
    DECLARE v_error      VARCHAR(200);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error = MESSAGE_TEXT;
        ROLLBACK;
        SET p_resultado = CONCAT('ERROR: ', v_error);
        SET p_id_persona_nuevo = NULL;
        SET p_id_legajo_nuevo  = NULL;
        SET p_usuario_creado   = NULL;
    END;

    -- Validar rol del operador
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden dar de alta personal.';
        LEAVE sp_alta;
    END IF;

    -- Validar DNI no duplicado (mensaje claro)
    IF EXISTS (SELECT 1 FROM personas WHERE dni = p_dni) THEN
        SET p_resultado = 'ERROR: El DNI ingresado ya existe en el sistema. Verifique que la persona no este registrada.';
        LEAVE sp_alta;
    END IF;

    -- Validar CUIL no duplicado
    IF EXISTS (SELECT 1 FROM personas WHERE cuil = p_cuil) THEN
        SET p_resultado = 'ERROR: El CUIL ingresado ya existe en el sistema.';
        LEAVE sp_alta;
    END IF;

    START TRANSACTION;

    -- Insertar persona (los triggers validan edad, DNI, CUIL, email, etc.)
    INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento,
        estado_civil, cantidad_hijos, provincia_residencia, ciudad_residencia,
        domicilio_datos, cuil, telefono, telefono_emergencia, email)
    VALUES (p_dni, p_apellido, p_nombre, p_genero, p_fecha_nac,
        p_estado_civil, p_cantidad_hijos, p_provincia, p_ciudad,
        p_domicilio, p_cuil, p_telefono, p_tel_emergencia, p_email);

    SET p_id_persona_nuevo = LAST_INSERT_ID();

    -- Insertar legajo (el trigger crea el usuario automaticamente)
    INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion,
        id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona)
    VALUES (p_fecha_ingreso, p_fecha_adm, p_id_cargo, p_id_categoria,
        p_id_oficina, p_tipo_contrato, 'activo', p_id_persona_nuevo);

    SET p_id_legajo_nuevo = LAST_INSERT_ID();
    SET p_usuario_creado  = CONVERT(p_id_legajo_nuevo, CHAR);

    -- Registrar en log
    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
        accion, tabla_afectada, id_registro, resultado, detalle)
    SELECT p_id_usuario_op, u.usuario, v_tipo,
        'ALTA_COMPLETA', 'personas', p_id_persona_nuevo, 'exitoso',
        CONCAT('Alta completa: DNI=', p_dni, ' | id_legajo=', p_id_legajo_nuevo,
               ' | usuario=', p_usuario_creado, ' | pass inicial=SHA2(dni)')
    FROM usuario u WHERE u.id_usuario = p_id_usuario_op;

    COMMIT;

    SET p_resultado = CONCAT('OK: Alta exitosa. ',
        'Usuario creado: ', p_usuario_creado,
        '. La contrasena inicial es el DNI: ', p_dni,
        '. Se recomienda cambiarla en el primer ingreso.');
END//

-- ============================================================
-- MODIFICAR PERSONA (admin y rrhh)
-- ============================================================
CREATE PROCEDURE sp_modificar_persona(
    IN  p_id_usuario_op INT,
    IN  p_id_persona    INT,
    IN  p_apellido      VARCHAR(50),
    IN  p_nombre        VARCHAR(50),
    IN  p_genero        ENUM('masculino','femenino','sin_determinar'),
    IN  p_fecha_nac     DATE,
    IN  p_estado_civil  ENUM('soltero/a','viudo/a','casado/a','concubinato'),
    IN  p_cantidad_hijos INT,
    IN  p_provincia     VARCHAR(50),
    IN  p_ciudad        VARCHAR(50),
    IN  p_domicilio     VARCHAR(300),
    IN  p_cuil          VARCHAR(13),
    IN  p_telefono      VARCHAR(20),
    IN  p_tel_emergencia VARCHAR(20),
    IN  p_email         VARCHAR(50),
    OUT p_resultado     VARCHAR(100)
)
sp_mod_p: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden modificar datos de personas.';
        LEAVE sp_mod_p;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM personas WHERE id_persona = p_id_persona) THEN
        SET p_resultado = 'ERROR: La persona no existe.';
        LEAVE sp_mod_p;
    END IF;

    UPDATE personas SET
        apellido            = p_apellido,
        nombre              = p_nombre,
        genero              = p_genero,
        fecha_nacimiento    = p_fecha_nac,
        estado_civil        = p_estado_civil,
        cantidad_hijos      = p_cantidad_hijos,
        provincia_residencia = p_provincia,
        ciudad_residencia   = p_ciudad,
        domicilio_datos     = p_domicilio,
        cuil                = p_cuil,
        telefono            = p_telefono,
        telefono_emergencia = p_tel_emergencia,
        email               = p_email
    WHERE id_persona = p_id_persona;

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
        accion, tabla_afectada, id_registro, resultado)
    SELECT p_id_usuario_op, u.usuario, v_tipo,
        'MODIFICAR_PERSONA', 'personas', p_id_persona, 'exitoso'
    FROM usuario u WHERE u.id_usuario = p_id_usuario_op;

    SET p_resultado = 'OK';
END//

-- ============================================================
-- MODIFICAR LEGAJO (admin y rrhh)
-- ============================================================
CREATE PROCEDURE sp_modificar_legajo(
    IN  p_id_usuario_op INT,
    IN  p_id_legajo     INT,
    IN  p_fecha_ingreso DATE,
    IN  p_fecha_adm     DATE,
    IN  p_id_cargo      INT,
    IN  p_id_categoria  INT,
    IN  p_id_oficina    INT,
    IN  p_tipo_contrato ENUM('locacion','permanente','funcionario'),
    OUT p_resultado     VARCHAR(100)
)
sp_mod_l: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden modificar legajos.';
        LEAVE sp_mod_l;
    END IF;

    UPDATE legajos SET
        fecha_ingreso               = p_fecha_ingreso,
        fecha_ingreso_administracion = p_fecha_adm,
        id_cargo                    = p_id_cargo,
        id_categoria                = p_id_categoria,
        id_oficina                  = p_id_oficina,
        tipo_contrato               = p_tipo_contrato
    WHERE id_legajo = p_id_legajo;

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
        accion, tabla_afectada, id_registro, resultado)
    SELECT p_id_usuario_op, u.usuario, v_tipo,
        'MODIFICAR_LEGAJO', 'legajos', p_id_legajo, 'exitoso'
    FROM usuario u WHERE u.id_usuario = p_id_usuario_op;

    SET p_resultado = 'OK';
END//

-- ============================================================
-- BAJA LOGICA DEL LEGAJO (admin y rrhh)
-- ============================================================
CREATE PROCEDURE sp_dar_baja_legajo(
    IN  p_id_usuario_op  INT,
    IN  p_id_legajo      INT,
    IN  p_detalle_baja   VARCHAR(300),
    IN  p_ip             VARCHAR(45),
    OUT p_resultado      VARCHAR(100)
)
sp_baja: BEGIN
    DECLARE v_tipo         VARCHAR(20);
    DECLARE v_estado       VARCHAR(20);
    DECLARE v_sumarios_ab  INT;
    DECLARE v_error        VARCHAR(200);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error = MESSAGE_TEXT;
        ROLLBACK;
        SET p_resultado = CONCAT('ERROR: ', v_error);
    END;

    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden dar de baja legajos.';
        LEAVE sp_baja;
    END IF;

    SELECT estado INTO v_estado FROM legajos WHERE id_legajo = p_id_legajo;
    IF v_estado IS NULL THEN
        SET p_resultado = 'ERROR: El legajo no existe.';
        LEAVE sp_baja;
    END IF;
    IF v_estado = 'de_baja' THEN
        SET p_resultado = 'ERROR: El legajo ya se encuentra dado de baja.';
        LEAVE sp_baja;
    END IF;
    IF v_estado IN ('traslado', 'prestamo') THEN
        SET p_resultado = 'ERROR: No se puede dar de baja un legajo en estado traslado o prestamo. Revierta el estado primero.';
        LEAVE sp_baja;
    END IF;

    -- Verificar sumarios activos pendientes
    SELECT COUNT(*) INTO v_sumarios_ab
    FROM sumarios WHERE id_legajo = p_id_legajo AND activo = 1
    AND detalle NOT LIKE 'Baja de legajo%';
    IF v_sumarios_ab > 0 THEN
        SET p_resultado = 'ERROR: El legajo tiene sumarios activos pendientes. Resolverlos antes de dar de baja.';
        LEAVE sp_baja;
    END IF;

    START TRANSACTION;

    -- Cambiar estado (el trigger tgr_borrado_logico_total hace el resto)
    UPDATE legajos SET estado = 'de_baja' WHERE id_legajo = p_id_legajo;

    -- Registrar sumario de baja
    INSERT INTO sumarios (detalle, id_legajo, activo)
    VALUES (CONCAT('Baja de legajo registrada. Detalle: ', p_detalle_baja),
            p_id_legajo, 0);

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
        accion, tabla_afectada, id_registro, resultado, detalle)
    SELECT p_id_usuario_op, u.usuario, v_tipo, p_ip,
        'BAJA_LEGAJO', 'legajos', p_id_legajo, 'exitoso',
        CONCAT('Baja legajo ', p_id_legajo, '. Motivo: ', p_detalle_baja)
    FROM usuario u WHERE u.id_usuario = p_id_usuario_op;

    COMMIT;
    SET p_resultado = 'OK';
END//

-- ============================================================
-- REACTIVAR LEGAJO (admin y rrhh)
-- ============================================================
CREATE PROCEDURE sp_reactivar_legajo(
    IN  p_id_usuario_op    INT,
    IN  p_id_legajo        INT,
    IN  p_detalle          VARCHAR(300),
    IN  p_ip               VARCHAR(45),
    OUT p_resultado        VARCHAR(100)
)
sp_react: BEGIN
    DECLARE v_tipo   VARCHAR(20);
    DECLARE v_estado VARCHAR(20);
    DECLARE v_error  VARCHAR(200);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error = MESSAGE_TEXT;
        ROLLBACK;
        SET p_resultado = CONCAT('ERROR: ', v_error);
    END;

    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden reactivar legajos.';
        LEAVE sp_react;
    END IF;

    SELECT estado INTO v_estado FROM legajos WHERE id_legajo = p_id_legajo;
    IF v_estado != 'de_baja' THEN
        SET p_resultado = 'ERROR: Solo se pueden reactivar legajos en estado de baja.';
        LEAVE sp_react;
    END IF;

    START TRANSACTION;
    UPDATE legajos SET estado = 'activo' WHERE id_legajo = p_id_legajo;

    INSERT INTO sumarios (detalle, id_legajo, activo)
    VALUES (CONCAT('Reactivacion de legajo. Detalle: ', p_detalle), p_id_legajo, 1);

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
        accion, tabla_afectada, id_registro, resultado, detalle)
    SELECT p_id_usuario_op, u.usuario, v_tipo, p_ip,
        'REACTIVACION_LEGAJO', 'legajos', p_id_legajo, 'exitoso',
        CONCAT('Reactivacion legajo ', p_id_legajo, '. Detalle: ', p_detalle)
    FROM usuario u WHERE u.id_usuario = p_id_usuario_op;

    COMMIT;
    SET p_resultado = 'OK';
END//

-- ============================================================
-- CAMBIAR TIPO DE USUARIO (solo administrador)
-- ============================================================
CREATE PROCEDURE sp_cambiar_tipo_usuario(
    IN  p_id_admin    INT,
    IN  p_id_usuario  INT,
    IN  p_tipo_nuevo  ENUM('funcionario','rrhh','empleado','administrador'),
    OUT p_resultado   VARCHAR(100)
)
sp_cambio_tipo: BEGIN
    DECLARE v_tipo_admin VARCHAR(20);
    SELECT tipo INTO v_tipo_admin
    FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;

    IF v_tipo_admin != 'administrador' THEN
        SET p_resultado = 'ERROR: Solo el administrador puede cambiar el tipo de usuario.';
        LEAVE sp_cambio_tipo;
    END IF;

    UPDATE usuario SET tipo = p_tipo_nuevo WHERE id_usuario = p_id_usuario;

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
        accion, tabla_afectada, id_registro, resultado, detalle)
    SELECT p_id_admin, u.usuario, 'administrador',
        'CAMBIO_TIPO_USUARIO', 'usuario', p_id_usuario, 'exitoso',
        CONCAT('Tipo cambiado a: ', p_tipo_nuevo)
    FROM usuario u WHERE u.id_usuario = p_id_admin;

    SET p_resultado = 'OK';
END//

-- ============================================================
-- LISTADO DE EMPLEADOS (admin, rrhh, funcionario)
-- Muestra: id_legajo, dni, nombre, apellido, estado,
--          genero, categoria, email, telefono
-- ============================================================
CREATE PROCEDURE sp_listado_empleados(
    IN p_id_usuario_op INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh', 'funcionario') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No tiene permisos para ver el listado de empleados.';
    END IF;

    SELECT
        l.id_legajo,
        p.dni,
        p.nombre,
        p.apellido,
        l.estado        AS estado_legajo,
        p.genero,
        cat.nombre_categoria AS categoria,
        p.email,
        p.telefono
    FROM legajos l
    INNER JOIN personas p ON p.id_persona = l.id_persona
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    ORDER BY p.apellido, p.nombre;

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
        accion, tabla_afectada, resultado)
    SELECT p_id_usuario_op, u.usuario, v_tipo,
        'CONSULTA_LISTADO_EMPLEADOS', 'legajos', 'exitoso'
    FROM usuario u WHERE u.id_usuario = p_id_usuario_op;
END//

-- ============================================================
-- CONSULTA COMPLETA POR ID_LEGAJO (admin, rrhh, funcionario)
-- Devuelve multiples result sets con toda la info del legajo
-- ============================================================
CREATE PROCEDURE sp_consulta_por_legajo(
    IN p_id_usuario_op INT,
    IN p_id_legajo     INT
)
BEGIN
    DECLARE v_tipo      VARCHAR(20);
    DECLARE v_id_persona INT;

    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh', 'funcionario') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No tiene permisos para esta consulta.';
    END IF;

    SELECT id_persona INTO v_id_persona
    FROM legajos WHERE id_legajo = p_id_legajo;

    IF v_id_persona IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: El legajo no existe.';
    END IF;

    -- Datos persona y legajo
    SELECT p.*, l.id_legajo, l.fecha_ingreso, l.fecha_ingreso_administracion,
        l.tipo_contrato, l.estado,
        cat.nombre_categoria, c.nombre_cargo, o.nombre_oficina
    FROM personas p
    INNER JOIN legajos l ON l.id_persona = p.id_persona
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.id_legajo = p_id_legajo;

    -- Titulos
    SELECT * FROM titulos WHERE id_persona = v_id_persona ORDER BY fecha_fin DESC;

    -- Cursos
    SELECT * FROM cursos WHERE id_persona = v_id_persona ORDER BY fecha_inicio DESC;

    -- Idiomas
    SELECT * FROM idiomas WHERE id_persona = v_id_persona;

    -- Familiar
    SELECT * FROM familiar WHERE id_persona = v_id_persona;

    -- Antecedentes
    SELECT * FROM antecedente_laboral WHERE id_persona = v_id_persona ORDER BY fecha_inicio DESC;

    -- Historial del legajo
    SELECT * FROM historial_legajos WHERE id_legajo = p_id_legajo ORDER BY fecha_registro DESC;

    -- Sumarios
    SELECT * FROM sumarios WHERE id_legajo = p_id_legajo ORDER BY fecha_registro DESC;

    -- Documentos
    SELECT id_documento, tipo_doc, nombre_archivo, activo, creado_en
    FROM documentos WHERE id_persona = v_id_persona ORDER BY creado_en DESC;

    -- Datos de usuario (sin pass)
    SELECT id_usuario, usuario, tipo, primer_ingreso, activo, fecha_creacion, ultimo_login
    FROM usuario WHERE id_legajo = p_id_legajo;

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario,
        accion, tabla_afectada, id_registro, resultado)
    SELECT p_id_usuario_op, u.usuario, v_tipo,
        'CONSULTA_COMPLETA', 'legajos', p_id_legajo, 'exitoso'
    FROM usuario u WHERE u.id_usuario = p_id_usuario_op;
END//

-- ============================================================
-- CONSULTA COMPLETA POR DNI (admin, rrhh, funcionario)
-- ============================================================
CREATE PROCEDURE sp_consulta_por_dni(
    IN p_id_usuario_op INT,
    IN p_dni           CHAR(8)
)
BEGIN
    DECLARE v_tipo       VARCHAR(20);
    DECLARE v_id_persona INT;
    DECLARE v_id_legajo  INT;

    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh', 'funcionario') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No tiene permisos para esta consulta.';
    END IF;

    SELECT id_persona INTO v_id_persona FROM personas WHERE dni = p_dni;
    IF v_id_persona IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: No existe ninguna persona con ese DNI.';
    END IF;

    SELECT id_legajo INTO v_id_legajo
    FROM legajos WHERE id_persona = v_id_persona
    ORDER BY id_legajo DESC LIMIT 1;

    CALL sp_consulta_por_legajo(p_id_usuario_op, v_id_legajo);
END//

-- ============================================================
-- CONSULTA PROPIA DEL EMPLEADO
-- ============================================================
CREATE PROCEDURE sp_mi_informacion(
    IN p_id_usuario INT
)
BEGIN
    DECLARE v_tipo       VARCHAR(20);
    DECLARE v_id_legajo  INT;
    DECLARE v_id_persona INT;

    SELECT tipo, id_legajo INTO v_tipo, v_id_legajo
    FROM usuario WHERE id_usuario = p_id_usuario AND activo = 1;

    IF v_tipo IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: Usuario no encontrado o inactivo.';
    END IF;

    SELECT id_persona INTO v_id_persona FROM legajos WHERE id_legajo = v_id_legajo;

    SELECT p.*, l.id_legajo, l.fecha_ingreso, l.tipo_contrato, l.estado,
        cat.nombre_categoria, c.nombre_cargo, o.nombre_oficina
    FROM personas p
    INNER JOIN legajos l ON l.id_persona = p.id_persona
    LEFT JOIN categorias cat ON cat.id_categoria = l.id_categoria
    LEFT JOIN cargos c ON c.id_cargo = l.id_cargo
    LEFT JOIN oficinas o ON o.id_oficina = l.id_oficina
    WHERE l.id_legajo = v_id_legajo;

    SELECT * FROM titulos WHERE id_persona = v_id_persona AND activo = 1;
    SELECT * FROM cursos WHERE id_persona = v_id_persona AND activo = 1;
    SELECT * FROM idiomas WHERE id_persona = v_id_persona AND activo = 1;
    SELECT * FROM familiar WHERE id_persona = v_id_persona AND activo = 1;
    SELECT * FROM antecedente_laboral WHERE id_persona = v_id_persona AND activo = 1;
    SELECT * FROM historial_legajos WHERE id_legajo = v_id_legajo AND activo = 1;
    SELECT * FROM sumarios WHERE id_legajo = v_id_legajo;
END//

-- ============================================================
-- INSERTAR DATOS SECUNDARIOS (admin y rrhh)
-- ============================================================

CREATE PROCEDURE sp_insertar_titulo(
    IN p_id_usuario_op INT,
    IN p_id_persona    INT,
    IN p_nombre_titulo VARCHAR(200),
    IN p_institucion   VARCHAR(200),
    IN p_fecha_inicio  DATE,
    IN p_fecha_fin     DATE,
    OUT p_resultado    VARCHAR(100)
)
sp_ins_tit: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden ingresar titulos.';
        LEAVE sp_ins_tit;
    END IF;

    INSERT INTO titulos (id_persona, nombre_titulo, institucion, fecha_inicio, fecha_fin)
    VALUES (p_id_persona, p_nombre_titulo, p_institucion, p_fecha_inicio, p_fecha_fin);

    SET p_resultado = 'OK';
END//

CREATE PROCEDURE sp_insertar_curso(
    IN p_id_usuario_op INT,
    IN p_id_persona    INT,
    IN p_nombre_curso  VARCHAR(200),
    IN p_institucion   VARCHAR(200),
    IN p_fecha_inicio  DATE,
    IN p_horas         INT,
    OUT p_resultado    VARCHAR(100)
)
sp_ins_cur: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden ingresar cursos.';
        LEAVE sp_ins_cur;
    END IF;

    INSERT INTO cursos (id_persona, nombre_curso, institucion, fecha_inicio, horas)
    VALUES (p_id_persona, p_nombre_curso, p_institucion, p_fecha_inicio, p_horas);

    SET p_resultado = 'OK';
END//

CREATE PROCEDURE sp_insertar_idioma(
    IN p_id_usuario_op INT,
    IN p_id_persona    INT,
    IN p_nombre        ENUM('Inglés','Italiano','Portugués','Francés','Alemán',
                            'Español','Coreano','Japonés','Chino Mandarín'),
    IN p_nivel         ENUM('basico','intermedio','avanzado','nativo'),
    OUT p_resultado    VARCHAR(100)
)
sp_ins_id: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden ingresar idiomas.';
        LEAVE sp_ins_id;
    END IF;

    INSERT INTO idiomas (id_persona, nombre, nivel)
    VALUES (p_id_persona, p_nombre, p_nivel);

    SET p_resultado = 'OK';
END//

CREATE PROCEDURE sp_insertar_familiar(
    IN p_id_usuario_op     INT,
    IN p_id_persona        INT,
    IN p_relacion          ENUM('padre','madre','hijo/a','conyuge','suegro/a','hermano/a','otro'),
    IN p_apellido_familiar VARCHAR(50),
    IN p_nombre_familiar   VARCHAR(50),
    IN p_dni_familiar      CHAR(8),
    IN p_fecha_nac         DATE,
    OUT p_resultado        VARCHAR(100)
)
sp_ins_fam: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden ingresar datos familiares.';
        LEAVE sp_ins_fam;
    END IF;

    INSERT INTO familiar (id_persona, relacion_empleado, apellido_familiar,
        nombre_familiar, dni_familiar, fecha_nacimiento_familiar)
    VALUES (p_id_persona, p_relacion, p_apellido_familiar,
        p_nombre_familiar, p_dni_familiar, p_fecha_nac);

    SET p_resultado = 'OK';
END//

CREATE PROCEDURE sp_insertar_antecedente(
    IN p_id_usuario_op INT,
    IN p_id_persona    INT,
    IN p_empresa       VARCHAR(200),
    IN p_cargo         VARCHAR(200),
    IN p_fecha_inicio  DATE,
    IN p_fecha_fin     DATE,
    OUT p_resultado    VARCHAR(100)
)
sp_ins_ant: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden ingresar antecedentes laborales.';
        LEAVE sp_ins_ant;
    END IF;

    INSERT INTO antecedente_laboral (id_persona, empresa, cargo_ocupado,
        fecha_inicio, fecha_fin)
    VALUES (p_id_persona, p_empresa, p_cargo, p_fecha_inicio, p_fecha_fin);

    SET p_resultado = 'OK';
END//

CREATE PROCEDURE sp_insertar_sumario(
    IN p_id_usuario_op INT,
    IN p_id_legajo     INT,
    IN p_detalle       VARCHAR(300),
    OUT p_resultado    VARCHAR(100)
)
sp_ins_sum: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden ingresar sumarios.';
        LEAVE sp_ins_sum;
    END IF;

    INSERT INTO sumarios (id_legajo, detalle) VALUES (p_id_legajo, p_detalle);
    SET p_resultado = 'OK';
END//

CREATE PROCEDURE sp_insertar_historial(
    IN p_id_usuario_op INT,
    IN p_id_legajo     INT,
    IN p_accion        ENUM('advertencia','llamada_atencion','suspension',
                            'cambio_de_funcion','sumario','traslado',
                            'vencimiento_contrato','jubilacion','renuncia',
                            'difunto','incapacidad','licencia'),
    IN p_detalle       VARCHAR(300),
    OUT p_resultado    VARCHAR(100)
)
sp_ins_his: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_usuario_op AND activo = 1;

    IF v_tipo NOT IN ('administrador', 'rrhh') THEN
        SET p_resultado = 'ERROR: Solo administrador o RRHH pueden registrar historial.';
        LEAVE sp_ins_his;
    END IF;

    INSERT INTO historial_legajos (id_legajo, accion, detalle)
    VALUES (p_id_legajo, p_accion, p_detalle);

    SET p_resultado = 'OK';
END//

-- ============================================================
-- BACKUPS (solo administrador)
-- ============================================================

CREATE PROCEDURE sp_backup_generar_diario(
    IN  p_id_admin  INT,
    IN  p_ruta_base VARCHAR(500),
    IN  p_ip        VARCHAR(45),
    OUT p_nombre    VARCHAR(255),
    OUT p_comando   TEXT,
    OUT p_resultado VARCHAR(100)
)
sp_backup_d: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;

    IF v_tipo != 'administrador' THEN
        SET p_resultado = 'ERROR: Solo el administrador puede generar backups.';
        LEAVE sp_backup_d;
    END IF;

    SET p_nombre  = CONCAT('backup_diario_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s'), '.sql');
    SET p_comando = CONCAT('mysqldump --single-transaction --routines --triggers ',
                           'torres_corregida1 > ',
                           p_ruta_base, '/', p_nombre);

    INSERT INTO backup_log (tipo, nombre_archivo, ruta_archivo, resultado)
    VALUES ('diario', p_nombre, CONCAT(p_ruta_base, '/', p_nombre), 'exitoso');

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
        accion, resultado, detalle)
    SELECT p_id_admin, u.usuario, 'administrador', p_ip,
        'BACKUP_DIARIO', 'exitoso', CONCAT('Backup: ', p_nombre)
    FROM usuario u WHERE u.id_usuario = p_id_admin;

    SET p_resultado = 'OK';
END//

CREATE PROCEDURE sp_backup_generar_semanal(
    IN  p_id_admin  INT,
    IN  p_ruta_base VARCHAR(500),
    IN  p_ip        VARCHAR(45),
    OUT p_nombre    VARCHAR(255),
    OUT p_comando   TEXT,
    OUT p_resultado VARCHAR(100)
)
sp_backup_s: BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;

    IF v_tipo != 'administrador' THEN
        SET p_resultado = 'ERROR: Solo el administrador puede generar backups.';
        LEAVE sp_backup_s;
    END IF;

    SET p_nombre  = CONCAT('backup_semanal_semana', WEEK(NOW()), '_', YEAR(NOW()), '.sql');
    SET p_comando = CONCAT('mysqldump --single-transaction --routines --triggers ',
                           'torres_corregida1 > ',
                           p_ruta_base, '/', p_nombre);

    INSERT INTO backup_log (tipo, nombre_archivo, ruta_archivo, resultado)
    VALUES ('semanal', p_nombre, CONCAT(p_ruta_base, '/', p_nombre), 'exitoso');

    INSERT INTO log_sistema (id_usuario, usuario_nombre, tipo_usuario, ip,
        accion, resultado, detalle)
    SELECT p_id_admin, u.usuario, 'administrador', p_ip,
        'BACKUP_SEMANAL', 'exitoso', CONCAT('Backup: ', p_nombre)
    FROM usuario u WHERE u.id_usuario = p_id_admin;

    SET p_resultado = 'OK';
END//

CREATE PROCEDURE sp_backup_listar(
    IN p_id_admin INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo
    FROM usuario WHERE id_usuario = p_id_admin AND activo = 1;

    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: Solo el administrador puede listar backups.';
    END IF;

    SELECT * FROM backup_log ORDER BY fecha_hora DESC;
END//

DELIMITER ;
