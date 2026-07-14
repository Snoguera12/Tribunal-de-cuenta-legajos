USE torres_corregida1;

-- ============================================================
-- SISTEMA TORRES_CORREGIDA1 — DATOS DE PRUEBA
-- ============================================================
--
-- CONTENIDO:
--   - 5 cargos de ejemplo
--   - 3 oficinas de ejemplo
--   - 6 personas con sus legajos y datos completos
--   - Titulos, cursos, idiomas, familiares, antecedentes
--   - 1 usuario rrhh, 1 funcionario, 4 empleados
--   - 1 sumario de ejemplo
--   - 1 historial de legajo de ejemplo
--
-- USUARIOS CREADOS AUTOMATICAMENTE POR TRIGGER:
--   usuario = id_legajo (numero), pass inicial = SHA2(dni)
--   El administrador (id_legajo=1) ya fue creado en 01_tablas
--
-- NOTA: Ejecutar DESPUES de los 5 archivos anteriores.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- CARGOS DE EJEMPLO
-- ============================================================
INSERT INTO cargos (nombre_cargo, descripcion) VALUES
('CONTADOR GENERAL',       'Responsable de la contabilidad general del organismo'),
('TECNICO ADMINISTRATIVO', 'Soporte administrativo y gestion de documentos'),
('AUDITOR',                'Control y auditoria de procesos internos'),
('CHOFER OFICIAL',         'Conductor de vehiculos oficiales del organismo'),
('ORDENANZA',              'Tareas generales de mantenimiento y mensajeria');

-- ============================================================
-- OFICINAS DE EJEMPLO
-- ============================================================
INSERT INTO oficinas (nombre_oficina, descripcion, ubicacion) VALUES
('SECRETARIA GENERAL',    'Oficina principal de secretaria',     'Planta Baja - Ala Norte'),
('CONTADURIA',            'Departamento de contabilidad y pagos', 'Primer Piso - Ala Sur'),
('AUDITORIA INTERNA',     'Control interno y auditoria',          'Segundo Piso'),
('RECURSOS HUMANOS',      'Gestion de personal',                  'Planta Baja - Ala Sur'),
('MESA DE ENTRADAS',      'Recepcion y despacho de expedientes',  'Planta Baja - Frente');

-- ============================================================
-- PERSONAS Y LEGAJOS DE PRUEBA
-- El trigger trg_crear_usuario_al_crear_legajo crea el usuario
-- automaticamente con usuario=id_legajo y pass=SHA2(dni)
-- ============================================================

-- Persona 1: RRHH
INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento, estado_civil,
    cantidad_hijos, provincia_residencia, ciudad_residencia, domicilio_datos,
    cuil, telefono, telefono_emergencia, email)
VALUES ('30456789', 'GONZALEZ', 'MARIA LAURA', 'femenino', '1985-03-15', 'casado/a',
    2, 'SANTIAGO DEL ESTERO', 'SANTIAGO DEL ESTERO', 'AV. BELGRANO 1234 - PISO 2',
    '27-30456789-4', '3854123456', '3854987654', 'mgonzalez@torres.gob.ar');

INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion,
    id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona)
VALUES ('2010-03-01', '2010-03-01', 2, 8, 4, 'permanente', 'activo',
    (SELECT id_persona FROM personas WHERE dni = '30456789'));

-- Cambiar tipo a rrhh (el trigger crea como empleado, admin lo cambia despues)
UPDATE usuario SET tipo = 'rrhh'
WHERE id_legajo = (SELECT id_legajo FROM legajos
    WHERE id_persona = (SELECT id_persona FROM personas WHERE dni = '30456789'));

-- Persona 2: Funcionario
INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento, estado_civil,
    cantidad_hijos, provincia_residencia, ciudad_residencia, domicilio_datos,
    cuil, telefono, telefono_emergencia, email)
VALUES ('25789012', 'RODRIGUEZ', 'CARLOS ANDRES', 'masculino', '1978-07-22', 'soltero/a',
    0, 'SANTIAGO DEL ESTERO', 'LA BANDA', 'CALLE SAN MARTIN 567',
    '20-25789012-8', '3854234567', '3854876543', 'crodriguez@torres.gob.ar');

INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion,
    id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona)
VALUES ('2005-08-15', '2005-08-15', 3, 5, 3, 'permanente', 'activo',
    (SELECT id_persona FROM personas WHERE dni = '25789012'));

UPDATE usuario SET tipo = 'funcionario'
WHERE id_legajo = (SELECT id_legajo FROM legajos
    WHERE id_persona = (SELECT id_persona FROM personas WHERE dni = '25789012'));

-- Persona 3: Empleado activo con datos completos
INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento, estado_civil,
    cantidad_hijos, provincia_residencia, ciudad_residencia, domicilio_datos,
    cuil, telefono, telefono_emergencia, email)
VALUES ('35123456', 'PEREZ', 'ANA SOFIA', 'femenino', '1992-11-08', 'soltero/a',
    0, 'SANTIAGO DEL ESTERO', 'SANTIAGO DEL ESTERO', 'MITRE 890 - DPTO 3B',
    '27-35123456-2', '3854345678', '3854765432', 'aperez@torres.gob.ar');

INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion,
    id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona)
VALUES ('2018-02-01', '2018-02-01', 2, 9, 2, 'permanente', 'activo',
    (SELECT id_persona FROM personas WHERE dni = '35123456'));

-- Persona 4: Empleado activo
INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento, estado_civil,
    cantidad_hijos, provincia_residencia, ciudad_residencia, domicilio_datos,
    cuil, telefono, telefono_emergencia, email)
VALUES ('28654321', 'LOPEZ', 'ROBERTO DANIEL', 'masculino', '1980-05-30', 'casado/a',
    3, 'SANTIAGO DEL ESTERO', 'SANTIAGO DEL ESTERO', 'CORRIENTES 2345',
    '20-28654321-6', '3854456789', '3854654321', 'rlopez@torres.gob.ar');

INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion,
    id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona)
VALUES ('2012-06-15', '2012-06-15', 4, 12, 1, 'permanente', 'activo',
    (SELECT id_persona FROM personas WHERE dni = '28654321'));

-- Persona 5: Empleado con locacion de servicios
INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento, estado_civil,
    cantidad_hijos, provincia_residencia, ciudad_residencia, domicilio_datos,
    cuil, telefono, telefono_emergencia, email)
VALUES ('40987654', 'MARTINEZ', 'LUCAS EZEQUIEL', 'masculino', '1995-09-12', 'soltero/a',
    0, 'SANTIAGO DEL ESTERO', 'FRÍAS', 'SAN JUAN 456',
    '20-40987654-5', '3854567890', '3854543210', 'lmartinez@torres.gob.ar');

INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion,
    id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona)
VALUES ('2022-01-10', '2022-01-10', 2, 10, 5, 'locacion', 'activo',
    (SELECT id_persona FROM personas WHERE dni = '40987654'));

-- Persona 6: Empleado dado de baja
INSERT INTO personas (dni, apellido, nombre, genero, fecha_nacimiento, estado_civil,
    cantidad_hijos, provincia_residencia, ciudad_residencia, domicilio_datos,
    cuil, telefono, telefono_emergencia, email)
VALUES ('22333444', 'FERNANDEZ', 'PATRICIA ELENA', 'femenino', '1970-12-03', 'viudo/a',
    1, 'SANTIAGO DEL ESTERO', 'SANTIAGO DEL ESTERO', 'TUCUMAN 789',
    '27-22333444-7', '3854678901', '3854432109', 'pfernandez@torres.gob.ar');

INSERT INTO legajos (fecha_ingreso, fecha_ingreso_administracion,
    id_cargo, id_categoria, id_oficina, tipo_contrato, estado, id_persona)
VALUES ('2000-04-01', '2000-04-01', 5, 12, 5, 'permanente', 'activo',
    (SELECT id_persona FROM personas WHERE dni = '22333444'));

