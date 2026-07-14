-- ============================================================
-- SISTEMA TORRES_CORREGIDA1 — ROLES Y PERMISOS
-- ============================================================
--
-- ESTRUCTURA DE ROLES:
-- ============================================================
--
-- [rol_administrador] → usr_administrador
--   Acceso total. Puede:
--   - Consultar y modificar TODAS las tablas principales,
--     historicas y de sistema/alertas
--   - Crear usuarios de cualquier tipo
--     (funcionario / rrhh / empleado / administrador)
--   - Cambiar el tipo de cualquier usuario
--   - Dar de baja y reactivar cualquier legajo
--   - Desbloquear usuarios bloqueados
--   - Atender solicitudes de recuperacion de clave
--   - Ver y gestionar log, eventos e incidentes
--   - Generar y listar backups
--   - Ejecutar TODOS los stored procedures
--
-- [rol_rrhh] → usr_rrhh
--   Gestion de personal. Puede:
--   - Consultar TODAS las tablas principales e historicas
--   - Ingresar y modificar tablas principales
--     (personas, legajos, titulos, cursos, idiomas,
--      familiar, antecedentes, historial, sumarios)
--   - Dar de baja y reactivar legajos
--   - Crear usuarios tipo 'empleado'
--   - Atender solicitudes de recuperacion de clave
--   - Insertar en log_sistema, eventos, incidentes
--   - Ejecutar sp_alta_completa, sp_consulta_*, sp_listado_*,
--     sp_insertar_*, sp_dar_baja_legajo, sp_reactivar_legajo,
--     sp_login, sp_cambiar_pass, sp_solicitar_recuperacion,
--     sp_atender_recuperacion
--
-- [rol_funcionario] → usr_funcionario
--   Solo lectura. Puede:
--   - Consultar TODAS las tablas principales e historicas
--   - Ejecutar sp_listado_empleados, sp_consulta_*,
--     sp_login, sp_cambiar_pass, sp_solicitar_recuperacion
--   - NO puede modificar ningun dato
--
-- [rol_empleado] → usr_empleado
--   Acceso solo a sus propios datos. Puede:
--   - Ejecutar sp_mi_informacion (filtra por su id_usuario)
--   - Ejecutar sp_login, sp_cambiar_pass, sp_solicitar_recuperacion
--   - NO puede ver datos de otros empleados
--
-- ============================================================

USE torres_corregida1;

-- ============================================================
-- CREACION DE ROLES
-- ============================================================
DROP ROLE IF EXISTS 'rol_administrador';
DROP ROLE IF EXISTS 'rol_rrhh';
DROP ROLE IF EXISTS 'rol_funcionario';
DROP ROLE IF EXISTS 'rol_empleado';

CREATE ROLE 'rol_administrador';
CREATE ROLE 'rol_rrhh';
CREATE ROLE 'rol_funcionario';
CREATE ROLE 'rol_empleado';

-- ============================================================
-- PERMISOS ROL_ADMINISTRADOR
-- Acceso total a tablas y todos los SPs
-- ============================================================

GRANT ALL PRIVILEGES ON torres_corregida1.* TO 'rol_administrador';

-- ============================================================
-- PERMISOS ROL_RRHH
-- Consulta de todas las tablas + modificacion de principales
-- ============================================================

-- Tablas maestras (solo consulta)
GRANT SELECT ON torres_corregida1.categorias TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.cargos TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.oficinas TO 'rol_rrhh';

-- Tablas principales (consulta + insercion + modificacion)
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.personas TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.legajos TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.usuario TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.titulos TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.cursos TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.idiomas TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.familiar TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.antecedente_laboral TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.historial_legajos TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.sumarios TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.documentos TO 'rol_rrhh';

-- Tablas historicas (solo consulta)
GRANT SELECT ON torres_corregida1.historico_personas TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_legajos TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_titulos TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_cursos TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_idiomas TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_familiar TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_antecedente_laboral TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_historial_legajos TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_sumarios TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_documentos TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.historico_usuario TO 'rol_rrhh';

-- Tablas de sistema necesarias para triggers y SPs
GRANT SELECT, INSERT ON torres_corregida1.log_sistema TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.intentos_login TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.solicitudes_recuperacion TO 'rol_rrhh';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.sesiones_activas TO 'rol_rrhh';
GRANT SELECT, INSERT ON torres_corregida1.eventos TO 'rol_rrhh';
GRANT SELECT, INSERT ON torres_corregida1.incidentes TO 'rol_rrhh';

-- SPs para rrhh
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_login TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_cambiar_pass TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_solicitar_recuperacion TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_atender_recuperacion TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_alta_completa TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_persona TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_legajo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_dar_baja_legajo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_reactivar_legajo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_listado_empleados TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_consulta_por_legajo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_consulta_por_dni TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_titulo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_curso TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_idioma TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_familiar TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_antecedente TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_sumario TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_historial TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_y_consultar TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_titulo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_curso TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_idioma TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_familiar TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_antecedente TO 'rol_rrhh';

