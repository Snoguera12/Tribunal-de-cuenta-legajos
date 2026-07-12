USE torres_corregida1;

-- ============================================================
-- SISTEMA TORRES_CORREGIDA1 — INDICES DE RENDIMIENTO
-- ============================================================
--
-- INDICE DE INDICES CREADOS:
-- ============================================================
--
-- [TABLAS MAESTRAS]
--   idx_categorias_nombre          — categorias(nombre_categoria)
--   idx_cargos_nombre              — cargos(nombre_cargo)
--   idx_oficinas_nombre            — oficinas(nombre_oficina)
--
-- [TABLA PERSONAS]
--   ft_personas_busqueda           — FULLTEXT(apellido, nombre, dni, cuil)
--                                    (ya incluido en CREATE TABLE)
--   idx_personas_apellido_nombre   — personas(apellido, nombre)
--   idx_personas_dni               — personas(dni) — ya UNIQUE, refuerza busqueda
--   idx_personas_cuil              — personas(cuil)
--
-- [TABLA LEGAJOS]
--   idx_legajos_id_persona         — legajos(id_persona)
--   idx_legajos_estado             — legajos(estado)
--   idx_legajos_id_cargo           — legajos(id_cargo)
--   idx_legajos_id_categoria       — legajos(id_categoria)
--   idx_legajos_id_oficina         — legajos(id_oficina)
--
-- [TABLA USUARIO]
--   idx_usuario_tipo               — usuario(tipo)
--   idx_usuario_activo             — usuario(activo)
--
-- [TABLAS SECUNDARIAS]
--   idx_titulos_id_persona         — titulos(id_persona)
--   idx_titulos_activo             — titulos(activo)
--   idx_cursos_id_persona          — cursos(id_persona)
--   idx_cursos_activo              — cursos(activo)
--   idx_idiomas_id_persona         — idiomas(id_persona)
--   idx_idiomas_activo             — idiomas(activo)
--   idx_familiar_id_persona        — familiar(id_persona)
--   idx_familiar_relacion          — familiar(relacion_empleado)
--   idx_antecedente_id_persona     — antecedente_laboral(id_persona)
--   idx_historial_id_legajo        — historial_legajos(id_legajo)
--   idx_historial_accion           — historial_legajos(accion)
--   idx_sumarios_id_legajo         — sumarios(id_legajo)
--   idx_sumarios_activo            — sumarios(activo)
--   idx_documentos_id_persona      — documentos(id_persona)
--   idx_documentos_tipo            — documentos(tipo_doc)
--   idx_documentos_hash            — documentos(hash_archivo)
--
-- [TABLAS HISTORICAS]
--   idx_hist_personas_id_persona
--   idx_hist_legajos_id_legajo
--   idx_hist_titulos_id_persona
--   idx_hist_cursos_id_persona
--   idx_hist_idiomas_id_persona
--   idx_hist_familiar_id_persona
--   idx_hist_antecedente_id_persona
--   idx_hist_historial_id_legajo
--   idx_hist_sumarios_id_legajo
--   idx_hist_documentos_id_persona
--   idx_hist_usuario_id_legajo
--
-- [TABLAS DE SISTEMA]
--   idx_log_fecha_hora             — log_sistema(fecha_hora)
--   idx_log_id_usuario             — log_sistema(id_usuario)
--   idx_log_accion                 — log_sistema(accion)
--   idx_intentos_usuario           — intentos_login(usuario)
--   idx_intentos_bloqueado         — intentos_login(bloqueado)
--   idx_solicitudes_usuario        — solicitudes_recuperacion(id_usuario)
--   idx_solicitudes_estado         — solicitudes_recuperacion(estado)
--   idx_sesiones_token             — sesiones_activas(token_hash)
--   idx_sesiones_usuario           — sesiones_activas(id_usuario)
--   idx_sesiones_activa            — sesiones_activas(activa)
--   idx_sesiones_expiracion        — sesiones_activas(fecha_expiracion)
--   idx_eventos_tipo               — eventos(tipo_evento)
--   idx_eventos_fecha              — eventos(fecha_hora)
--   idx_incidentes_estado          — incidentes(estado)
--   idx_incidentes_tipo            — incidentes(tipo_incidente)
--   idx_incidentes_severidad       — incidentes(nivel_severidad)
--   idx_backup_tipo                — backup_log(tipo)
--
-- ============================================================

