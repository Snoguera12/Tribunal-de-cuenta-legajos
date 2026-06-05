DROP DATABASE IF EXISTS torres_corregida1;
CREATE DATABASE IF NOT EXISTS torres_corregida1;
USE torres_corregida1;



CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(15) NOT NULL UNIQUE, 
    descripcion TEXT, 
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cargos (
	id_cargo int AUTO_INCREMENT PRIMARY KEY,
    nombre_cargo VARCHAR(30) NOT NULL UNIQUE,
    descripcion TEXT, 
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE oficinas (
	 id_oficina int AUTO_INCREMENT PRIMARY KEY,
    nombre_oficina VARCHAR(30) NOT NULL UNIQUE,
    descripcion TEXT, 
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE personas (
	id_persona INT (8) AUTO_INCREMENT PRIMARY KEY,
    dni CHAR(8) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    genero ENUM('masculino', 'femenino', 'sin_determinar'),
    fecha_nacimiento DATE NOT NULL,
    estado_civil ENUM('soltero/a', 'viudo/a', 'casado/a', 'concubinato') NOT NULL,
    cantidad_hijos INT (2) NOT NULL DEFAULT 0 CHECK (cantidad_hijos >= 0),
    provincia_residencia VARCHAR(50) NOT NULL, 
    ciudad_residencia VARCHAR(50) NOT NULL,
    domicilio_datos VARCHAR(300),    
    cuil VARCHAR(13) UNIQUE NOT NULL, 
    telefono VARCHAR(20) NOT NULL,
    telefono_emergencia VARCHAR(20) NOT NULL,
    email VARCHAR(50),
    eliminado_logico TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE legajos (
    id_legajo INT (8) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ingreso DATE, 
    fecha_ingreso_administracion DATE,
    id_cargo INT,
    id_categoria INT,
    id_oficina INT,
    tipo_contrato ENUM('locacion', 'permanente', 'funcionario'),
    estado ENUM('activo', 'de_baja', 'traslado', 'prestamo') DEFAULT 'activo',    
    id_persona int (8) NOT NULL,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria),
    FOREIGN KEY (id_cargo) REFERENCES cargos(id_cargo), 
    FOREIGN KEY (id_oficina) REFERENCES oficinas(id_oficina)
);

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    pass VARCHAR(255) NOT NULL,
    tipo ENUM('funcionario', 'rrhh', 'empleado') NOT NULL,
    id_legajo INT(8) NOT NULL UNIQUE,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) ON DELETE RESTRICT
);

