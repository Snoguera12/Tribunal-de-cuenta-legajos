-- ============================================================
-- SISTEMA TORRES_CORREGIDA1 — TABLAS
-- Base de datos: torres_corregida1
-- Motor: MySQL 8.0 | utf8mb4_spanish_ci
-- ============================================================
--
-- ORDEN DE CREACION:
-- ============================================================
--
-- [MAESTRAS / CATALOGOS]
--   TABLE categorias              — Categorias de escalafon (CAT 1 a CAT 12)
--   TABLE cargos                  — Puestos de trabajo disponibles
--   TABLE oficinas                — Oficinas o sectores de la organizacion
--   TABLE historico_categorias    — Auditoria de cambios en categorias
--   TABLE historico_cargos        — Auditoria de cambios en cargos
--   TABLE historico_oficinas      — Auditoria de cambios en oficinas
--
-- [TABLAS PRINCIPALES]
--   TABLE personas                — Datos personales del individuo (DNI, nombre, contacto, etc.)
--   TABLE legajos                 — Legajo laboral (cargo, categoria, oficina, estado, contrato)
--   TABLE usuario                 — Credenciales de acceso (usuario=id_legajo, pass=dni, tipo, activo)
--   TABLE titulos                 — Titulos educativos del empleado (FK -> personas)
--   TABLE cursos                  — Cursos y capacitaciones (FK -> personas)
--   TABLE idiomas                 — Idiomas que domina el empleado (FK -> personas)
--   TABLE familiar                — Grupo familiar (padres, hijos, conyuge, etc.) (FK -> personas)
--   TABLE antecedente_laboral     — Historial de empleos anteriores (FK -> personas)
--   TABLE historial_legajos       — Acciones del legajo: advertencias, traslados, sumarios, etc.
--   TABLE sumarios                — Sumarios disciplinarios y notas (FK -> legajos)
--   TABLE documentos              — Archivos digitalizados: DNI, titulos, fotos, etc. (FK -> personas)
--
-- [TABLAS HISTORICAS DE LAS PRINCIPALES]
-- (registran valor anterior en cada INSERT/UPDATE — auditoria automatica via triggers)
--   TABLE historico_personas
--   TABLE historico_legajos
--   TABLE historico_titulos
--   TABLE historico_cursos
--   TABLE historico_idiomas
--   TABLE historico_familiar
--   TABLE historico_antecedente_laboral
--   TABLE historico_historial_legajos
--   TABLE historico_sumarios
--   TABLE historico_documentos
--   TABLE historico_usuario
--
-- [TABLAS DE SISTEMA / ALERTAS / SEGURIDAD]
--   TABLE log_sistema             — Log detallado: usuario, IP, accion, tabla, resultado, duracion
--   TABLE intentos_login          — Contador de intentos fallidos con bloqueo automatico
--   TABLE solicitudes_recuperacion — Solicitudes de recuperacion de clave al administrador
--   TABLE sesiones_activas        — Tokens de sesion activos (para middleware de autenticacion)
--   TABLE eventos                 — Eventos del sistema: login, logout, cambio_pass, baja, etc.
--   TABLE incidentes              — Incidentes de seguridad con nivel de severidad
--   TABLE backup_log              — Registro de backups generados: tipo, ruta, resultado
--
-- ============================================================

