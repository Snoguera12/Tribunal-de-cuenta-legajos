# Puesta en producción — Sistema de Legajos (TCSE)

Guía paso a paso para instalar y dejar andando el sistema en un servidor Debian 13 con Apache y MariaDB.
Los valores de ejemplo son: carpeta `/var/www/legajos`, nombre `tsce.legajos`, IP `192.168.1.21`. Cambiarlos por los reales.

Regla general: todo lo que toque archivos del sistema se corre con el usuario de Apache:
`sudo -u www-data php artisan ...`. Si se corre con otro usuario se crean archivos que Apache después no puede escribir (error 500).

---

## 1. Programas necesarios

```bash
sudo apt update
sudo apt install apache2 mariadb-server git composer supervisor cron ghostscript nodejs npm \
  php8.4 php8.4-cli libapache2-mod-php8.4 php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip \
  php8.4-intl php8.4-gd php8.4-mysql php8.4-sqlite3
sudo systemctl enable apache2 mariadb supervisor cron
```

Comprobar: `php -v` (8.4 o superior) y `php -m | grep -i zip` (la extensión `zip` es necesaria para exportar a Excel).

## 2. Base de datos

```bash
sudo mysql
```

```sql
CREATE DATABASE legajos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'legajos'@'localhost' IDENTIFIED BY 'CLAVE_DE_LA_BASE';
GRANT ALL PRIVILEGES ON legajos.* TO 'legajos'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Código

```bash
cd /var/www
sudo git clone URL_DEL_REPOSITORIO legajos
cd legajos
cp .env.production.example .env
nano .env                              # completar todos los <COMPLETAR>
php artisan key:generate               # solo la primera vez
composer install --no-dev --optimize-autoloader
npm ci && npm run build                # compila los estilos del panel (public/build no se sube a git)
```

En el `.env` revisar especialmente: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tsce.legajos`,
`SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=database`, `DB_*` y el correo (SMTP).

## 4. Permisos

```bash
cd /var/www/legajos
sudo mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views \
  storage/app/private storage/app/public bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
sudo chown www-data:www-data .env && sudo chmod 600 .env
```

## 5. Base de datos del sistema

Solo la **primera instalación**:

```bash
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --class=AreaSeeder --force
sudo -u www-data php artisan db:seed --class=CargoSeeder --force
sudo -u www-data php artisan db:seed --class=CategoriaSeeder --force
```

- Cada seeder se corre **una sola vez** (si se repite se duplican registros).
- **No** correr `db:seed` a secas ni `migrate:fresh --seed`: crea usuarios de prueba con la clave `123456`
  (y falla en producción porque usa datos falsos de desarrollo).
- **Nunca** correr `migrate:fresh` en un servidor con datos reales: borra todas las tablas.
  Para aplicar cambios nuevos se usa siempre `migrate --force`.

Crear el administrador (la clave no queda en el historial de la terminal):

```bash
read -s -p "Clave provisoria: " CLAVE; echo
sudo -u www-data env HOME=/tmp CLAVE="$CLAVE" php artisan tinker --execute='
App\Models\User::create([
    "name" => "Administrador",
    "email" => "CORREO_REAL@dominio.com",
    "password" => getenv("CLAVE"),
    "rol" => "administrador",
    "email_verified_at" => now(),
    "habilitado" => true,
]);
echo "Usuario creado" . PHP_EOL;'
unset CLAVE
```

En el primer ingreso el sistema obliga a cambiar la clave. Los demás usuarios se crean desde el panel.

Historial de datos y cachés:

```bash
sudo -u www-data php artisan historico:sincronizar --copiar-actual
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan filament:optimize
```

No hace falta `php artisan storage:link`: los documentos van cifrados en un disco privado y se abren solo con enlaces firmados.

## 6. Apache y https

Certificado propio (uso interno), con el nombre y la IP del servidor:

```bash
sudo openssl req -x509 -nodes -newkey rsa:4096 -sha256 -days 825 \
  -keyout /etc/ssl/private/legajos.key -out /etc/ssl/certs/legajos.crt \
  -subj "/C=AR/O=Tribunal de Cuentas de Santiago del Estero/CN=tsce.legajos" \
  -addext "subjectAltName=DNS:tsce.legajos,IP:192.168.1.21"
sudo chmod 600 /etc/ssl/private/legajos.key
```

El certificado vence a los 825 días: anotar la fecha y repetir el comando antes. Los navegadores avisan que no es de
confianza; para quitar el aviso se instala `legajos.crt` en cada equipo o se usa uno de la autoridad interna del Tribunal.

Sitio: `/etc/apache2/sites-available/legajos.conf`

```apache
<VirtualHost *:80>
    ServerName tsce.legajos
    Redirect permanent / https://tsce.legajos/
</VirtualHost>

<VirtualHost *:443>
    ServerName tsce.legajos

    DocumentRoot /var/www/legajos/public

    <Directory /var/www/legajos/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/legajos.crt
    SSLCertificateKeyFile /etc/ssl/private/legajos.key

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "same-origin"

    ErrorLog ${APACHE_LOG_DIR}/legajos-error.log
    CustomLog ${APACHE_LOG_DIR}/legajos-access.log combined
</VirtualHost>
```

**Importante:** `DocumentRoot` tiene que terminar en `/public`. Si apunta a la raíz del proyecto, desde el navegador
se pueden leer el `.env`, `vendor/` y `storage/`.

```bash
sudo a2enmod rewrite ssl headers
sudo a2ensite legajos
sudo apache2ctl configtest          # tiene que decir Syntax OK
sudo systemctl reload apache2
sudo apache2ctl -S                  # el sitio tsce.legajos tiene que figurar en el 443
```

