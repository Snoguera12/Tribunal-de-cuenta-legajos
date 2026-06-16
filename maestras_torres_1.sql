USE torres_corregida1;

-- TABLAS HISTORICAS, TRIGGERS Y STORED PROCEDURES PARA TABLAS MAESTRAS
-- OBJETIVO: Auditar y gestionar categorias, cargos y oficinas

-- ======================================================================
-- TABLAS HISTORICAS
-- ======================================================================
DROP TABLE IF EXISTS historico_categorias;
DROP TABLE IF EXISTS historico_cargos;
DROP TABLE IF EXISTS historico_oficinas;

CREATE TABLE historico_categorias (
    id_historico_categoria INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT,
    nombre_categoria VARCHAR(15),
    descripcion TEXT,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_cargos (
    id_historico_cargo INT AUTO_INCREMENT PRIMARY KEY,
    id_cargo INT,
    nombre_cargo VARCHAR(30),
    descripcion TEXT,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

CREATE TABLE historico_oficinas (
    id_historico_oficina INT AUTO_INCREMENT PRIMARY KEY,
    id_oficina INT,
    nombre_oficina VARCHAR(30),
    descripcion TEXT,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_accion VARCHAR(50),
    tipo_cambio ENUM('INSERT', 'UPDATE', 'DELETE')
);

-- ======================================================================
-- TRIGGERS: categorias
-- ======================================================================
DROP TRIGGER IF EXISTS trg_historico_categorias_insert;
DROP TRIGGER IF EXISTS trg_historico_categorias_update;
DROP TRIGGER IF EXISTS trg_historico_cargos_insert;
DROP TRIGGER IF EXISTS trg_historico_cargos_update;
DROP TRIGGER IF EXISTS trg_historico_oficinas_insert;
DROP TRIGGER IF EXISTS trg_historico_oficinas_update;
DROP PROCEDURE IF EXISTS sp_admin_insertar_categoria;
DROP PROCEDURE IF EXISTS sp_admin_modificar_categoria;
DROP PROCEDURE IF EXISTS sp_admin_insertar_cargo;
DROP PROCEDURE IF EXISTS sp_admin_modificar_cargo;
DROP PROCEDURE IF EXISTS sp_admin_insertar_oficina;
DROP PROCEDURE IF EXISTS sp_admin_modificar_oficina;

DELIMITER //

CREATE TRIGGER trg_historico_categorias_insert
AFTER INSERT ON categorias
FOR EACH ROW
BEGIN
    INSERT INTO historico_categorias (id_categoria, nombre_categoria, descripcion, usuario_accion, tipo_cambio)
    VALUES (NEW.id_categoria, NEW.nombre_categoria, NEW.descripcion, USER(), 'INSERT');
END//

CREATE TRIGGER trg_historico_categorias_update
BEFORE UPDATE ON categorias
FOR EACH ROW
BEGIN
    INSERT INTO historico_categorias (id_categoria, nombre_categoria, descripcion, usuario_accion, tipo_cambio)
    VALUES (OLD.id_categoria, OLD.nombre_categoria, OLD.descripcion, USER(), 'UPDATE');
END//

-- ======================================================================
-- TRIGGERS: cargos
-- ======================================================================

CREATE TRIGGER trg_historico_cargos_insert
AFTER INSERT ON cargos
FOR EACH ROW
BEGIN
    INSERT INTO historico_cargos (id_cargo, nombre_cargo, descripcion, usuario_accion, tipo_cambio)
    VALUES (NEW.id_cargo, NEW.nombre_cargo, NEW.descripcion, USER(), 'INSERT');
END//

CREATE TRIGGER trg_historico_cargos_update
BEFORE UPDATE ON cargos
FOR EACH ROW
BEGIN
    INSERT INTO historico_cargos (id_cargo, nombre_cargo, descripcion, usuario_accion, tipo_cambio)
    VALUES (OLD.id_cargo, OLD.nombre_cargo, OLD.descripcion, USER(), 'UPDATE');
END//

-- ======================================================================
-- TRIGGERS: oficinas
-- ======================================================================

CREATE TRIGGER trg_historico_oficinas_insert
AFTER INSERT ON oficinas
FOR EACH ROW
BEGIN
    INSERT INTO historico_oficinas (id_oficina, nombre_oficina, descripcion, usuario_accion, tipo_cambio)
    VALUES (NEW.id_oficina, NEW.nombre_oficina, NEW.descripcion, USER(), 'INSERT');
END//

CREATE TRIGGER trg_historico_oficinas_update
BEFORE UPDATE ON oficinas
FOR EACH ROW
BEGIN
    INSERT INTO historico_oficinas (id_oficina, nombre_oficina, descripcion, usuario_accion, tipo_cambio)
    VALUES (OLD.id_oficina, OLD.nombre_oficina, OLD.descripcion, USER(), 'UPDATE');
END//

-- ======================================================================
-- STORED PROCEDURES: categorias
-- ======================================================================

CREATE PROCEDURE sp_admin_insertar_categoria(
    IN p_id_usuario_admin INT,
    IN p_nombre_categoria VARCHAR(15),
    IN p_descripcion TEXT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede gestionar categorias.';
    END IF;
    INSERT INTO categorias (nombre_categoria, descripcion) VALUES (p_nombre_categoria, p_descripcion);
END//

CREATE PROCEDURE sp_admin_modificar_categoria(
    IN p_id_usuario_admin INT,
    IN p_id_categoria INT,
    IN p_nombre_categoria VARCHAR(15),
    IN p_descripcion TEXT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede gestionar categorias.';
    END IF;
    UPDATE categorias SET nombre_categoria = p_nombre_categoria, descripcion = p_descripcion
    WHERE id_categoria = p_id_categoria;
END//

-- ======================================================================
-- STORED PROCEDURES: cargos
-- ======================================================================

CREATE PROCEDURE sp_admin_insertar_cargo(
    IN p_id_usuario_admin INT,
    IN p_nombre_cargo VARCHAR(30),
    IN p_descripcion TEXT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede gestionar cargos.';
    END IF;
    INSERT INTO cargos (nombre_cargo, descripcion) VALUES (p_nombre_cargo, p_descripcion);
END//

CREATE PROCEDURE sp_admin_modificar_cargo(
    IN p_id_usuario_admin INT,
    IN p_id_cargo INT,
    IN p_nombre_cargo VARCHAR(30),
    IN p_descripcion TEXT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede gestionar cargos.';
    END IF;
    UPDATE cargos SET nombre_cargo = p_nombre_cargo, descripcion = p_descripcion
    WHERE id_cargo = p_id_cargo;
END//

-- ======================================================================
-- STORED PROCEDURES: oficinas
-- ======================================================================

CREATE PROCEDURE sp_admin_insertar_oficina(
    IN p_id_usuario_admin INT,
    IN p_nombre_oficina VARCHAR(30),
    IN p_descripcion TEXT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede gestionar oficinas.';
    END IF;
    INSERT INTO oficinas (nombre_oficina, descripcion) VALUES (p_nombre_oficina, p_descripcion);
END//

CREATE PROCEDURE sp_admin_modificar_oficina(
    IN p_id_usuario_admin INT,
    IN p_id_oficina INT,
    IN p_nombre_oficina VARCHAR(30),
    IN p_descripcion TEXT
)
BEGIN
    DECLARE v_tipo VARCHAR(20);
    SELECT tipo INTO v_tipo FROM usuario WHERE id_usuario = p_id_usuario_admin AND activo = 1;
    IF v_tipo != 'administrador' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Acceso denegado. Solo el administrador puede gestionar oficinas.';
    END IF;
    UPDATE oficinas SET nombre_oficina = p_nombre_oficina, descripcion = p_descripcion
    WHERE id_oficina = p_id_oficina;
END//

DELIMITER ;
