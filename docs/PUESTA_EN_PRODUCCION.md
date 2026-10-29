# Puesta en producción: lista de verificación

Antes de usar el sistema con datos reales, en el servidor:

```bash
php artisan sistema:diagnostico      # tiene que terminar con 0 errores (y revisar los avisos)
```

## 1. Archivo `.env`
- `APP_ENV=production` y `APP_DEBUG=false`.
- `APP_KEY` generada **y guardada fuera del servidor**: sin ella no se pueden abrir los documentos cifrados.
- `BACKUP_ARCHIVE_PASSWORD` definida (el backup va cifrado e incluye el `.env`).
- Si hay un proxy o balanceador delante: `TRUSTED_PROXIES=...` (para registrar la IP real).
- `chmod 600 .env`. El `.env` nunca va al repositorio ni a un zip.

## 2. Base de datos
- SQLite (la de los ejemplos) sirve para pocos usuarios. El sistema la deja en modo **WAL** con espera de 5 s
  (`DB_JOURNAL_MODE`, `DB_BUSY_TIMEOUT`, `HIST_DB_JOURNAL_MODE`, `HIST_DB_BUSY_TIMEOUT`) y repite el guardado de
  la auditoría si la base está ocupada. Aparecen archivos `-wal` y `-shm` junto a la base: es normal; no se borran
  con el sistema andando y no se suben a git.
- Para decenas de usuarios a la vez conviene MySQL/MariaDB. **Ya se probó con MariaDB 10.11**: las 236 pruebas
  pasan (sin BackupTest, que es de SQLite), `migrate:fresh --seed`, la carga de 500 agentes y `migrate:rollback` funcionan.
  Para usarlo: crear dos bases (principal e histórico), poner en el `.env` `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`,
  `DB_USERNAME`, `DB_PASSWORD` y lo mismo con el prefijo `HIST_DB_` (`HIST_DB_CONNECTION=mysql`), y correr `php artisan migrate --force`.
- Antes de cambiar de motor: `composer test:mysql` (crea antes las bases `legajos_test` y `legajos_hist` y ajustá usuario y clave en `phpunit.mysql.xml`).
- Los backups con MySQL guardan una exportación por tabla en JSON; la restauración automática de las bases es solo para SQLite
  (con MySQL se restaura a mano con el `.sql` que trae el zip).

## 3. Programas en el servidor
- `sudo apt install ghostscript` (si no, los PDF no se achican).
- PHP con las extensiones `gd`, `mbstring`, `xml`, `zip`, `intl`, `bcmath`, `curl`, `sqlite3` (o `mysql`).
- Opcional: `sudo apt install clamav` y en el `.env` `ANTIVIRUS_ACTIVO=true` para revisar cada archivo que se sube.

## 4. Tareas programadas (backups y verificaciones)
```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```
Sin esta línea no se hacen los backups ni las verificaciones. `sistema:diagnostico` avisa si no la detecta.

## 5. Copias de seguridad
- Un segundo destino fuera del servidor en `BACKUP_DISCOS`.
- Probar una restauración: `php artisan sistema:restaurar` en un servidor de prueba.

## 6. Datos
- Sin usuarios de prueba (`./2_borrar_todo.sh --sin-admin`) y los administradores con claves propias:
  `php artisan sistema:crear-admin correo@dominio --sector=tic` y `--sector=rrhh`.
- Cambiar cualquier clave de ejemplo.

## 7. Cada vez que se actualiza el sistema
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
```
