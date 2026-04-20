CREATE DATABASE legajos_tcse;
USE legajos_tcse;

CREATE TABLE persona (
	id_persona INT UNIQUE NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dni CHAR(12),
    apellido VARCHAR(30) NOT NULL,
    nombre VARCHAR(30) NOT NULL,
    fechanac DATE,
    domicilio VARCHAR(100),
    cuil VARCHAR(11) UNIQUE,
    telefono VARCHAR(20),
    telefono_emergencia VARCHAR(20),
    email VARCHAR(50)
);

CREATE TABLE estudio (
	id_estudio INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    institucion VARCHAR(100),
    fecha_inicio DATE,
    fecha_fin DATE,
    id_persona INT NOT NULL,
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
);

CREATE TABLE curso (
	id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    institucion VARCHAR(100),
    fecha DATE,
    horas INT,
    id_persona INT NOT NULL,
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
);

CREATE TABLE idioma (
	id_idioma INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    nivel VARCHAR(20),
    id_persona INT NOT NULL,
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
);

CREATE TABLE usuario (
	id_usuario INT UNIQUE NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50),
    pass VARCHAR(20) NOT NULL,
    tipo VARCHAR(15) NOT NULL,
    id_persona INT NOT NULL,
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona)
);

CREATE TABLE familiar (
id_familiar INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL,
    dni VARCHAR(12),
    fecha_nac DATE,
    estado VARCHAR(50)
);

CREATE TABLE persona_familiar (
	id_persona INT NOT NULL,
    id_familiar INT NOT NULL,
    parentesco VARCHAR(20) NOT NULL,
    PRIMARY KEY (id_persona, id_familiar),
    FOREIGN KEY (id_persona) REFERENCES persona(id_persona),
    FOREIGN KEY (id_familiar) REFERENCES familiar(id_familiar)
);

CREATE TABLE legajos (
    id_legajos INT UNIQUE NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fecha_ingreso DATE,
    Nres VARCHAR(10),
    cargo VARCHAR(50),
    categoria VARCHAR(3),
    id_persona INT NOT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_persona) references persona(id_persona),
    FOREIGN KEY (id_usuario) references usuario(id_usuario)
);

CREATE TABLE antecedente_laboral (
	id_antecedente INT AUTO_INCREMENT PRIMARY KEY,
    empresa VARCHAR(100),
    cargo VARCHAR(100),
    fecha_inicio DATE,
    fecha_fin DATE,
    id_legajos INT NOT NULL,
    FOREIGN KEY (id_legajos) REFERENCES legajos(id_legajos)
);

CREATE TABLE documentos (
    id_documentos INT UNIQUE NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipodoc VARCHAR(3),
    fechasubida DATE,
    estado VARCHAR(20),
    descripcion VARCHAR(50),
    id_legajos INT NOT NULL,
    FOREIGN KEY (id_legajos) REFERENCES legajos(id_legajos)
);

CREATE TABLE historial_legajo (
	id_historial INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL,
    accion VARCHAR(50) NOT NULL,
    detalle VARCHAR(255),
    id_legajos INT NOT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_legajos) REFERENCES legajos(id_legajos),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);