CREATE TABLE estudio (
    id_estudio INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    nivel_estudio ENUM('primaria', 'secundaria', 'terciario', 'universitario', 'doctorado', 'maestria', 'sin_estudios'),
    institucion VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    id_legajo INT (8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);
    
CREATE TABLE curso (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    institucion VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    horas INT,
    id_legajo INT (8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);
    
CREATE TABLE idioma (
    id_idioma INT AUTO_INCREMENT PRIMARY KEY,
    nombre ENUM('Inglés','Italiano','Portugués','Francés','Alemán','Español','Coreano','Japonés','Chino Mandarín') NOT NULL,
    nivel ENUM('Principiante (A1-A2)', 'Intermedio (B1-B2)', 'Avanzado (C1-C2)', 'Nativo') NOT NULL,
    id_legajo INT (8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);

CREATE TABLE familiar (
    id_familiar INT AUTO_INCREMENT PRIMARY KEY,
    nombre_familiar VARCHAR(50) NOT NULL,
    apellido_familiar VARCHAR(50) NOT NULL,
    dni_familiar CHAR(8) NOT NULL, 
    fecha_nac_familiar DATE NOT NULL,
    estado ENUM('vivo', 'fallecido') NOT NULL,
    relacion_empleado ENUM('padres', 'hijos', 'suegros', 'sobrinos', 'conyuge'),
    id_legajo INT (8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);
    
CREATE TABLE antecedente_laboral (
    id_antecedente INT AUTO_INCREMENT PRIMARY KEY,
    empresa VARCHAR(100),
    cargo VARCHAR(100),
    fecha_inicio DATE,
    fecha_fin DATE,
    id_legajo INT (8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);

CREATE TABLE historial_legajo (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accion ENUM('advertencia', 'llamada_atencion', 'suspension', 'cambio_de_funcion', 'sumario', 'traslado', 'vencimiento_contrato', 'jubilacion', 'renuncia', 'difunto', 'incapacidad', 'licencia') NOT NULL,
    detalle VARCHAR(300),
    id_legajo INT (8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);

CREATE TABLE sumario (
    id_sumario INT AUTO_INCREMENT PRIMARY KEY,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detalle VARCHAR(300),
    id_legajo INT (8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);
    
CREATE TABLE documentos (
    id_documentos INT AUTO_INCREMENT PRIMARY KEY,
    tipodoc ENUM('DNI', 'TITULO', 'CURSOS', 'LICENCIA', 'ACTA_DE_NACIMIENTO', 'CERTIFICADO_ESCOLARIDAD', 'CERTIFICADO_DEFUNCION', 'SUMARIO', 'RESOLUCION', 'CERTIFICADO_DE_CASAMIENTO', 'FOTO_PERFIL', 'CURRICULUM', 'OTRO') NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    descripcion TEXT,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tamano_archivo INT(11) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    hash_archivo VARCHAR(64) NOT NULL,
    id_legajo INT (8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);

INSERT INTO categorias (nombre_categoria, descripcion) VALUES
('CAT 1', 'SECRETARIO GENERAL - CONTADOR FISCAL'),
('CAT 2', 'JEFE DE RENDICION'),
('CAT 3', 'AUDITOR SENIOR - SIN DENOMINACION'),
('CAT 4', 'AUDITOR SEMIJUNIOR - SIN DENOMINACION (SIN TITULO)'),
('CAT 5', 'JEFE DE DESPACHO - AUDITOR JUNIOR'),
('CAT 6', 'OPERADOR'),
('CAT 7', 'JEFE DE SECCION'),
('CAT 8', 'AUXILIAR - ADMINISTRATIVO A - REVISOR A'),
('CAT 9', 'AUXILIAR - ADMINISTRATIVO B - REVISOR B'),
('CAT 10', 'ADMINISTRATIVO C - REVISOR C'),
('CAT 11', 'MAYORDOMO'),
('CAT 12', 'ORDENANZA - CHOFER - SIN TITULO');

CREATE TABLE historico_personas (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT,
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    dni VARCHAR(20),
    fecha_nacimiento DATE,
    email VARCHAR(100),
    telefono VARCHAR(20),
    domicilio VARCHAR(200),
    localidad VARCHAR(100),
    provincia VARCHAR(100),
    pais VARCHAR(50),
    estado_civil VARCHAR(30),
    genero VARCHAR(20),
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_legajos (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_legajo INT,
    id_persona INT,
    numero_legajo VARCHAR(50),
    fecha_ingreso DATE,
    fecha_egreso DATE,
    cargo VARCHAR(100),
    area VARCHAR(100),
    sector VARCHAR(100),
    estado VARCHAR(30),
    observaciones TEXT,
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_estudio (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_estudio INT,
    id_persona INT,
    nivel_estudio VARCHAR(50),
    institucion VARCHAR(150),
    titulo VARCHAR(150),
    fecha_inicio DATE,
    fecha_fin DATE,
    estado VARCHAR(30),
    certificado VARCHAR(100),
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_curso (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT,
    id_persona INT,
    nombre_curso VARCHAR(150),
    institucion VARCHAR(150),
    duracion_horas INT,
    fecha_inicio DATE,
    fecha_fin DATE,
    certificado VARCHAR(100),
    tipo VARCHAR(50),
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_idioma (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_idioma INT,
    id_persona INT,
    idioma VARCHAR(50),
    nivel_oral ENUM('Básico', 'Intermedio', 'Avanzado', 'Nativo'),
    nivel_escrito ENUM('Básico', 'Intermedio', 'Avanzado', 'Nativo'),
    certificacion VARCHAR(100),
    institucion VARCHAR(150),
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_familiar (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_familiar INT,
    id_persona INT,
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    parentesco VARCHAR(50),
    dni VARCHAR(20),
    telefono VARCHAR(20),
    fecha_nacimiento DATE,
    observaciones TEXT,
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_antecedente_laboral (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_antecedente INT,
    id_persona INT,
    empresa VARCHAR(150),
    cargo VARCHAR(100),
    fecha_inicio DATE,
    fecha_fin DATE,
    tareas TEXT,
    telefono_contacto VARCHAR(20),
    motivo_egreso TEXT,
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_historial_legajo (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_historial INT,
    id_legajo INT,
    fecha_movimiento DATE,
    tipo_movimiento VARCHAR(50),
    descripcion TEXT,
    usuario_responsable VARCHAR(100),
    documento_respaldo VARCHAR(200),
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_sumario (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_sumario INT,
    id_legajo INT,
    numero_sumario VARCHAR(50),
    fecha_apertura DATE,
    fecha_cierre DATE,
    cargos TEXT,
    resolucion TEXT,
    sancion VARCHAR(100),
    estado VARCHAR(30),
    instructor VARCHAR(100),
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_documento (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT,
    id_persona INT,
    tipo_documento VARCHAR(50),
    numero_documento VARCHAR(50),
    fecha_emision DATE,
    fecha_vencimiento DATE,
    archivo_path VARCHAR(500),
    observaciones TEXT,
    fecha_archivo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE INDEX idx_hist_personas_id ON historico_personas(id_persona, fecha_archivo);
CREATE INDEX idx_hist_legajos_id ON historico_legajos(id_legajo, fecha_archivo);
CREATE INDEX idx_hist_estudio_persona ON historico_estudio(id_persona, fecha_archivo);
CREATE INDEX idx_hist_curso_persona ON historico_curso(id_persona, fecha_archivo);
CREATE INDEX idx_hist_idioma_persona ON historico_idioma(id_persona, fecha_archivo);
CREATE INDEX idx_hist_familiar_persona ON historico_familiar(id_persona, fecha_archivo);
CREATE INDEX idx_hist_antecedente_persona ON historico_antecedente_laboral(id_persona, fecha_archivo);
CREATE INDEX idx_hist_historial_legajo ON historico_historial_legajo(id_legajo, fecha_archivo);
CREATE INDEX idx_hist_sumario_legajo ON historico_sumario(id_legajo, fecha_archivo);
CREATE INDEX idx_hist_documento_persona ON historico_documento(id_persona, fecha_archivo);