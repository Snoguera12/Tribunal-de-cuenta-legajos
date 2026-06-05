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
    cantidad_hijos INT NOT NULL DEFAULT 0 CHECK (cantidad_hijos >= 0),
    provincia_residencia VARCHAR(50) NOT NULL, 
    ciudad_residencia VARCHAR(50) NOT NULL,
    domicilio_datos VARCHAR(300),    
    cuil VARCHAR(13) UNIQUE NOT NULL, 
    telefono VARCHAR(20) NOT NULL,
    telefono_emergencia VARCHAR(20) NOT NULL,
    email VARCHAR(50),
    primer_ingreso TINYINT(1) NOT NULL ,
   activo TINYINT(1) NOT NULL DEFAULT 1
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

CREATE TABLE  usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    pass VARCHAR(255) NOT NULL,
    tipo ENUM('funcionario', 'rrhh', 'empleado') NOT NULL,
    id_legajo INT(8) NOT NULL UNIQUE,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
    );

CREATE TABLE titulos (
    id_titulo INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    nivel_estudio ENUM('primaria', 'secundaria', 'terciario', 'universitario', 'doctorado', 'maestria', 'sin_estudios'),
    institucion VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    id_legajo INT (8) NOT NULL,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);
    
CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    institucion VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    horas INT,
    id_legajo INT (8) NOT NULL,
	primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);
    
CREATE TABLE idiomas (
    id_idioma INT AUTO_INCREMENT PRIMARY KEY,
    nombre ENUM('Inglés','Italiano','Portugués','Francés','Alemán','Español','Coreano','Japonés','Chino Mandarín') NOT NULL,
    nivel ENUM('Principiante (A1-A2)', 'Intermedio (B1-B2)', 'Avanzado (C1-C2)', 'Nativo') NOT NULL,
    id_legajo INT (8) NOT NULL,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
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
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);
    
CREATE TABLE antecedente_laboral (
    id_antecedente INT AUTO_INCREMENT PRIMARY KEY,
    empresa VARCHAR(100),
    cargo VARCHAR(100),
    fecha_inicio DATE,
    fecha_fin DATE,
    id_legajo INT (8) NOT NULL,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);

CREATE TABLE historial_legajos (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accion ENUM('advertencia', 'llamada_atencion', 'suspension', 'cambio_de_funcion', 'sumario', 'traslado', 'vencimiento_contrato', 'jubilacion', 'renuncia', 'difunto', 'incapacidad', 'licencia') NOT NULL,
    detalle VARCHAR(300),
    id_legajo INT (8) NOT NULL,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);

CREATE TABLE sumarios (
    id_sumario INT AUTO_INCREMENT PRIMARY KEY,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detalle VARCHAR(300),
    id_legajo INT (8) NOT NULL,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
    FOREIGN KEY (id_legajo) REFERENCES legajos(id_legajo) 
);
    
CREATE TABLE documentos (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    tipo_doc ENUM('DNI', 'TITULO', 'CURSOS', 'LICENCIA', 'ACTA_DE_NACIMIENTO', 'CERTIFICADO_ESCOLARIDAD', 'CERTIFICADO_DEFUNCION', 'SUMARIO', 'RESOLUCION', 'CERTIFICADO_DE_CASAMIENTO', 'FOTO_PERFIL', 'CURRICULUM', 'OTRO') NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    descripcion TEXT,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tamano_archivo INT(11) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    hash_archivo VARCHAR(64) NOT NULL,
    id_legajo INT (8) NOT NULL,
    primer_ingreso TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL ,
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
    id_historico_persona INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT (8),
    dni CHAR(8) ,
    apellido VARCHAR(50) ,
    nombre VARCHAR(50) ,
    genero VARCHAR(15),
    fecha_nacimiento DATE,
    estado_civil VARCHAR(15),
    cantidad_hijos INT ,
    provincia_residencia VARCHAR(50) , 
    ciudad_residencia VARCHAR(50) ,
    domicilio_datos VARCHAR(300),    
    cuil VARCHAR(13) , 
    telefono VARCHAR(20),
    telefono_emergencia VARCHAR(20) ,
    email VARCHAR(50),   
	fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_legajos (
    id_historico_legajo INT AUTO_INCREMENT PRIMARY KEY,
    id_legajo INT (8) ,
    fecha_registro date,
     fecha_ingreso DATE, 
    fecha_ingreso_administracion DATE,
    id_cargo INT,
    id_categoria INT,
    id_oficina INT,
    tipo_contrato VARCHAR(20),
    estado VARCHAR(20),    
    id_persona int (8) ,
	fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_titulos (
    id_historico_titulo INT AUTO_INCREMENT PRIMARY KEY,
    id_titulo INT ,
    titulo VARCHAR(100),
    nivel_estudio varchar (30),
    institucion VARCHAR(100) ,
    fecha_inicio DATE ,
    fecha_fin DATE ,
    id_legajo INT (8), 
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_cursos (
    id_historico_curso INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT ,
    nombre VARCHAR(100) ,
    institucion VARCHAR(100) ,
    fecha_inicio DATE ,
    horas INT,
    id_legajo INT (8) ,
     fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_idiomas (
    id_historico_idioma INT AUTO_INCREMENT PRIMARY KEY,
    id_idioma INT ,
    nombre varchar (25),
    nivel varchar (25),
    id_legajo INT (8),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_familiar (
    id_historico_familiar INT AUTO_INCREMENT PRIMARY KEY,
    id_familiar INT ,
    nombre_familiar VARCHAR(50) ,
    apellido_familiar VARCHAR(50) ,
    dni_familiar CHAR(8) , 
    fecha_nac_familiar DATE ,
    estado varchar (20),
    relacion_empleado varchar (20),
    id_legajo INT (8) NOT NULL,
     fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_antecedente_laboral (
    id_historico_laboral INT AUTO_INCREMENT PRIMARY KEY,
   id_antecedente INT ,
    empresa VARCHAR(100),
    cargo VARCHAR(100),
    fecha_inicio DATE,
    fecha_fin DATE,
    id_legajo INT (8),  
   fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_historial_legajos (
    id_historico_historial_legajo INT AUTO_INCREMENT PRIMARY KEY,
    id_historial INT ,
    fecha_registro date,
    accion varchar (25),
    detalle VARCHAR(300),
    id_legajo INT (8),
     fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_sumarios (
    id_historico_sumario INT AUTO_INCREMENT PRIMARY KEY,
    id_sumario INT ,
    fecha_registro date,
    id_legajo INT (8) ,    
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_documentos (
    id_historico_documento INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT ,
    tipo_doc varchar (30),
    creado_en date,
    descripcion TEXT,
    nombre_archivo VARCHAR(255) ,
    ruta_archivo VARCHAR(500) ,
    tamano_archivo INT(11) ,
    mime_type VARCHAR(100) ,
    extension VARCHAR(10) ,
    hash_archivo VARCHAR(64) ,
    id_legajo INT (8),  
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