Si la red no tiene DNS interno, en cada computadora que use el sistema agregar a `/etc/hosts`
(en Windows: `C:\Windows\System32\drivers\etc\hosts`):

```
192.168.1.21   tsce.legajos
```

Entrar siempre por el nombre (`https://tsce.legajos`), no por la IP: los enlaces de descarga de documentos se generan con `APP_URL`.

Comprobar que no se filtra nada interno (las tres tienen que dar 404 o 403, nunca 200):

```bash
curl -k -o /dev/null -w "%{http_code}\n" --resolve tsce.legajos:443:192.168.1.21 https://tsce.legajos/.env
curl -k -o /dev/null -w "%{http_code}\n" --resolve tsce.legajos:443:192.168.1.21 https://tsce.legajos/composer.json
curl -k -o /dev/null -w "%{http_code}\n" --resolve tsce.legajos:443:192.168.1.21 https://tsce.legajos/storage/logs/laravel.log
```

La restricción a la red interna del Tribunal está explicada en `docs/RED_INTERNA_APACHE.md`.

## 7. Proceso de colas (Supervisor)

Importar, exportar y borrar tokens vencidos funcionan en segundo plano: sin este proceso los botones parecen
andar pero no hacen nada.

`/etc/supervisor/conf.d/legajos-worker.conf`

```ini
[program:legajos-worker]
command=php /var/www/legajos/artisan queue:work --tries=3 --timeout=300 --max-time=3600
directory=/var/www/legajos
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/legajos/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status           # legajos-worker tiene que figurar RUNNING
```

El worker carga el código y la configuración una sola vez al arrancar. **Después de cada actualización del código,
del `.env` o de las cachés, reiniciarlo:**

```bash
sudo supervisorctl restart legajos-worker
```

## 8. Tareas programadas (cron)

Cada minuto el servidor le pide a Laravel que revise su agenda (backups, recordatorios, auditoría, avisos).

```bash
sudo systemctl enable --now cron
systemctl is-active cron            # active
(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd /var/www/legajos && php artisan schedule:run >> /dev/null 2>&1") | sudo crontab -u www-data -
sudo crontab -u www-data -l         # tiene que mostrar solo esa línea
sudo -u www-data php artisan schedule:list
```

Tiene que ser el crontab del usuario `www-data`. Si algún día cambia la carpeta del sistema, hay que editar la línea.
Prueba opcional: agregar `* * * * * date >> /tmp/cron-prueba.log`, esperar dos minutos, mirar el archivo y sacar la línea.

## 9. Comprobación final

```bash
sudo supervisorctl status
sudo -u www-data php artisan sistema:backup
sudo -u www-data php artisan sistema:backup-verificar
```

Desde el navegador, en `https://tsce.legajos`:

1. Ingresar con el administrador y cambiar la clave provisoria.
2. Crear un usuario de cada rol y entrar con cada uno.
3. Cargar una persona con su legajo y subir un PDF.
4. Importar y exportar personas.
5. Descargar la ficha en PDF de un legajo.
6. Dar de baja un legajo (no se borra, cambia de estado).
7. En Auditoría tienen que figurar estas acciones.

## 10. Actualizar el sistema más adelante

```bash
cd /var/www/legajos
sudo supervisorctl stop legajos-worker
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
sudo chown -R www-data:www-data storage bootstrap/cache
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan filament:optimize
sudo supervisorctl start legajos-worker
sudo systemctl reload apache2
```

Después de cada `composer update` hay que volver a correr `npm run build`; si no, el panel se ve sin formato.

## 11. Guardar fuera del servidor

- `APP_KEY` y `BACKUP_ARCHIVE_PASSWORD`: sin ellas no se pueden abrir los documentos cifrados ni restaurar un backup.
- Una copia periódica de los backups (carpeta `storage/app/` de backups) en otro equipo o disco: si el disco del servidor
  falla, los backups guardados en el mismo equipo se pierden junto con el sistema.
- Probar al menos una vez la restauración de un backup en otra máquina.

## 12. Problemas frecuentes

| Síntoma | Causa probable | Solución |
|---|---|---|
| Error 500 al abrir el sistema | `storage/` o `bootstrap/cache` no son de `www-data`, o falta `vendor/` | Repetir el paso 4 y `composer install` |
| El panel se ve sin formato | Falta compilar el tema | `npm run build` |
| Importar o exportar no hace nada | El worker no está corriendo | `sudo supervisorctl status` y `start legajos-worker` |
| Exportar falla con rutas viejas en el mensaje | El worker tiene la configuración vieja en memoria | `sudo supervisorctl restart legajos-worker` |
| Exportar a Excel falla, CSV anda | Falta `php8.4-zip` | Instalarlo y reiniciar Apache y el worker |
| Nada se programa solo | `cron` apagado o línea mal cargada | Paso 8 |
| `Invalid command 'Header'` en Apache | Falta el módulo | `sudo a2enmod headers rewrite ssl` |
| Se ve el certificado de otro sitio | El sitio `legajos` no está activado | `sudo a2ensite legajos` y recargar Apache |
| `Call to undefined function ...fake()` | Se corrió un seeder de desarrollo en producción | No usar `db:seed` a secas; ver paso 5 |
| `Writing to directory /var/www/.config/psysh is not allowed` | Tinker no puede escribir su configuración | Anteponer `env HOME=/tmp` |
| PDF legítimo rechazado por "contenido activo" | El control encontró una palabra sospechosa | Ver en Auditoría cuál fue; el motivo queda registrado |