-- ============================================================
-- PERMISOS ROL_FUNCIONARIO
-- Solo lectura + SPs de consulta
-- ============================================================

-- Tablas maestras (solo consulta)
GRANT SELECT ON torres_corregida1.categorias TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.cargos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.oficinas TO 'rol_funcionario';

-- Tablas principales (solo consulta)
GRANT SELECT ON torres_corregida1.personas TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.legajos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.titulos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.cursos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.idiomas TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.familiar TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.antecedente_laboral TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historial_legajos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.sumarios TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.documentos TO 'rol_funcionario';

-- Tablas historicas (solo consulta)
GRANT SELECT ON torres_corregida1.historico_personas TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_legajos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_titulos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_cursos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_idiomas TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_familiar TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_antecedente_laboral TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_historial_legajos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_sumarios TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_documentos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historico_usuario TO 'rol_funcionario';

-- Tablas de sistema necesarias para login
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.usuario TO 'rol_funcionario';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.intentos_login TO 'rol_funcionario';
GRANT SELECT, INSERT ON torres_corregida1.solicitudes_recuperacion TO 'rol_funcionario';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.sesiones_activas TO 'rol_funcionario';
GRANT INSERT ON torres_corregida1.log_sistema TO 'rol_funcionario';
GRANT INSERT ON torres_corregida1.eventos TO 'rol_funcionario';
GRANT INSERT ON torres_corregida1.incidentes TO 'rol_funcionario';

-- SPs para funcionario
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_login TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_cambiar_pass TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_solicitar_recuperacion TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_listado_empleados TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_consulta_por_legajo TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_consulta_por_dni TO 'rol_funcionario';

-- ============================================================
-- PERMISOS ROL_EMPLEADO
-- Solo sus propios datos via sp_mi_informacion
-- ============================================================

-- Permisos minimos para que los SPs funcionen
GRANT SELECT ON torres_corregida1.personas TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.legajos TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.titulos TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.cursos TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.idiomas TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.familiar TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.antecedente_laboral TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.historial_legajos TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.sumarios TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.categorias TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.cargos TO 'rol_empleado';
GRANT SELECT ON torres_corregida1.oficinas TO 'rol_empleado';

-- Tablas de sistema necesarias para login y cambio de clave
GRANT SELECT, UPDATE ON torres_corregida1.usuario TO 'rol_empleado';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.intentos_login TO 'rol_empleado';
GRANT SELECT, INSERT ON torres_corregida1.solicitudes_recuperacion TO 'rol_empleado';
GRANT SELECT, INSERT, UPDATE ON torres_corregida1.sesiones_activas TO 'rol_empleado';
GRANT INSERT ON torres_corregida1.log_sistema TO 'rol_empleado';
GRANT INSERT ON torres_corregida1.eventos TO 'rol_empleado';
GRANT INSERT ON torres_corregida1.incidentes TO 'rol_empleado';

-- SPs para empleado
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_login TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_cambiar_pass TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_solicitar_recuperacion TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_mi_informacion TO 'rol_empleado';

-- ============================================================
-- CREACION DE USUARIOS MYSQL
-- IMPORTANTE: Cambiar estas contraseñas antes de produccion.
-- Los usuarios tienen PASSWORD EXPIRE para forzar el cambio
-- en el primer uso desde una herramienta de administracion.
-- ============================================================
CREATE USER IF NOT EXISTS 'usr_administrador'@'localhost'
    IDENTIFIED WITH caching_sha2_password BY 'Admin_Torres_2024!'
    PASSWORD EXPIRE;

CREATE USER IF NOT EXISTS 'usr_rrhh'@'localhost'
    IDENTIFIED WITH caching_sha2_password BY 'Rrhh_Torres_2024!'
    PASSWORD EXPIRE;

CREATE USER IF NOT EXISTS 'usr_funcionario'@'localhost'
    IDENTIFIED WITH caching_sha2_password BY 'Func_Torres_2024!'
    PASSWORD EXPIRE;

CREATE USER IF NOT EXISTS 'usr_empleado'@'localhost'
    IDENTIFIED WITH caching_sha2_password BY 'Empl_Torres_2024!'
    PASSWORD EXPIRE;

-- ============================================================
-- ASIGNACION DE ROLES A USUARIOS
-- ============================================================
GRANT 'rol_administrador' TO 'usr_administrador'@'localhost';
GRANT 'rol_rrhh'          TO 'usr_rrhh'@'localhost';
GRANT 'rol_funcionario'   TO 'usr_funcionario'@'localhost';
GRANT 'rol_empleado'      TO 'usr_empleado'@'localhost';

-- Activar roles por defecto al iniciar sesion
SET DEFAULT ROLE 'rol_administrador' TO 'usr_administrador'@'localhost';
SET DEFAULT ROLE 'rol_rrhh'          TO 'usr_rrhh'@'localhost';
SET DEFAULT ROLE 'rol_funcionario'   TO 'usr_funcionario'@'localhost';
SET DEFAULT ROLE 'rol_empleado'      TO 'usr_empleado'@'localhost';

FLUSH PRIVILEGES;
