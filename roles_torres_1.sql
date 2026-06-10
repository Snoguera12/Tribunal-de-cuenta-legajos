USE torres_corregida1;

-- LISTADO COMPLETO DE ROLES Y PERMISOS PARA torres_corregida1
-- OBJETIVO: Definir roles nativos de MySQL 8.0 con permisos por nivel de acceso
-- ROLES:    rol_administrador - permisos totales sobre todo el sistema
--           rol_funcionario   - consulta en todas las tablas principales e historicas
--           rol_rrhh          - consulta en todas las tablas, insertar y modificar principales, crear solo empleados
--           rol_empleado      - consulta solo de su propia informacion en tablas principales
-- USUARIOS: usr_administrador, usr_funcionario, usr_rrhh, usr_empleado

-- ======================================================================
-- LIMPIEZA PREVIA
-- ======================================================================
DROP USER IF EXISTS 'usr_administrador'@'localhost';
DROP USER IF EXISTS 'usr_funcionario'@'localhost';
DROP USER IF EXISTS 'usr_rrhh'@'localhost';
DROP USER IF EXISTS 'usr_empleado'@'localhost';
DROP ROLE IF EXISTS 'rol_administrador';
DROP ROLE IF EXISTS 'rol_funcionario';
DROP ROLE IF EXISTS 'rol_rrhh';
DROP ROLE IF EXISTS 'rol_empleado';

-- ======================================================================
-- CREACION DE ROLES
-- ======================================================================
CREATE ROLE 'rol_administrador';
CREATE ROLE 'rol_funcionario';
CREATE ROLE 'rol_rrhh';
CREATE ROLE 'rol_empleado';

-- ======================================================================
-- PERMISOS: rol_administrador
-- Todos los permisos sobre toda la base de datos
-- Puede crear usuarios de cualquier tipo
-- ======================================================================
GRANT ALL PRIVILEGES ON torres_corregida1.* TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_persona TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_persona TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_persona TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_legajo TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_legajo TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_legajo TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_titulo TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_titulo TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_titulos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_curso TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_curso TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_cursos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_idioma TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_idioma TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_idiomas TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_familiar TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_familiar TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_familiares TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_antecedente TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_antecedente TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_antecedentes TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_historial TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_historial TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_historial TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_sumario TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_sumario TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_sumarios TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_documento TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_documento TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_documentos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_usuario TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_usuario TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_usuario TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_persona TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_personas TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_legajo TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_legajos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_legajos_por_estado TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_usuario TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_usuarios TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_titulo TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_titulos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_curso TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_cursos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_idioma TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_idiomas TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_familiar TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_familiares TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_antecedente TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_antecedentes TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_historial TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historial TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_sumario TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_sumarios TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_obtener_documento TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_documentos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_categorias TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_cargos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_oficinas TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_personas TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_legajos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_titulos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_cursos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_idiomas TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_familiar TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_antecedente TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_historial TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_sumarios TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_documentos TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_listar_historico_usuario TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_legajo_completo TO 'rol_administrador';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_admin_buscar_persona TO 'rol_administrador';

-- ======================================================================
-- PERMISOS: rol_funcionario
-- SELECT en todas las tablas principales e historicas
-- EXECUTE en SPs de consulta
-- Sin INSERT, UPDATE, DELETE ni creacion de usuarios
-- ======================================================================
GRANT SELECT ON torres_corregida1.personas TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.legajos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.usuario TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.titulos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.cursos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.idiomas TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.familiar TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.antecedente_laboral TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.historial_legajos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.sumarios TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.documentos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.categorias TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.cargos TO 'rol_funcionario';
GRANT SELECT ON torres_corregida1.oficinas TO 'rol_funcionario';
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
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_persona TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_personas TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_legajo TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_legajos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_legajos_por_estado TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_usuario TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_usuarios TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_titulo TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_titulos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_curso TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_cursos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_idioma TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_idiomas TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_familiar TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_familiares TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_antecedente TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_antecedentes TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_historial TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historial TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_sumario TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_sumarios TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_obtener_documento TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_documentos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_categorias TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_cargos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_oficinas TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_personas TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_legajos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_titulos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_cursos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_idiomas TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_familiar TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_antecedente TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_historial TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_sumarios TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_documentos TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_listar_historico_usuario TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_legajo_completo TO 'rol_funcionario';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_func_buscar_persona TO 'rol_funcionario';

-- ======================================================================
-- PERMISOS: rol_rrhh
-- SELECT en todas las tablas principales e historicas
-- INSERT y UPDATE en tablas principales solamente
-- EXECUTE en SPs de insertar, modificar y consultar
-- Sin DELETE directo (la baja es logica via SP)
-- Solo puede crear usuarios tipo empleado (controlado por SP)
-- ======================================================================
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
GRANT SELECT ON torres_corregida1.categorias TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.cargos TO 'rol_rrhh';
GRANT SELECT ON torres_corregida1.oficinas TO 'rol_rrhh';
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
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_persona TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_persona TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_persona TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_legajo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_legajo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_legajo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_titulo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_titulo TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_titulos TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_curso TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_curso TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_cursos TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_idioma TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_idioma TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_idiomas TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_familiar TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_familiar TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_familiares TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_antecedente TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_antecedente TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_antecedentes TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_historial TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_historial TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_historial TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_sumario TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_sumario TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_sumarios TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_documento TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_documento TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_documentos TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_insertar_usuario TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_modificar_usuario TO 'rol_rrhh';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_usuario TO 'rol_rrhh';

-- ======================================================================
-- PERMISOS: rol_empleado
-- EXECUTE solo en SPs de consulta de su propia informacion
-- Sin acceso directo a ninguna tabla
-- Sin acceso a tablas historicas
-- ======================================================================
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_persona TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_legajo TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_titulos TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_cursos TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_idiomas TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_familiares TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_antecedentes TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_historial TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_sumarios TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_documentos TO 'rol_empleado';
GRANT EXECUTE ON PROCEDURE torres_corregida1.sp_obtener_usuario TO 'rol_empleado';

-- ======================================================================
-- CREACION DE USUARIOS DE BASE DE DATOS Y ASIGNACION DE ROLES
-- ======================================================================
CREATE USER 'usr_administrador'@'localhost' IDENTIFIED BY 'Admin_Torres_2024!';
CREATE USER 'usr_funcionario'@'localhost' IDENTIFIED BY 'Func_Torres_2024!';
CREATE USER 'usr_rrhh'@'localhost' IDENTIFIED BY 'Rrhh_Torres_2024!';
CREATE USER 'usr_empleado'@'localhost' IDENTIFIED BY 'Empl_Torres_2024!';

GRANT 'rol_administrador' TO 'usr_administrador'@'localhost';
GRANT 'rol_funcionario' TO 'usr_funcionario'@'localhost';
GRANT 'rol_rrhh' TO 'usr_rrhh'@'localhost';
GRANT 'rol_empleado' TO 'usr_empleado'@'localhost';

-- ======================================================================
-- ACTIVAR ROLES POR DEFECTO AL INICIAR SESION
-- ======================================================================
SET DEFAULT ROLE 'rol_administrador' TO 'usr_administrador'@'localhost';
SET DEFAULT ROLE 'rol_funcionario' TO 'usr_funcionario'@'localhost';
SET DEFAULT ROLE 'rol_rrhh' TO 'usr_rrhh'@'localhost';
SET DEFAULT ROLE 'rol_empleado' TO 'usr_empleado'@'localhost';

FLUSH PRIVILEGES;
