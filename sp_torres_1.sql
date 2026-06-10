USE torres_corregida1;

-- LISTADO COMPLETO DE STORED PROCEDURES PARA torres_corregida1
-- OBJETIVO: Gestionar el ingreso, modificación y consulta de datos por tabla
-- ACCESO: Solo usuarios con tipo = 'rrhh' pueden insertar y modificar datos

-- 1. TABLA: personas
-- SP #1:  sp_insertar_persona      - Inserta una persona nueva
-- SP #2:  sp_modificar_persona     - Modifica los datos de una persona
-- SP #3:  sp_obtener_persona       - Muestra los datos de una persona

-- 2. TABLA: legajos
-- SP #4:  sp_insertar_legajo       - Inserta un legajo nuevo
-- SP #5:  sp_modificar_legajo      - Modifica los datos de un legajo
-- SP #6:  sp_obtener_legajo        - Muestra los datos de un legajo

-- 3. TABLA: titulos
-- SP #7:  sp_insertar_titulo       - Inserta un título nuevo
-- SP #8:  sp_modificar_titulo      - Modifica los datos de un título
-- SP #9:  sp_obtener_titulos       - Muestra los títulos de un legajo

-- 4. TABLA: cursos
-- SP #10: sp_insertar_curso        - Inserta un curso nuevo
-- SP #11: sp_modificar_curso       - Modifica los datos de un curso
-- SP #12: sp_obtener_cursos        - Muestra los cursos de un legajo

-- 5. TABLA: idiomas
-- SP #13: sp_insertar_idioma       - Inserta un idioma nuevo
-- SP #14: sp_modificar_idioma      - Modifica los datos de un idioma
-- SP #15: sp_obtener_idiomas       - Muestra los idiomas de un legajo

-- 6. TABLA: familiar
-- SP #16: sp_insertar_familiar     - Inserta un familiar nuevo
-- SP #17: sp_modificar_familiar    - Modifica los datos de un familiar
-- SP #18: sp_obtener_familiares    - Muestra los familiares de un legajo

-- 7. TABLA: antecedente_laboral
-- SP #19: sp_insertar_antecedente  - Inserta un antecedente laboral nuevo
-- SP #20: sp_modificar_antecedente - Modifica los datos de un antecedente laboral
-- SP #21: sp_obtener_antecedentes  - Muestra los antecedentes laborales de un legajo

-- 8. TABLA: historial_legajos
-- SP #22: sp_insertar_historial    - Inserta un historial de legajo nuevo
-- SP #23: sp_modificar_historial   - Modifica los datos de un historial de legajo
-- SP #24: sp_obtener_historial     - Muestra el historial de un legajo

-- 9. TABLA: sumarios
-- SP #25: sp_insertar_sumario      - Inserta un sumario nuevo
-- SP #26: sp_modificar_sumario     - Modifica los datos de un sumario
-- SP #27: sp_obtener_sumarios      - Muestra los sumarios de un legajo

-- 10. TABLA: documentos
-- SP #28: sp_insertar_documento    - Inserta un documento nuevo
-- SP #29: sp_modificar_documento   - Modifica los datos de un documento
-- SP #30: sp_obtener_documentos    - Muestra los documentos de un legajo

-- 11. TABLA: usuario
-- SP #31: sp_insertar_usuario      - Genera usuario y clave automaticamente, controla rol del creador y devuelve credenciales para envio
-- SP #32: sp_modificar_usuario     - Modifica tipo y estado de un usuario, controla rol del creador
-- SP #33: sp_obtener_usuario       - Muestra los datos de un usuario