-- ============================================================
-- DATOS SECUNDARIOS
-- ============================================================

-- Titulos de Ana Perez
INSERT INTO titulos (id_persona, nombre_titulo, institucion, fecha_inicio, fecha_fin)
SELECT id_persona, 'CONTADOR PUBLICO NACIONAL', 'UNIVERSIDAD NACIONAL DE SANTIAGO DEL ESTERO',
    '2010-03-01', '2015-12-15'
FROM personas WHERE dni = '35123456';

INSERT INTO titulos (id_persona, nombre_titulo, institucion, fecha_inicio, fecha_fin)
SELECT id_persona, 'TECNICATURA EN ADMINISTRACION', 'INSTITUTO SUPERIOR JOSE EVARISTO URIBURU',
    '2008-03-01', '2010-11-30'
FROM personas WHERE dni = '35123456';

-- Titulos de Carlos Rodriguez
INSERT INTO titulos (id_persona, nombre_titulo, institucion, fecha_inicio, fecha_fin)
SELECT id_persona, 'LICENCIADO EN AUDITORIA', 'UNIVERSIDAD NACIONAL DE SANTIAGO DEL ESTERO',
    '1996-03-01', '2002-12-10'
FROM personas WHERE dni = '25789012';

-- Cursos de Ana Perez
INSERT INTO cursos (id_persona, nombre_curso, institucion, fecha_inicio, horas)
SELECT id_persona, 'EXCEL AVANZADO', 'INSTITUTO CAPACITA', '2020-04-01', 40
FROM personas WHERE dni = '35123456';

INSERT INTO cursos (id_persona, nombre_curso, institucion, fecha_inicio, horas)
SELECT id_persona, 'GESTION PUBLICA MUNICIPAL', 'MINISTERIO DE ECONOMIA', '2021-08-15', 60
FROM personas WHERE dni = '35123456';

-- Cursos de Roberto Lopez
INSERT INTO cursos (id_persona, nombre_curso, institucion, fecha_inicio, horas)
SELECT id_persona, 'MANEJO DEFENSIVO', 'AUTOESCUELA PROVINCIAL', '2019-03-10', 20
FROM personas WHERE dni = '28654321';

-- Idiomas
INSERT INTO idiomas (id_persona, nombre, nivel)
SELECT id_persona, 'Inglés', 'intermedio'
FROM personas WHERE dni = '35123456';

INSERT INTO idiomas (id_persona, nombre, nivel)
SELECT id_persona, 'Inglés', 'basico'
FROM personas WHERE dni = '25789012';

INSERT INTO idiomas (id_persona, nombre, nivel)
SELECT id_persona, 'Portugués', 'basico'
FROM personas WHERE dni = '25789012';

-- Familiares
INSERT INTO familiar (id_persona, relacion_empleado, apellido_familiar,
    nombre_familiar, dni_familiar, fecha_nacimiento_familiar)
SELECT id_persona, 'conyuge', 'HERRERA', 'JUAN PABLO', '31987654', '1984-06-20'
FROM personas WHERE dni = '35123456';

INSERT INTO familiar (id_persona, relacion_empleado, apellido_familiar,
    nombre_familiar, dni_familiar, fecha_nacimiento_familiar)
SELECT id_persona, 'conyuge', 'VILLEGAS', 'SANDRA BEATRIZ', '27654123', '1979-09-14'
FROM personas WHERE dni = '28654321';

INSERT INTO familiar (id_persona, relacion_empleado, apellido_familiar,
    nombre_familiar, fecha_nacimiento_familiar)
SELECT id_persona, 'hijo/a', 'LOPEZ', 'VALENTINA', '2010-03-22'
FROM personas WHERE dni = '28654321';

INSERT INTO familiar (id_persona, relacion_empleado, apellido_familiar,
    nombre_familiar, fecha_nacimiento_familiar)
SELECT id_persona, 'hijo/a', 'LOPEZ', 'MATIAS', '2012-11-05'
FROM personas WHERE dni = '28654321';

INSERT INTO familiar (id_persona, relacion_empleado, apellido_familiar,
    nombre_familiar, fecha_nacimiento_familiar)
