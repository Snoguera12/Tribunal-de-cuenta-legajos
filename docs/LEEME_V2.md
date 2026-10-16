# Versión 2 - auditoría, histórico en base aparte, incidentes, backup y correcciones

## Cómo aplicarlo

1. Copiar las carpetas de `cambios/` sobre el proyecto (o `git apply nueva_version.patch`).
2. Borrar los archivos de `ARCHIVOS_A_BORRAR.txt` (en Linux `misdatospersonales.php` y `MisDatosPersonales.php` son distintos: debe quedar solo el de mayúsculas).
3. Ejecutar:

       php artisan optimize:clear
       php artisan migrate
       php artisan historico:sincronizar --copiar-actual
       php artisan usuarios:sincronizar-habilitacion
       php artisan auditoria:verificar
       php artisan documentos:reoptimizar --dry-run

Requisitos: `php-zip`, `php-gd`, `php-exif`. Recomendado: `ghostscript` (`gs`). Cron: `* * * * * php artisan schedule:run`.

## Regla nueva: baja de legajo = usuario deshabilitado

- Si una persona se queda **sin ningún legajo de alta**, su usuario se deshabilita: no puede iniciar sesión, se le cierran las sesiones abiertas y se revocan sus tokens. Si vuelve a tener un legajo de alta, se habilita solo.
- Cuenta como baja: cambiar el estado, borrar el legajo o pasarlo a otra persona. También se deshabilita si se elimina la persona.
- Lo deshabilitado a mano por el administrador/RRHH (campo "Usuario habilitado") **no** se vuelve a habilitar solo.
- A un **administrador** nunca se lo deshabilita solo (queda registrado en la auditoría), para no dejar el sistema sin acceso.
- Un intento de ingreso de un usuario deshabilitado queda como "acceso denegado" (y no suma como clave mala).
- `usuarios:sincronizar-habilitacion` aplica la regla a los datos que ya existían.


## Acceso: intentos de clave, bloqueos, clave provisoria y sesión única

### Intentos de clave (por usuario, se configura en `config/sistema.php`, bloque `login`)

1. **Hasta 5 intentos por tanda**, con **20 segundos** entre uno y otro. Después del primer error aparece el cartel
   *"Clave incorrecta. Esperá 20 segundos... Te quedan 4 intento(s)"*. Un intento antes de los 20 segundos no se evalúa
   (ni siquiera con la clave correcta) y **no gasta** un intento.
2. Al llegar a 0 intentos: **bloqueo temporal de 1 hora**. Pasada la hora tiene **5 intentos más**.
3. Si también se agotan esos 5: **bloqueo definitivo** hasta que el administrador le dé permiso otra vez.
4. **Si se frenó en 4 intentos** (sin usar el 5to), a los **10 minutos se le devuelven** esos intentos. Esto puede pasar hasta 3 veces.
5. **Tope total: 12 claves incorrectas.** Al llegar a 12 el bloqueo es definitivo, sin importar el camino
   (4 + 4 + 4 = 12). Los 12 se cuentan desde el último ingreso correcto o desde que el administrador desbloquea.
6. Una clave correcta borra todos los contadores.
7. Un correo que no existe recibe **los mismos mensajes** (así no se puede averiguar qué correos están registrados).

Cómo se interpretó: la consigna mezclaba "5 + 5 = 10" con "tope 12". Se resolvió así: el bloqueo definitivo llega a los 10 si se
gastan las dos tandas completas, o a los 12 si el usuario se frena en 4 e insiste (4 + 4 + 4). Si querés otros números, se cambian en el archivo de configuración.

### Avisos al administrador
- Al 4to error: advertencia en la auditoría ("le queda un solo intento").
- Bloqueo temporal: **incidente (alta)** + notificación en el panel (y correo si `ALERTAS_CORREO=true`).
- Bloqueo definitivo: **incidente (crítica)** + notificación. El usuario queda deshabilitado con motivo `bloqueo_login`.
- Intentos devueltos, desbloqueos y cierres de sesión quedan en la auditoría.
- El resumen diario cuenta los usuarios bloqueados y los que siguen con clave provisoria hace más de 7 días.

### Desbloquear (solo administrador)
- Lista de usuarios -> botón **Desbloquear** (visible solo si está bloqueado y solo para el administrador).
- Si queda bloqueado el único administrador: `php artisan usuarios:desbloquear correo@dominio` desde el servidor.
- Importante: como el bloqueo es por usuario, cualquiera que sepa un correo puede bloquear esa cuenta escribiendo claves mal (es lo que implica la regla).

### Usuario nuevo y clave provisoria
- Al crear un usuario se le manda un **mail** (nunca con la clave) pidiéndole que ingrese y cambie la clave provisoria.
  Si el correo no está configurado o falla, el usuario igual se crea.