-- ======================================================================
-- LIMPIEZA PREVIA DE STORED PROCEDURES
-- ======================================================================
DROP PROCEDURE IF EXISTS sp_insertar_persona;
DROP PROCEDURE IF EXISTS sp_modificar_persona;
DROP PROCEDURE IF EXISTS sp_obtener_persona;
DROP PROCEDURE IF EXISTS sp_insertar_legajo;
DROP PROCEDURE IF EXISTS sp_modificar_legajo;
DROP PROCEDURE IF EXISTS sp_obtener_legajo;
DROP PROCEDURE IF EXISTS sp_insertar_titulo;
DROP PROCEDURE IF EXISTS sp_modificar_titulo;
DROP PROCEDURE IF EXISTS sp_obtener_titulos;
DROP PROCEDURE IF EXISTS sp_insertar_curso;
DROP PROCEDURE IF EXISTS sp_modificar_curso;
DROP PROCEDURE IF EXISTS sp_obtener_cursos;
DROP PROCEDURE IF EXISTS sp_insertar_idioma;
DROP PROCEDURE IF EXISTS sp_modificar_idioma;
DROP PROCEDURE IF EXISTS sp_obtener_idiomas;
DROP PROCEDURE IF EXISTS sp_insertar_familiar;
DROP PROCEDURE IF EXISTS sp_modificar_familiar;
DROP PROCEDURE IF EXISTS sp_obtener_familiares;
DROP PROCEDURE IF EXISTS sp_insertar_antecedente;
DROP PROCEDURE IF EXISTS sp_modificar_antecedente;
DROP PROCEDURE IF EXISTS sp_obtener_antecedentes;
DROP PROCEDURE IF EXISTS sp_insertar_historial;
DROP PROCEDURE IF EXISTS sp_modificar_historial;
DROP PROCEDURE IF EXISTS sp_obtener_historial;
DROP PROCEDURE IF EXISTS sp_insertar_sumario;
DROP PROCEDURE IF EXISTS sp_modificar_sumario;
DROP PROCEDURE IF EXISTS sp_obtener_sumarios;
DROP PROCEDURE IF EXISTS sp_insertar_documento;
DROP PROCEDURE IF EXISTS sp_modificar_documento;
DROP PROCEDURE IF EXISTS sp_obtener_documentos;
DROP PROCEDURE IF EXISTS sp_insertar_usuario;
DROP PROCEDURE IF EXISTS sp_modificar_usuario;
DROP PROCEDURE IF EXISTS sp_obtener_usuario;

DELIMITER //

-- ======================================================================
-- 1. TABLA: personas
-- ======================================================================

CREATE PROCEDURE sp_insertar_persona(
    IN p_id_usuario_rrhh INT,
    IN p_dni CHAR(8),
    IN p_apellido VARCHAR(50),
    IN p_nombre VARCHAR(50),
    IN p_genero ENUM('masculino','femenino','sin_determinar'),
    IN p_fecha_nacimiento DATE,
    IN p_estado_civil ENUM('soltero/a','viudo/a','casado/a','concubinato'),
    IN p_cantidad_hijos INT,
    IN p_provincia_residencia VARCHAR(50),
    IN p_ciudad_residencia VARCHAR(50),
    IN p_domicilio_datos VARCHAR(300),
    IN p_cuil VARCHAR(13),
    IN p_telefono VARCHAR(20),
    IN p_telefono_emergencia VARCHAR(20),
    IN p_email VARCHAR(50)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento, estado_civil, cantidad_hijos, provincia_residencia, ciudad_residencia, domicilio_datos, cuil, telefono, telefono_emergencia, email)
    VALUES (p_dni, p_apellido, p_nombre, p_genero, p_fecha_nacimiento, p_estado_civil, p_cantidad_hijos, p_provincia_residencia, p_ciudad_residencia, p_domicilio_datos, p_cuil, p_telefono, p_telefono_emergencia, p_email);
END//