DROP DATABASE IF EXISTS torres_corregida1;
CREATE DATABASE IF NOT EXISTS torres_corregida1 CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE torres_corregida1;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- LIMPIEZA PREVIA (en orden inverso a las FK)
-- ============================================================
DROP TABLE IF EXISTS historico_categorias;
DROP TABLE IF EXISTS historico_cargos;
DROP TABLE IF EXISTS historico_oficinas;
DROP TABLE IF EXISTS sesiones_activas;
DROP TABLE IF EXISTS backup_log;
DROP TABLE IF EXISTS incidentes;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS solicitudes_recuperacion;
DROP TABLE IF EXISTS intentos_login;
DROP TABLE IF EXISTS log_sistema;
DROP TABLE IF EXISTS historico_usuario;
DROP TABLE IF EXISTS historico_documentos;
DROP TABLE IF EXISTS historico_sumarios;
DROP TABLE IF EXISTS historico_historial_legajos;
DROP TABLE IF EXISTS historico_antecedente_laboral;
DROP TABLE IF EXISTS historico_familiar;
DROP TABLE IF EXISTS historico_idiomas;
DROP TABLE IF EXISTS historico_cursos;
DROP TABLE IF EXISTS historico_titulos;
DROP TABLE IF EXISTS historico_legajos;
DROP TABLE IF EXISTS historico_personas;
DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS sumarios;
DROP TABLE IF EXISTS historial_legajos;
DROP TABLE IF EXISTS antecedente_laboral;
DROP TABLE IF EXISTS familiar;
DROP TABLE IF EXISTS idiomas;
DROP TABLE IF EXISTS cursos;
DROP TABLE IF EXISTS titulos;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS legajos;
DROP TABLE IF EXISTS personas;
DROP TABLE IF EXISTS oficinas;
DROP TABLE IF EXISTS cargos;
DROP TABLE IF EXISTS categorias;

-- ============================================================
-- TABLAS MAESTRAS / CATALOGOS
-- ============================================================

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(200)
);

CREATE TABLE cargos (
    id_cargo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cargo VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(200)
);

CREATE TABLE oficinas (
    id_oficina INT AUTO_INCREMENT PRIMARY KEY,
    nombre_oficina VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(200),
    ubicacion VARCHAR(200)
);

-- ============================================================
-- TABLAS PRINCIPALES
-- ============================================================

CREATE TABLE personas (
    id_persona INT(8) AUTO_INCREMENT PRIMARY KEY,
    dni CHAR(8) NOT NULL UNIQUE,
    apellido VARCHAR(50) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    genero ENUM('masculino', 'femenino', 'sin_determinar'),
    fecha_nacimiento DATE NOT NULL,
    estado_civil ENUM('soltero/a', 'viudo/a', 'casado/a', 'concubinato') NOT NULL,
    cantidad_hijos INT NOT NULL DEFAULT 0 CHECK (cantidad_hijos >= 0),
    provincia_residencia VARCHAR(50) NOT NULL,
    ciudad_residencia VARCHAR(50) NOT NULL,
    domicilio_datos VARCHAR(300),
    cuil VARCHAR(13) UNIQUE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    telefono_emergencia VARCHAR(20) NOT NULL,
    email VARCHAR(50),
    FULLTEXT INDEX ft_personas_busqueda (apellido, nombre, dni, cuil)
);

CREATE TABLE legajos (
    id_legajo INT(8) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ingreso DATE,
    fecha_ingreso_administracion DATE,
    id_cargo INT,
    id_categoria INT,
    id_oficina INT,
    tipo_contrato ENUM('locacion', 'permanente', 'funcionario'),
    estado ENUM('activo', 'de_baja', 'traslado', 'prestamo') DEFAULT 'activo',
    id_persona INT(8) NOT NULL,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria),
    FOREIGN KEY (id_cargo) REFERENCES cargos(id_cargo),
    FOREIGN KEY (id_oficina) REFERENCES oficinas(id_oficina)
);

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    -- usuario = id_legajo (generado automaticamente por trigger al crear legajo)
    -- pass    = SHA2(dni, 256) (generado automaticamente por trigger)
    usuario VARCHAR(50) NOT NULL UNIQUE,
    pass VARCHAR(255) NOT NULL,
    tipo ENUM('funcionario', 'rrhh', 'empleado', 'administrador') NOT NULL,
    id_legajo INT(8) NOT NULL UNIQUE,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo)
);

CREATE TABLE titulos (
    id_titulo INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT(8) NOT NULL,
    nombre_titulo VARCHAR(200) NOT NULL,
    institucion VARCHAR(200),
    fecha_inicio DATE,
    fecha_fin DATE,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona)
);

CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT(8) NOT NULL,
    nombre_curso VARCHAR(200) NOT NULL,
    institucion VARCHAR(200),
    fecha_inicio DATE,
    horas INT CHECK (horas > 0),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona)
);

CREATE TABLE idiomas (
    id_idioma INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT(8) NOT NULL,
    nombre ENUM('Inglés','Italiano','Portugués','Francés','Alemán','Español','Coreano','Japonés','Chino Mandarín') NOT NULL,
    nivel ENUM('basico', 'intermedio', 'avanzado', 'nativo') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona)
);

CREATE TABLE familiar (
    id_familiar INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT(8) NOT NULL,
    relacion_empleado ENUM('padre', 'madre', 'hijo/a', 'conyuge', 'suegro/a', 'hermano/a', 'otro') NOT NULL,
    apellido_familiar VARCHAR(50) NOT NULL,
    nombre_familiar VARCHAR(50) NOT NULL,
    dni_familiar CHAR(8),
    fecha_nacimiento_familiar DATE,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona)
);

CREATE TABLE antecedente_laboral (
    id_antecedente INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT(8) NOT NULL,
    empresa VARCHAR(200) NOT NULL,
    cargo_ocupado VARCHAR(200),
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona)
);

CREATE TABLE historial_legajos (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accion ENUM('advertencia', 'llamada_atencion', 'suspension', 'cambio_de_funcion',
                'sumario', 'traslado', 'vencimiento_contrato', 'jubilacion',
                'renuncia', 'difunto', 'incapacidad', 'licencia') NOT NULL,
    detalle VARCHAR(300),
    id_legajo INT(8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo)
);

CREATE TABLE sumarios (
    id_sumario INT AUTO_INCREMENT PRIMARY KEY,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detalle VARCHAR(300),
    id_legajo INT(8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo)
);

CREATE TABLE documentos (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT(8) NOT NULL,
    tipo_doc ENUM('DNI','TITULO','CURSOS','LICENCIA','ACTA_DE_NACIMIENTO',
                  'CERTIFICADO_ESCOLARIDAD','CERTIFICADO_DEFUNCION','SUMARIO',
                  'RESOLUCION','CERTIFICADO_DE_CASAMIENTO','FOTO_PERFIL',
                  'CURRICULUM','OTRO') NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    hash_archivo VARCHAR(64),
    mime_type VARCHAR(100),
    extension VARCHAR(10),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona)
);

-- ============================================================
-- TABLAS HISTORICAS DE LAS PRINCIPALES
-- (guarda el estado anterior en cada INSERT / UPDATE)
-- ============================================================