SELECT id_persona, 'hijo/a', 'LOPEZ', 'IGNACIO', '2015-07-18'
FROM personas WHERE dni = '28654321';

-- Antecedentes laborales
INSERT INTO antecedente_laboral (id_persona, empresa, cargo_ocupado,
    fecha_inicio, fecha_fin)
SELECT id_persona, 'MUNICIPALIDAD DE SANTIAGO DEL ESTERO',
    'ADMINISTRATIVO', '2014-01-01', '2017-12-31'
FROM personas WHERE dni = '35123456';

INSERT INTO antecedente_laboral (id_persona, empresa, cargo_ocupado,
    fecha_inicio, fecha_fin)
SELECT id_persona, 'ESTUDIO CONTABLE GOMEZ & ASOCIADOS',
    'ASISTENTE CONTABLE', '2016-03-01', '2018-01-31'
FROM personas WHERE dni = '35123456';

INSERT INTO antecedente_laboral (id_persona, empresa, cargo_ocupado,
    fecha_inicio, fecha_fin)
SELECT id_persona, 'GOBIERNO DE SANTIAGO DEL ESTERO',
    'AUDITOR JUNIOR', '1998-05-01', '2005-07-31'
FROM personas WHERE dni = '25789012';

-- ============================================================
-- HISTORIAL Y SUMARIOS DE EJEMPLO
-- ============================================================

-- Advertencia en el legajo de Lucas Martinez
INSERT INTO historial_legajos (accion, detalle, id_legajo)
SELECT 'advertencia',
    'Primera advertencia por reiteradas llegadas tarde. Fecha: 15/03/2023',
    l.id_legajo
FROM legajos l INNER JOIN personas p ON p.id_persona = l.id_persona
WHERE p.dni = '40987654';

-- Licencia en el legajo de Roberto Lopez
INSERT INTO historial_legajos (accion, detalle, id_legajo)
SELECT 'licencia',
    'Licencia por enfermedad. Periodo: 01/06/2023 al 15/06/2023',
    l.id_legajo
FROM legajos l INNER JOIN personas p ON p.id_persona = l.id_persona
WHERE p.dni = '28654321';

-- Sumario de ejemplo para Ana Perez
INSERT INTO sumarios (detalle, id_legajo)
SELECT 'Sumario administrativo por uso indebido de equipamiento. Resuelto sin sancion. Fecha: 20/09/2022',
    l.id_legajo
FROM legajos l INNER JOIN personas p ON p.id_persona = l.id_persona
WHERE p.dni = '35123456';

-- ============================================================
-- DAR DE BAJA A PATRICIA FERNANDEZ (persona 6)
-- El trigger tgr_borrado_logico_total desactiva el usuario
-- ============================================================
UPDATE legajos SET estado = 'de_baja'
WHERE id_persona = (SELECT id_persona FROM personas WHERE dni = '22333444');

INSERT INTO sumarios (detalle, id_legajo, activo)
SELECT CONCAT('Baja de legajo registrada. Detalle: Jubilacion por anos de servicio'),
    l.id_legajo, 0
FROM legajos l INNER JOIN personas p ON p.id_persona = l.id_persona
WHERE p.dni = '22333444';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- RESUMEN DE DATOS CARGADOS
-- ============================================================
SELECT 'RESUMEN DE DATOS DE PRUEBA' AS descripcion;
SELECT COUNT(*) AS total_categorias FROM categorias;
SELECT COUNT(*) AS total_cargos FROM cargos;
SELECT COUNT(*) AS total_oficinas FROM oficinas;
SELECT COUNT(*) AS total_personas FROM personas;
SELECT COUNT(*) AS total_legajos FROM legajos;
SELECT COUNT(*) AS total_usuarios FROM usuario;
SELECT
    tipo,
    COUNT(*) AS cantidad,
    SUM(activo) AS activos
FROM usuario
GROUP BY tipo
ORDER BY tipo;
SELECT
    estado,
    COUNT(*) AS cantidad
FROM legajos
GROUP BY estado;