- En su **primer ingreso** queda anotado y el sistema lo manda a la pantalla **Cambiar contraseña**: hasta que no la cambie no puede abrir ninguna otra página.
- La clave nueva exige 10 o más caracteres con mayúsculas, minúsculas y números, y tiene que ser distinta de la actual y del correo.
- Si un administrador le cambia la clave a alguien, vuelve a ser provisoria. Los usuarios que ya existían no se obligan a cambiarla.

### Una sola sesión por usuario (`SESION_UNICA_MODO` en el .env)
- `bloquear` (por defecto): si el usuario ya tiene una sesión abierta y activa, el segundo ingreso se rechaza con un aviso.
  Si la otra sesión estuvo **10 minutos sin actividad** (`SESION_UNICA_INACTIVIDAD`) se considera abandonada y el nuevo ingreso la reemplaza.
- `reemplazar`: el último ingreso gana y la sesión anterior se cierra en su próximo pedido.
- `apagada`: sin control.
- Tres ingresos duplicados en 30 minutos abren un incidente ("posible clave compartida").
- El administrador puede cerrar la sesión de un usuario desde la lista de usuarios.

## Variables del .env (ver .env.example)

- `HIST_DB_*`: base aparte (por defecto `database/historico.sqlite`). En MySQL, otra base y un usuario que solo pueda INSERT/SELECT.
- `BACKUP_ARCHIVE_PASSWORD`: clave del zip. Sin clave el backup NO va cifrado y NO incluye el `.env`.
- `BACKUP_DISCOS=backups,backups_externo` + `BACKUP_EXTERNO_RUTA`: segunda copia.
- `DOC_*`: calidad de la recompresión de documentos.
- `ALERTAS_CORREO=true`, `ALERTAS_DESTINOS`, `ALERTAS_DESDE`: avisos por correo (necesita `MAIL_*`).
- `TRUSTED_PROXIES`: si hay proxy o balanceador, para guardar la IP real.
- `SESION_UNICA_MODO`, `SESION_UNICA_INACTIVIDAD`.
- `SANCTUM_EXPIRATION`, `SEED_DEMO_PASSWORD`.

## Solo el administrador (menú "Seguridad")

Auditoría, Incidentes, Historial de datos y Copias de seguridad. Los otros roles reciben 403 (probado). Auditoría e Incidentes tienen botón **Descargar informe (CSV)** con lo que se ve filtrado; cada descarga queda anotada.

## Comandos

    sistema:backup [--tipo=db] [--sin-documentos]     sistema:backup-verificar [zip]
    sistema:backup-limpiar                            sistema:restaurar zip [--solo-bases|--solo-documentos]
    historico:sincronizar [--copiar-actual]           historico:restaurar tabla id version
    auditoria:verificar                               auditoria:archivar [--dias=365]
    usuarios:sincronizar-habilitacion                 sistema:resumen-diario
    usuarios:desbloquear correo
    documentos:reoptimizar [--dry-run]                documentos:encriptar-existentes [--dry-run]
    documentos:huerfanos [--borrar]

Agenda: backups 03:00 (completo) y cada 6 h (bases), verificación de la auditoría 02:30, del backup 03:30, limpieza 04:00, resumen diario 07:00, archivado de auditoría el día 1 de cada mes.

## Auditoría a prueba de manipulación

Cada registro lleva el hash (HMAC con la APP_KEY) del anterior. `auditoria:verificar` detecta registros modificados, borrados, insertados a mano o cortados al final (guarda el último verificado fuera de la base), y abre un incidente crítico. El backup anota el último hash. `auditoria:archivar` pasa lo más viejo a un `.jsonl.gz` con hash, sin romper la cadena.

## Limitaciones

- **MAC:** solo se lee de la tabla ARP si la PC está en la misma red que el servidor; puede estar vacía y puede falsificarse. La IP siempre se guarda.
- La auditoría y el histórico usan eventos de Eloquent: `Model::where()->update()` y los borrados en cascada de la base no los disparan.
- Quien tenga la `APP_KEY` y acceso de escritura a la base podría rehacer la cadena: conviene usuario de base solo-insert y backups fuera del servidor.
- La recompresión de imágenes/PDF es con pérdida y no se deshace (solo con el backup).
- Restaurar bases MySQL se hace a mano con el `.sql` del zip.
- Con borrado suave, los campos únicos (dni, email) de un registro borrado siguen ocupados.
- Si se cambia la `APP_KEY` los documentos cifrados no se abren (el backup incluye el `.env`, cifrado).
- No incluye verificación en dos pasos ni antivirus (ClamAV).
- El bloqueo por usuario permite que un tercero bloquee una cuenta a propósito; para el administrador hay recuperación por consola.
- La sesión única depende de la actividad registrada: un navegador cerrado sin salir ocupa el lugar hasta 10 minutos.
- Hay que hacer en el servidor: cambiar las claves que hoy son `Legajo2026!`, `APP_DEBUG=false`, sacar `.env` y `laravel.log` del zip/repositorio.

Pruebas: `php artisan test` (166 pruebas).