CREATE PROCEDURE sp_modificar_persona(
    IN p_id_usuario_rrhh INT,
    IN p_id_persona INT,
    IN p_dni CHAR(8),
    IN p_apellido VARCHAR(50),
    IN p_nombre VARCHAR(50),
    IN p_genero ENUM('masculino','femenino','sin_determinar'),
    IN p_fecha_nacimiento DATE,
    IN p_estado_civil ENUM('soltero/a','viudo/a','casado/a','concubinato'),
    IN p_cantidad_hijos INT,
    IN p_provincia_residencia VARCHAR(50),
    IN p_ciudad_residencia VARCHAR(50),
    IN p_domicilio_datos VARCHAR(300),
    IN p_cuil VARCHAR(13),
    IN p_telefono VARCHAR(20),
    IN p_telefono_emergencia VARCHAR(20),
    IN p_email VARCHAR(50)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE personas SET
        dni = p_dni,
        apellido = p_apellido,
        nombre = p_nombre,
        genero = p_genero,
        fecha_nacimiento = p_fecha_nacimiento,
        estado_civil = p_estado_civil,
        cantidad_hijos = p_cantidad_hijos,
        provincia_residencia = p_provincia_residencia,
        ciudad_residencia = p_ciudad_residencia,
        domicilio_datos = p_domicilio_datos,
        cuil = p_cuil,
        telefono = p_telefono,
        telefono_emergencia = p_telefono_emergencia,
        email = p_email
    WHERE id_persona = p_id_persona;
END//

CREATE PROCEDURE sp_obtener_persona(
    IN p_id_persona INT
)
BEGIN
    SELECT * FROM personas WHERE id_persona = p_id_persona;
END//

-- ======================================================================
-- 2. TABLA: legajos
-- ======================================================================

CREATE PROCEDURE sp_insertar_legajo(
    IN p_id_usuario_rrhh INT,
    IN p_fecha_ingreso DATE,
    IN p_fecha_ingreso_administracion DATE,
    IN p_id_cargo INT,
    IN p_id_categoria INT,
    IN p_id_oficina INT,
    IN p_tipo_contrato ENUM('locacion','permanente','funcionario'),
    IN p_estado ENUM('activo','de_baja','traslado','prestamo'),
    IN p_id_persona INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion, id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona)
    VALUES (p_fecha_ingreso, p_fecha_ingreso_administracion, p_id_cargo, p_id_categoria, p_id_oficina, p_tipo_contrato, p_estado, p_id_persona);
END//

CREATE PROCEDURE sp_modificar_legajo(
    IN p_id_usuario_rrhh INT,
    IN p_id_legajo INT,
    IN p_fecha_ingreso DATE,
    IN p_fecha_ingreso_administracion DATE,
    IN p_id_cargo INT,
    IN p_id_categoria INT,
    IN p_id_oficina INT,
    IN p_tipo_contrato ENUM('locacion','permanente','funcionario'),
    IN p_estado ENUM('activo','de_baja','traslado','prestamo')
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE legajos SET
        fecha_ingreso = p_fecha_ingreso,
        fecha_ingreso_administracion = p_fecha_ingreso_administracion,
        id_cargo = p_id_cargo,
        id_categoria = p_id_categoria,
        id_oficina = p_id_oficina,
        tipo_contrato = p_tipo_contrato,
        estado = p_estado
    WHERE id_legajo = p_id_legajo;
END//

CREATE PROCEDURE sp_obtener_legajo(
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

-- ======================================================================
-- 3. TABLA: titulos
-- ======================================================================

CREATE PROCEDURE sp_insertar_titulo(
    IN p_id_usuario_rrhh INT,
    IN p_titulo VARCHAR(100),
    IN p_nivel_estudio ENUM('primaria','secundaria','terciario','universitario','doctorado','maestria','sin_estudios'),
    IN p_institucion VARCHAR(100),
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO titulos (titulo, nivel_estudio, institucion, fecha_inicio, fecha_fin, id_legajo)
    VALUES (p_titulo, p_nivel_estudio, p_institucion, p_fecha_inicio, p_fecha_fin, p_id_legajo);
END//

CREATE PROCEDURE sp_modificar_titulo(
    IN p_id_usuario_rrhh INT,
    IN p_id_titulo INT,
    IN p_titulo VARCHAR(100),
    IN p_nivel_estudio ENUM('primaria','secundaria','terciario','universitario','doctorado','maestria','sin_estudios'),
    IN p_institucion VARCHAR(100),
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE titulos SET
        titulo = p_titulo,
        nivel_estudio = p_nivel_estudio,
        institucion = p_institucion,
        fecha_inicio = p_fecha_inicio,
        fecha_fin = p_fecha_fin
    WHERE id_titulo = p_id_titulo;
END//

CREATE PROCEDURE sp_obtener_titulos(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM titulos WHERE id_legajo = p_id_legajo AND activo = 1;
END//

-- ======================================================================
-- 4. TABLA: cursos
-- ======================================================================

CREATE PROCEDURE sp_insertar_curso(
    IN p_id_usuario_rrhh INT,
    IN p_nombre VARCHAR(100),
    IN p_institucion VARCHAR(100),
    IN p_fecha_inicio DATE,
    IN p_horas INT,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO cursos (nombre, institucion, fecha_inicio, horas, id_legajo)
    VALUES (p_nombre, p_institucion, p_fecha_inicio, p_horas, p_id_legajo);
END//

CREATE PROCEDURE sp_modificar_curso(
    IN p_id_usuario_rrhh INT,
    IN p_id_curso INT,
    IN p_nombre VARCHAR(100),
    IN p_institucion VARCHAR(100),
    IN p_fecha_inicio DATE,
    IN p_horas INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE cursos SET
        nombre = p_nombre,
        institucion = p_institucion,
        fecha_inicio = p_fecha_inicio,
        horas = p_horas
    WHERE id_curso = p_id_curso;
END//

CREATE PROCEDURE sp_obtener_cursos(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM cursos WHERE id_legajo = p_id_legajo AND activo = 1;
END//

-- ======================================================================
-- 5. TABLA: idiomas
-- ======================================================================

CREATE PROCEDURE sp_insertar_idioma(
    IN p_id_usuario_rrhh INT,
    IN p_nombre ENUM('Inglés','Italiano','Portugués','Francés','Alemán','Español','Coreano','Japonés','Chino Mandarín'),
    IN p_nivel ENUM('Principiante (A1-A2)','Intermedio (B1-B2)','Avanzado (C1-C2)','Nativo'),
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO idiomas (nombre, nivel, id_legajo)
    VALUES (p_nombre, p_nivel, p_id_legajo);
END//

CREATE PROCEDURE sp_modificar_idioma(
    IN p_id_usuario_rrhh INT,
    IN p_id_idioma INT,
    IN p_nombre ENUM('Inglés','Italiano','Portugués','Francés','Alemán','Español','Coreano','Japonés','Chino Mandarín'),
    IN p_nivel ENUM('Principiante (A1-A2)','Intermedio (B1-B2)','Avanzado (C1-C2)','Nativo')
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE idiomas SET
        nombre = p_nombre,
        nivel = p_nivel
    WHERE id_idioma = p_id_idioma;
END//

CREATE PROCEDURE sp_obtener_idiomas(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM idiomas WHERE id_legajo = p_id_legajo AND activo = 1;
END//

-- ======================================================================
-- 6. TABLA: familiar
-- ======================================================================

CREATE PROCEDURE sp_insertar_familiar(
    IN p_id_usuario_rrhh INT,
    IN p_nombre_familiar VARCHAR(50),
    IN p_apellido_familiar VARCHAR(50),
    IN p_dni_familiar CHAR(8),
    IN p_fecha_nac_familiar DATE,
    IN p_estado ENUM('vivo','fallecido'),
    IN p_relacion_empleado ENUM('padres','hijos','suegros','sobrinos','conyuge'),
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO familiar (nombre_familiar, apellido_familiar, dni_familiar, fecha_nac_familiar, estado, relacion_empleado, id_legajo)
    VALUES (p_nombre_familiar, p_apellido_familiar, p_dni_familiar, p_fecha_nac_familiar, p_estado, p_relacion_empleado, p_id_legajo);
END//

CREATE PROCEDURE sp_modificar_familiar(
    IN p_id_usuario_rrhh INT,
    IN p_id_familiar INT,
    IN p_nombre_familiar VARCHAR(50),
    IN p_apellido_familiar VARCHAR(50),
    IN p_dni_familiar CHAR(8),
    IN p_fecha_nac_familiar DATE,
    IN p_estado ENUM('vivo','fallecido'),
    IN p_relacion_empleado ENUM('padres','hijos','suegros','sobrinos','conyuge')
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE familiar SET
        nombre_familiar = p_nombre_familiar,
        apellido_familiar = p_apellido_familiar,
        dni_familiar = p_dni_familiar,
        fecha_nac_familiar = p_fecha_nac_familiar,
        estado = p_estado,
        relacion_empleado = p_relacion_empleado
    WHERE id_familiar = p_id_familiar;
END//

CREATE PROCEDURE sp_obtener_familiares(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM familiar WHERE id_legajo = p_id_legajo AND activo = 1;
END//

-- ======================================================================
-- 7. TABLA: antecedente_laboral
-- ======================================================================

CREATE PROCEDURE sp_insertar_antecedente(
    IN p_id_usuario_rrhh INT,
    IN p_empresa VARCHAR(100),
    IN p_cargo VARCHAR(100),
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO antecedente_laboral (empresa, cargo, fecha_inicio, fecha_fin, id_legajo)
    VALUES (p_empresa, p_cargo, p_fecha_inicio, p_fecha_fin, p_id_legajo);
END//

CREATE PROCEDURE sp_modificar_antecedente(
    IN p_id_usuario_rrhh INT,
    IN p_id_antecedente INT,
    IN p_empresa VARCHAR(100),
    IN p_cargo VARCHAR(100),
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE antecedente_laboral SET
        empresa = p_empresa,
        cargo = p_cargo,
        fecha_inicio = p_fecha_inicio,
        fecha_fin = p_fecha_fin
    WHERE id_antecedente = p_id_antecedente;
END//

CREATE PROCEDURE sp_obtener_antecedentes(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM antecedente_laboral WHERE id_legajo = p_id_legajo AND activo = 1;
END//

-- ======================================================================
-- 8. TABLA: historial_legajos
-- ======================================================================

CREATE PROCEDURE sp_insertar_historial(
    IN p_id_usuario_rrhh INT,
    IN p_accion ENUM('advertencia','llamada_atencion','suspension','cambio_de_funcion','sumario','traslado','vencimiento_contrato','jubilacion','renuncia','difunto','incapacidad','licencia'),
    IN p_detalle VARCHAR(300),
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO historial_legajos (accion, detalle, id_legajo)
    VALUES (p_accion, p_detalle, p_id_legajo);
END//

CREATE PROCEDURE sp_modificar_historial(
    IN p_id_usuario_rrhh INT,
    IN p_id_historial INT,
    IN p_accion ENUM('advertencia','llamada_atencion','suspension','cambio_de_funcion','sumario','traslado','vencimiento_contrato','jubilacion','renuncia','difunto','incapacidad','licencia'),
    IN p_detalle VARCHAR(300)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE historial_legajos SET
        accion = p_accion,
        detalle = p_detalle
    WHERE id_historial = p_id_historial;
END//

CREATE PROCEDURE sp_obtener_historial(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM historial_legajos WHERE id_legajo = p_id_legajo AND activo = 1 ORDER BY fecha_registro DESC;
END//

-- ======================================================================
-- 9. TABLA: sumarios
-- ======================================================================

CREATE PROCEDURE sp_insertar_sumario(
    IN p_id_usuario_rrhh INT,
    IN p_detalle VARCHAR(300),
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO sumarios (detalle, id_legajo)
    VALUES (p_detalle, p_id_legajo);
END//

CREATE PROCEDURE sp_modificar_sumario(
    IN p_id_usuario_rrhh INT,
    IN p_id_sumario INT,
    IN p_detalle VARCHAR(300)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE sumarios SET
        detalle = p_detalle
    WHERE id_sumario = p_id_sumario;
END//

CREATE PROCEDURE sp_obtener_sumarios(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM sumarios WHERE id_legajo = p_id_legajo AND activo = 1 ORDER BY fecha_registro DESC;
END//

-- ======================================================================
-- 10. TABLA: documentos
-- ======================================================================

CREATE PROCEDURE sp_insertar_documento(
    IN p_id_usuario_rrhh INT,
    IN p_tipo_doc ENUM('DNI','TITULO','CURSOS','LICENCIA','ACTA_DE_NACIMIENTO','CERTIFICADO_ESCOLARIDAD','CERTIFICADO_DEFUNCION','SUMARIO','RESOLUCION','CERTIFICADO_DE_CASAMIENTO','FOTO_PERFIL','CURRICULUM','OTRO'),
    IN p_descripcion TEXT,
    IN p_nombre_archivo VARCHAR(255),
    IN p_ruta_archivo VARCHAR(500),
    IN p_tamano_archivo INT,
    IN p_mime_type VARCHAR(100),
    IN p_extension VARCHAR(10),
    IN p_hash_archivo VARCHAR(64),
    IN p_id_legajo INT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden insertar datos.';
    END IF;
    INSERT INTO documentos (tipo_doc, descripcion, nombre_archivo, ruta_archivo, tamano_archivo, mime_type, extension, hash_archivo, id_legajo)
    VALUES (p_tipo_doc, p_descripcion, p_nombre_archivo, p_ruta_archivo, p_tamano_archivo, p_mime_type, p_extension, p_hash_archivo, p_id_legajo);
END//

CREATE PROCEDURE sp_modificar_documento(
    IN p_id_usuario_rrhh INT,
    IN p_id_documento INT,
    IN p_tipo_doc ENUM('DNI','TITULO','CURSOS','LICENCIA','ACTA_DE_NACIMIENTO','CERTIFICADO_ESCOLARIDAD','CERTIFICADO_DEFUNCION','SUMARIO','RESOLUCION','CERTIFICADO_DE_CASAMIENTO','FOTO_PERFIL','CURRICULUM','OTRO'),
    IN p_descripcion TEXT,
    IN p_nombre_archivo VARCHAR(255),
    IN p_ruta_archivo VARCHAR(500),
    IN p_tamano_archivo INT,
    IN p_mime_type VARCHAR(100),
    IN p_extension VARCHAR(10),
    IN p_hash_archivo VARCHAR(64)
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_rrhh AND activo = 1;
    IF v_tipo != 'rrhh' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo usuarios rrhh pueden modificar datos.';
    END IF;
    UPDATE documentos SET
        tipo_doc = p_tipo_doc,
        descripcion = p_descripcion,
        nombre_archivo = p_nombre_archivo,
        ruta_archivo = p_ruta_archivo,
        tamano_archivo = p_tamano_archivo,
        mime_type = p_mime_type,
        extension = p_extension,
        hash_archivo = p_hash_archivo
    WHERE id_documento = p_id_documento;
END//

CREATE PROCEDURE sp_obtener_documentos(
    IN p_id_legajo INT
)
BEGIN
    SELECT * FROM documentos WHERE id_legajo = p_id_legajo AND activo = 1 ORDER BY creado_en DESC;
END//

-- ======================================================================
-- 11. TABLA: usuario
-- ======================================================================

CREATE PROCEDURE sp_insertar_usuario(
    IN p_id_usuario_creador INT,
    IN p_tipo ENUM('funcionario','rrhh','empleado','administrador'),
    IN p_id_legajo INT,
    OUT p_usuario_generado VARCHAR(50),
    OUT p_pass_generada VARCHAR(50),
    OUT p_email_persona VARCHAR(50),
    OUT p_telefono_persona VARCHAR(20)
)
BEGIN
    DECLARE v_tipo_creador VARCHAR(20);
    DECLARE v_apellido VARCHAR(50);
    DECLARE v_num1 INT;
    DECLARE v_num2 INT;
    DECLARE v_usuario_base VARCHAR(50);
    DECLARE v_usuario_final VARCHAR(50);
    DECLARE v_pass VARCHAR(50);
    DECLARE v_existe INT;
    DECLARE v_contador INT DEFAULT 0;

    SELECT tipo INTO v_tipo_creador FROM usuario WHERE id_usuario = p_id_usuario_creador AND activo = 1;

    IF v_tipo_creador = 'rrhh' AND p_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El rol rrhh solo puede crear usuarios tipo empleado.';
    END IF;

    IF v_tipo_creador NOT IN ('rrhh', 'administrador') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo rrhh o administrador pueden crear usuarios.';
    END IF;

    SELECT p.apellido, p.email, p.telefono
    INTO v_apellido, p_email_persona, p_telefono_persona
    FROM personas p
    INNER JOIN legajos l ON l.id_persona = p.id_persona
    WHERE l.id_legajo = p_id_legajo;

    SET v_usuario_base = LOWER(CONCAT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        v_apellido,
        'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),
        'Á','a'),'É','e'),'Í','i'),'Ó','o'),'Ú','u'),
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
    SET v_pass = CONCAT(
        UPPER(LEFT(v_apellido, 1)),
        LOWER(SUBSTRING(v_apellido, 2, 4)),
        v_num1,
        v_num2
    );

    INSERT INTO usuario (usuario, pass, tipo, id_legajo, activo)
    VALUES (v_usuario_final, SHA2(v_pass, 256), p_tipo, p_id_legajo, 1);

    SET p_usuario_generado = v_usuario_final;
    SET p_pass_generada = v_pass;
END//

CREATE PROCEDURE sp_modificar_usuario(
    IN p_id_usuario_creador INT,
    IN p_id_usuario INT,
    IN p_tipo ENUM('funcionario','rrhh','empleado','administrador'),
    IN p_activo TINYINT(1)
)
BEGIN
    DECLARE v_tipo_creador VARCHAR(20);
    DECLARE v_tipo_destino VARCHAR(20);

    SELECT tipo INTO v_tipo_creador FROM usuario WHERE id_usuario = p_id_usuario_creador AND activo = 1;
    SELECT tipo INTO v_tipo_destino FROM usuario WHERE id_usuario = p_id_usuario;

    IF v_tipo_creador NOT IN ('rrhh', 'administrador') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo rrhh o administrador pueden modificar usuarios.';
    END IF;

    IF v_tipo_creador = 'rrhh' AND p_tipo != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El rol rrhh solo puede gestionar usuarios tipo empleado.';
    END IF;

    IF v_tipo_creador = 'rrhh' AND v_tipo_destino != 'empleado' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El rol rrhh solo puede modificar usuarios tipo empleado.';
    END IF;

    UPDATE usuario SET
        tipo = p_tipo,
        activo = p_activo
    WHERE id_usuario = p_id_usuario;
END//

CREATE PROCEDURE sp_obtener_usuario(
    IN p_id_legajo INT
)
BEGIN
    SELECT id_usuario, usuario, tipo, id_legajo, primer_ingreso, activo, fecha_creacion, ultimo_login
    FROM usuario
    WHERE id_legajo = p_id_legajo;
END//

DELIMITER ;
