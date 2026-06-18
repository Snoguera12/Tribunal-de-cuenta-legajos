USE torres_corregida1;

-- LISTADO COMPLETO DE INDICES PARA torres_corregida1
-- OBJETIVO: Mejorar el rendimiento de las consultas mas frecuentes del sistema

-- ======================================================================
-- LIMPIEZA PREVIA DE INDICES
-- ======================================================================
DROP INDEX IF EXISTS idx_personas_dni ON personas;
DROP INDEX IF EXISTS idx_personas_cuil ON personas;
DROP INDEX IF EXISTS idx_personas_apellido_nombre ON personas;
DROP INDEX IF EXISTS idx_legajos_id_persona ON legajos;
DROP INDEX IF EXISTS idx_legajos_estado ON legajos;
DROP INDEX IF EXISTS idx_legajos_id_cargo ON legajos;
DROP INDEX IF EXISTS idx_legajos_id_categoria ON legajos;
DROP INDEX IF EXISTS idx_legajos_id_oficina ON legajos;
DROP INDEX IF EXISTS idx_usuario_tipo ON usuario;
DROP INDEX IF EXISTS idx_usuario_activo ON usuario;
DROP INDEX IF EXISTS idx_titulos_id_persona ON titulos;
DROP INDEX IF EXISTS idx_titulos_activo ON titulos;
DROP INDEX IF EXISTS idx_cursos_id_persona ON cursos;
DROP INDEX IF EXISTS idx_cursos_activo ON cursos;
DROP INDEX IF EXISTS idx_idiomas_id_persona ON idiomas;
DROP INDEX IF EXISTS idx_idiomas_activo ON idiomas;
DROP INDEX IF EXISTS idx_familiar_id_persona ON familiar;
DROP INDEX IF EXISTS idx_familiar_activo ON familiar;
DROP INDEX IF EXISTS idx_familiar_relacion ON familiar;
DROP INDEX IF EXISTS idx_antecedente_id_persona ON antecedente_laboral;
DROP INDEX IF EXISTS idx_antecedente_activo ON antecedente_laboral;
DROP INDEX IF EXISTS idx_historial_id_legajo ON historial_legajos;
DROP INDEX IF EXISTS idx_historial_accion ON historial_legajos;
DROP INDEX IF EXISTS idx_sumarios_id_legajo ON sumarios;
DROP INDEX IF EXISTS idx_documentos_id_persona ON documentos;
DROP INDEX IF EXISTS idx_documentos_tipo_doc ON documentos;
DROP INDEX IF EXISTS idx_documentos_hash ON documentos;
DROP INDEX IF EXISTS idx_hist_personas_id_persona ON historico_personas;
DROP INDEX IF EXISTS idx_hist_legajos_id_legajo ON historico_legajos;
DROP INDEX IF EXISTS idx_hist_titulos_id_persona ON historico_titulos;
DROP INDEX IF EXISTS idx_hist_cursos_id_persona ON historico_cursos;
DROP INDEX IF EXISTS idx_hist_idiomas_id_persona ON historico_idiomas;
DROP INDEX IF EXISTS idx_hist_familiar_id_persona ON historico_familiar;
DROP INDEX IF EXISTS idx_hist_antecedente_id_persona ON historico_antecedente_laboral;
DROP INDEX IF EXISTS idx_hist_historial_id_legajo ON historico_historial_legajos;
DROP INDEX IF EXISTS idx_hist_sumarios_id_legajo ON historico_sumarios;
DROP INDEX IF EXISTS idx_hist_documentos_id_persona ON historico_documentos;
DROP INDEX IF EXISTS idx_hist_usuario_id_legajo ON historico_usuario;

-- ======================================================================
-- INDICES: personas
-- ======================================================================
CREATE INDEX idx_personas_dni ON personas (dni);
CREATE INDEX idx_personas_cuil ON personas (cuil);
CREATE INDEX idx_personas_apellido_nombre ON personas (apellido, nombre);

-- ======================================================================
-- INDICES: legajos
-- ======================================================================
CREATE INDEX idx_legajos_id_persona ON legajos (id_persona);
CREATE INDEX idx_legajos_estado ON legajos (estado);
CREATE INDEX idx_legajos_id_cargo ON legajos (id_cargo);
CREATE INDEX idx_legajos_id_categoria ON legajos (id_categoria);
CREATE INDEX idx_legajos_id_oficina ON legajos (id_oficina);

-- ======================================================================
-- INDICES: usuario
-- ======================================================================
CREATE INDEX idx_usuario_tipo ON usuario (tipo);
CREATE INDEX idx_usuario_activo ON usuario (activo);

-- ======================================================================
-- INDICES: titulos
-- ======================================================================
CREATE INDEX idx_titulos_id_persona ON titulos (id_persona);
CREATE INDEX idx_titulos_activo ON titulos (activo);

-- ======================================================================
-- INDICES: cursos
-- ======================================================================
CREATE INDEX idx_cursos_id_persona ON cursos (id_persona);
CREATE INDEX idx_cursos_activo ON cursos (activo);