-- ============================================================
-- LIMPIEZA PREVIA
-- ============================================================
DROP INDEX IF EXISTS idx_categorias_nombre          ON categorias;
DROP INDEX IF EXISTS idx_cargos_nombre              ON cargos;
DROP INDEX IF EXISTS idx_oficinas_nombre            ON oficinas;
DROP INDEX IF EXISTS idx_personas_apellido_nombre   ON personas;
DROP INDEX IF EXISTS idx_legajos_id_persona         ON legajos;
DROP INDEX IF EXISTS idx_legajos_estado             ON legajos;
DROP INDEX IF EXISTS idx_legajos_id_cargo           ON legajos;
DROP INDEX IF EXISTS idx_legajos_id_categoria       ON legajos;
DROP INDEX IF EXISTS idx_legajos_id_oficina         ON legajos;
DROP INDEX IF EXISTS idx_usuario_tipo               ON usuario;
DROP INDEX IF EXISTS idx_usuario_activo             ON usuario;
DROP INDEX IF EXISTS idx_titulos_id_persona         ON titulos;
DROP INDEX IF EXISTS idx_titulos_activo             ON titulos;
DROP INDEX IF EXISTS idx_cursos_id_persona          ON cursos;
DROP INDEX IF EXISTS idx_cursos_activo              ON cursos;
DROP INDEX IF EXISTS idx_idiomas_id_persona         ON idiomas;
DROP INDEX IF EXISTS idx_idiomas_activo             ON idiomas;
DROP INDEX IF EXISTS idx_familiar_id_persona        ON familiar;
DROP INDEX IF EXISTS idx_familiar_relacion          ON familiar;
DROP INDEX IF EXISTS idx_antecedente_id_persona     ON antecedente_laboral;
DROP INDEX IF EXISTS idx_historial_id_legajo        ON historial_legajos;
DROP INDEX IF EXISTS idx_historial_accion           ON historial_legajos;
DROP INDEX IF EXISTS idx_sumarios_id_legajo         ON sumarios;
DROP INDEX IF EXISTS idx_sumarios_activo            ON sumarios;
DROP INDEX IF EXISTS idx_documentos_id_persona      ON documentos;
DROP INDEX IF EXISTS idx_documentos_tipo            ON documentos;
DROP INDEX IF EXISTS idx_documentos_hash            ON documentos;
DROP INDEX IF EXISTS idx_hist_personas_id_persona   ON historico_personas;
DROP INDEX IF EXISTS idx_hist_legajos_id_legajo     ON historico_legajos;
DROP INDEX IF EXISTS idx_hist_titulos_id_persona    ON historico_titulos;
DROP INDEX IF EXISTS idx_hist_cursos_id_persona     ON historico_cursos;
DROP INDEX IF EXISTS idx_hist_idiomas_id_persona    ON historico_idiomas;
DROP INDEX IF EXISTS idx_hist_familiar_id_persona   ON historico_familiar;
DROP INDEX IF EXISTS idx_hist_ant_id_persona        ON historico_antecedente_laboral;
DROP INDEX IF EXISTS idx_hist_historial_id_legajo   ON historico_historial_legajos;
DROP INDEX IF EXISTS idx_hist_sumarios_id_legajo    ON historico_sumarios;
DROP INDEX IF EXISTS idx_hist_doc_id_persona        ON historico_documentos;
DROP INDEX IF EXISTS idx_hist_usuario_id_legajo     ON historico_usuario;
DROP INDEX IF EXISTS idx_log_fecha_hora             ON log_sistema;
DROP INDEX IF EXISTS idx_log_id_usuario             ON log_sistema;
DROP INDEX IF EXISTS idx_log_accion                 ON log_sistema;
DROP INDEX IF EXISTS idx_intentos_usuario           ON intentos_login;
DROP INDEX IF EXISTS idx_intentos_bloqueado         ON intentos_login;
DROP INDEX IF EXISTS idx_solicitudes_usuario        ON solicitudes_recuperacion;
DROP INDEX IF EXISTS idx_solicitudes_estado         ON solicitudes_recuperacion;
DROP INDEX IF EXISTS idx_sesiones_token             ON sesiones_activas;
DROP INDEX IF EXISTS idx_sesiones_usuario           ON sesiones_activas;
DROP INDEX IF EXISTS idx_sesiones_activa            ON sesiones_activas;
DROP INDEX IF EXISTS idx_sesiones_expiracion        ON sesiones_activas;
DROP INDEX IF EXISTS idx_eventos_tipo               ON eventos;
DROP INDEX IF EXISTS idx_eventos_fecha              ON eventos;
DROP INDEX IF EXISTS idx_incidentes_estado          ON incidentes;
DROP INDEX IF EXISTS idx_incidentes_tipo            ON incidentes;
DROP INDEX IF EXISTS idx_incidentes_severidad       ON incidentes;
DROP INDEX IF EXISTS idx_backup_tipo                ON backup_log;

-- ============================================================
-- TABLAS MAESTRAS
-- ============================================================
CREATE INDEX idx_categorias_nombre        ON categorias (nombre_categoria);
CREATE INDEX idx_cargos_nombre            ON cargos (nombre_cargo);
CREATE INDEX idx_oficinas_nombre          ON oficinas (nombre_oficina);

-- ============================================================
-- TABLA PERSONAS
-- ============================================================
CREATE INDEX idx_personas_apellido_nombre ON personas (apellido, nombre);