CREATE TABLE historico_personas (
    id_historico_persona INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT(8),
    dni CHAR(8),
    apellido VARCHAR(50),
    nombre VARCHAR(50),
    genero VARCHAR(15),
    fecha_nacimiento DATE,
    estado_civil VARCHAR(15),
    cantidad_hijos INT,
    provincia_residencia VARCHAR(50),
    ciudad_residencia VARCHAR(50),
    domicilio_datos VARCHAR(300),
    cuil VARCHAR(13),
    telefono VARCHAR(20),
    telefono_emergencia VARCHAR(20),
    email VARCHAR(50),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_legajos (
    id_historico_legajo INT AUTO_INCREMENT PRIMARY KEY,
    id_legajo INT(8),
    id_persona INT(8),
    fecha_ingreso DATE,
    fecha_ingreso_administracion DATE,
    id_cargo INT,
    id_categoria INT,
    id_oficina INT,
    tipo_contrato VARCHAR(20),
    estado VARCHAR(20),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_titulos (
    id_historico_titulo INT AUTO_INCREMENT PRIMARY KEY,
    id_titulo INT,
    id_persona INT(8),
    nombre_titulo VARCHAR(200),
    institucion VARCHAR(200),
    fecha_inicio DATE,
    fecha_fin DATE,
    activo TINYINT(1),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_cursos (
    id_historico_curso INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT,
    id_persona INT(8),
    nombre_curso VARCHAR(200),
    institucion VARCHAR(200),
    fecha_inicio DATE,
    horas INT,
    activo TINYINT(1),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_idiomas (
    id_historico_idioma INT AUTO_INCREMENT PRIMARY KEY,
    id_idioma INT,
    id_persona INT(8),
    nombre VARCHAR(50),
    nivel VARCHAR(20),
    activo TINYINT(1),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_familiar (
    id_historico_familiar INT AUTO_INCREMENT PRIMARY KEY,
    id_familiar INT,
    id_persona INT(8),
    relacion_empleado VARCHAR(20),
    apellido_familiar VARCHAR(50),
    nombre_familiar VARCHAR(50),
    dni_familiar CHAR(8),
    fecha_nacimiento_familiar DATE,
    activo TINYINT(1),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_antecedente_laboral (
    id_historico_antecedente INT AUTO_INCREMENT PRIMARY KEY,
    id_antecedente INT,
    id_persona INT(8),
    empresa VARCHAR(200),
    cargo_ocupado VARCHAR(200),
    fecha_inicio DATE,
    fecha_fin DATE,
    activo TINYINT(1),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_historial_legajos (
    id_historico_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_historial INT,
    id_legajo INT(8),
    accion VARCHAR(50),
    detalle VARCHAR(300),
    activo TINYINT(1),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_sumarios (
    id_historico_sumario INT AUTO_INCREMENT PRIMARY KEY,
    id_sumario INT,
    id_legajo INT(8),
    detalle VARCHAR(300),
    activo TINYINT(1),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_documentos (
    id_historico_documento INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT,
    id_persona INT(8),
    tipo_doc VARCHAR(50),
    nombre_archivo VARCHAR(255),
    ruta_archivo VARCHAR(500),
    activo TINYINT(1),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_usuario (
    id_historico_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_legajo INT(8),
    usuario VARCHAR(50),
    tipo VARCHAR(20),
    activo TINYINT(1),
    fecha_creacion DATE,
    ultimo_login TIMESTAMP,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

-- ============================================================
-- TABLAS DE SISTEMA / ALERTAS / SEGURIDAD
-- ============================================================

CREATE TABLE log_sistema (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT,
    usuario_nombre VARCHAR(50),
    tipo_usuario VARCHAR(20),
    ip VARCHAR(45),
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50),
    id_registro INT,
    detalle TEXT,
    resultado ENUM('exitoso', 'fallido', 'bloqueado') NOT NULL DEFAULT 'exitoso',
    duracion_ms INT
);

CREATE TABLE intentos_login (
    id_intento INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    ip VARCHAR(45),
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resultado ENUM('exitoso', 'fallido') NOT NULL,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    bloqueado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_bloqueo DATETIME,
    fecha_desbloqueo DATETIME
);

CREATE TABLE solicitudes_recuperacion (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'atendida', 'rechazada') NOT NULL DEFAULT 'pendiente',
    id_admin_atiende INT,
    fecha_atencion DATETIME,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE sesiones_activas (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    ip VARCHAR(45),
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_actividad DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo_evento ENUM('login', 'logout', 'cambio_pass', 'creacion_usuario',
                     'baja_legajo', 'reactivacion_legajo', 'backup',
                     'bloqueo_usuario', 'desbloqueo_usuario', 'recuperacion_pass') NOT NULL,
    id_usuario INT,
    usuario_nombre VARCHAR(50),
    tipo_usuario VARCHAR(20),
    ip VARCHAR(45),
    tabla_afectada VARCHAR(50),
    id_registro INT,
    resultado ENUM('exitoso', 'fallido') NOT NULL DEFAULT 'exitoso',
    detalle TEXT
);

CREATE TABLE incidentes (
    id_incidente INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo_incidente ENUM('acceso_no_autorizado', 'multiples_intentos_fallidos',
                        'usuario_bloqueado', 'error_sistema', 'otro') NOT NULL,
    nivel_severidad ENUM('bajo', 'medio', 'alto', 'critico') NOT NULL DEFAULT 'medio',
    id_usuario INT,
    usuario_nombre VARCHAR(50),
    ip VARCHAR(45),
    descripcion TEXT,
    estado ENUM('abierto', 'en_revision', 'resuelto', 'cerrado') NOT NULL DEFAULT 'abierto',
    resolucion TEXT
);

CREATE TABLE backup_log (
    id_backup INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('diario', 'semanal') NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tamano_kb INT,
    resultado ENUM('exitoso', 'fallido') NOT NULL DEFAULT 'exitoso',
    detalle TEXT
);

-- ============================================================
-- HISTORICAS DE MAESTRAS
-- ============================================================

CREATE TABLE historico_categorias (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT,
    nombre_categoria VARCHAR(50),
    descripcion VARCHAR(200),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_cargos (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_cargo INT,
    nombre_cargo VARCHAR(100),
    descripcion VARCHAR(200),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_oficinas (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_oficina INT,
    nombre_oficina VARCHAR(100),
    descripcion VARCHAR(200),
    ubicacion VARCHAR(200),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

-- ============================================================
-- DATOS INICIALES — CATEGORIAS
-- ============================================================
INSERT INTO categorias (nombre_categoria, descripcion) VALUES
('CAT 1',  'SECRETARIO GENERAL - CONTADOR FISCAL'),
('CAT 2',  'JEFE DE RENDICION'),
('CAT 3',  'AUDITOR SENIOR - SIN DENOMINACION'),
('CAT 4',  'AUDITOR SEMIJUNIOR - SIN DENOMINACION (SIN TITULO)'),
('CAT 5',  'JEFE DE DESPACHO - AUDITOR JUNIOR'),
('CAT 6',  'OPERADOR'),
('CAT 7',  'JEFE DE SECCION'),
('CAT 8',  'AUXILIAR - ADMINISTRATIVO A - REVISOR A'),
('CAT 9',  'AUXILIAR - ADMINISTRATIVO B - REVISOR B'),
('CAT 10', 'ADMINISTRATIVO C - REVISOR C'),
('CAT 11', 'MAYORDOMO'),
('CAT 12', 'ORDENANZA - CHOFER - SIN TITULO');

-- ============================================================
-- DATOS INICIALES — USUARIO ADMINISTRADOR DEL SISTEMA
-- Usuario: admin_torres / Clave: Admin12345! (cambiar en primer ingreso)
-- ============================================================
INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento, estado_civil,
    cantidad_hijos, provincia_residencia, ciudad_residencia, domicilio_datos,
    cuil, telefono, telefono_emergencia, email)
VALUES ('00000000', 'ADMINISTRADOR', 'SISTEMA', 'sin_determinar', '1985-01-01', 'soltero/a',
    0, 'SANTIAGO DEL ESTERO', 'SANTIAGO DEL ESTERO', 'SIN DATOS',
    '20-00000000-0', '0000000000', '0000000000', 'admin@torres.gob.ar');

INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion, tipo_contrato, estado, id_persona)
VALUES ('2020-01-01', '2020-01-01', 'funcionario', 'activo',
    (SELECT id_persona FROM personas WHERE dni = '00000000'));

INSERT INTO usuario (usuario, pass, tipo, id_legajo, primer_ingreso)
VALUES (
    CONVERT((SELECT id_legajo FROM legajos WHERE id_persona = (SELECT id_persona FROM personas WHERE dni = '00000000')), CHAR),
    SHA2('Admin12345!', 256),
    'administrador',
    (SELECT id_legajo FROM legajos WHERE id_persona = (SELECT id_persona FROM personas WHERE dni = '00000000')),
    1
);

SET FOREIGN_KEY_CHECKS = 1;