-- ======================================================================
-- INDICES: idiomas
-- ======================================================================
CREATE INDEX idx_idiomas_id_persona ON idiomas (id_persona);
CREATE INDEX idx_idiomas_activo ON idiomas (activo);

-- ======================================================================
-- INDICES: familiar
-- ======================================================================
CREATE INDEX idx_familiar_id_persona ON familiar (id_persona);
CREATE INDEX idx_familiar_activo ON familiar (activo);
CREATE INDEX idx_familiar_relacion ON familiar (relacion_empleado);

-- ======================================================================
-- INDICES: antecedente_laboral
-- ======================================================================
CREATE INDEX idx_antecedente_id_persona ON antecedente_laboral (id_persona);
CREATE INDEX idx_antecedente_activo ON antecedente_laboral (activo);

-- ======================================================================
-- INDICES: historial_legajos
-- ======================================================================
CREATE INDEX idx_historial_id_legajo ON historial_legajos (id_legajo);
CREATE INDEX idx_historial_accion ON historial_legajos (accion);

-- ======================================================================
-- INDICES: sumarios
-- ======================================================================
CREATE INDEX idx_sumarios_id_legajo ON sumarios (id_legajo);

-- ======================================================================
-- INDICES: documentos
-- ======================================================================
CREATE INDEX idx_documentos_id_persona ON documentos (id_persona);
CREATE INDEX idx_documentos_tipo_doc ON documentos (tipo_doc);
CREATE INDEX idx_documentos_hash ON documentos (hash_archivo);

-- ======================================================================
-- INDICES: tablas historicas
-- ======================================================================
CREATE INDEX idx_hist_personas_id_persona ON historico_personas (id_persona);
CREATE INDEX idx_hist_legajos_id_legajo ON historico_legajos (id_legajo);
CREATE INDEX idx_hist_titulos_id_persona ON historico_titulos (id_persona);
CREATE INDEX idx_hist_cursos_id_persona ON historico_cursos (id_persona);
CREATE INDEX idx_hist_idiomas_id_persona ON historico_idiomas (id_persona);
CREATE INDEX idx_hist_familiar_id_persona ON historico_familiar (id_persona);
CREATE INDEX idx_hist_antecedente_id_persona ON historico_antecedente_laboral (id_persona);
CREATE INDEX idx_hist_historial_id_legajo ON historico_historial_legajos (id_legajo);
CREATE INDEX idx_hist_sumarios_id_legajo ON historico_sumarios (id_legajo);
CREATE INDEX idx_hist_documentos_id_persona ON historico_documentos (id_persona);
CREATE INDEX idx_hist_usuario_id_legajo ON historico_usuario (id_legajo);

-- ======================================================================
-- INDICES DE TABLAS DE SEGURIDAD
-- ======================================================================
DROP INDEX IF EXISTS idx_log_sistema_fecha ON log_sistema;
DROP INDEX IF EXISTS idx_log_sistema_usuario ON log_sistema;
DROP INDEX IF EXISTS idx_log_sistema_accion ON log_sistema;
DROP INDEX IF EXISTS idx_intentos_login_usuario ON intentos_login;
DROP INDEX IF EXISTS idx_intentos_login_bloqueado ON intentos_login;
DROP INDEX IF EXISTS idx_solicitudes_recuperacion_usuario ON solicitudes_recuperacion;
DROP INDEX IF EXISTS idx_solicitudes_recuperacion_estado ON solicitudes_recuperacion;
DROP INDEX IF EXISTS idx_eventos_tipo ON eventos;
DROP INDEX IF EXISTS idx_eventos_fecha ON eventos;
DROP INDEX IF EXISTS idx_incidentes_estado ON incidentes;
DROP INDEX IF EXISTS idx_incidentes_tipo ON incidentes;
DROP INDEX IF EXISTS idx_incidentes_severidad ON incidentes;
DROP INDEX IF EXISTS idx_backup_log_tipo ON backup_log;

CREATE INDEX idx_log_sistema_fecha ON log_sistema (fecha_hora);
CREATE INDEX idx_log_sistema_usuario ON log_sistema (id_usuario);
CREATE INDEX idx_log_sistema_accion ON log_sistema (accion);
CREATE INDEX idx_intentos_login_usuario ON intentos_login (usuario);
CREATE INDEX idx_intentos_login_bloqueado ON intentos_login (bloqueado);
CREATE INDEX idx_solicitudes_recuperacion_usuario ON solicitudes_recuperacion (id_usuario);
CREATE INDEX idx_solicitudes_recuperacion_estado ON solicitudes_recuperacion (estado);
CREATE INDEX idx_eventos_tipo ON eventos (tipo_evento);
CREATE INDEX idx_eventos_fecha ON eventos (fecha_hora);
CREATE INDEX idx_incidentes_estado ON incidentes (estado);
CREATE INDEX idx_incidentes_tipo ON incidentes (tipo_incidente);
CREATE INDEX idx_incidentes_severidad ON incidentes (nivel_severidad);
CREATE INDEX idx_backup_log_tipo ON backup_log (tipo);