-- ============================================================
-- TABLA LEGAJOS
-- ============================================================
CREATE INDEX idx_legajos_id_persona   ON legajos (id_persona);
CREATE INDEX idx_legajos_estado       ON legajos (estado);
CREATE INDEX idx_legajos_id_cargo     ON legajos (id_cargo);
CREATE INDEX idx_legajos_id_categoria ON legajos (id_categoria);
CREATE INDEX idx_legajos_id_oficina   ON legajos (id_oficina);

-- ============================================================
-- TABLA USUARIO
-- ============================================================
CREATE INDEX idx_usuario_tipo   ON usuario (tipo);
CREATE INDEX idx_usuario_activo ON usuario (activo);

-- ============================================================
-- TABLAS SECUNDARIAS
-- ============================================================
CREATE INDEX idx_titulos_id_persona     ON titulos (id_persona);
CREATE INDEX idx_titulos_activo         ON titulos (activo);
CREATE INDEX idx_cursos_id_persona      ON cursos (id_persona);
CREATE INDEX idx_cursos_activo          ON cursos (activo);
CREATE INDEX idx_idiomas_id_persona     ON idiomas (id_persona);
CREATE INDEX idx_idiomas_activo         ON idiomas (activo);
CREATE INDEX idx_familiar_id_persona    ON familiar (id_persona);
CREATE INDEX idx_familiar_relacion      ON familiar (relacion_empleado);
CREATE INDEX idx_antecedente_id_persona ON antecedente_laboral (id_persona);
CREATE INDEX idx_historial_id_legajo    ON historial_legajos (id_legajo);
CREATE INDEX idx_historial_accion       ON historial_legajos (accion);
CREATE INDEX idx_sumarios_id_legajo     ON sumarios (id_legajo);
CREATE INDEX idx_sumarios_activo        ON sumarios (activo);
CREATE INDEX idx_documentos_id_persona  ON documentos (id_persona);
CREATE INDEX idx_documentos_tipo        ON documentos (tipo_doc);
CREATE INDEX idx_documentos_hash        ON documentos (hash_archivo);

-- ============================================================
-- TABLAS HISTORICAS
-- ============================================================
CREATE INDEX idx_hist_personas_id_persona  ON historico_personas (id_persona);
CREATE INDEX idx_hist_legajos_id_legajo    ON historico_legajos (id_legajo);
CREATE INDEX idx_hist_titulos_id_persona   ON historico_titulos (id_persona);
CREATE INDEX idx_hist_cursos_id_persona    ON historico_cursos (id_persona);
CREATE INDEX idx_hist_idiomas_id_persona   ON historico_idiomas (id_persona);
CREATE INDEX idx_hist_familiar_id_persona  ON historico_familiar (id_persona);
CREATE INDEX idx_hist_ant_id_persona       ON historico_antecedente_laboral (id_persona);
CREATE INDEX idx_hist_historial_id_legajo  ON historico_historial_legajos (id_legajo);
CREATE INDEX idx_hist_sumarios_id_legajo   ON historico_sumarios (id_legajo);
CREATE INDEX idx_hist_doc_id_persona       ON historico_documentos (id_persona);
CREATE INDEX idx_hist_usuario_id_legajo    ON historico_usuario (id_legajo);

-- ============================================================
-- TABLAS DE SISTEMA / ALERTAS
-- ============================================================
CREATE INDEX idx_log_fecha_hora       ON log_sistema (fecha_hora);
CREATE INDEX idx_log_id_usuario       ON log_sistema (id_usuario);
CREATE INDEX idx_log_accion           ON log_sistema (accion);
CREATE INDEX idx_intentos_usuario     ON intentos_login (usuario);
CREATE INDEX idx_intentos_bloqueado   ON intentos_login (bloqueado);
CREATE INDEX idx_solicitudes_usuario  ON solicitudes_recuperacion (id_usuario);
CREATE INDEX idx_solicitudes_estado   ON solicitudes_recuperacion (estado);
CREATE INDEX idx_sesiones_token       ON sesiones_activas (token_hash);
CREATE INDEX idx_sesiones_usuario     ON sesiones_activas (id_usuario);
CREATE INDEX idx_sesiones_activa      ON sesiones_activas (activa);
CREATE INDEX idx_sesiones_expiracion  ON sesiones_activas (fecha_expiracion);
CREATE INDEX idx_eventos_tipo         ON eventos (tipo_evento);
CREATE INDEX idx_eventos_fecha        ON eventos (fecha_hora);
CREATE INDEX idx_incidentes_estado    ON incidentes (estado);
CREATE INDEX idx_incidentes_tipo      ON incidentes (tipo_incidente);
CREATE INDEX idx_incidentes_severidad ON incidentes (nivel_severidad);
CREATE INDEX idx_backup_tipo          ON backup_log (tipo);
