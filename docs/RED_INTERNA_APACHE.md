# Solo red interna: configuración de Apache2

El sistema tiene dos barreras. Las dos usan la misma lista de redes: **hay que cargarla en los dos lugares**.

1. **Apache (principal):** lo de afuera ni llega a PHP.
2. **La aplicación (`RED_INTERNA_ACTIVA=true`):** si Apache se configura mal o alguien llega por otro camino, Laravel igual rechaza con 403 y deja el intento en la auditoría (se anota una vez cada 10 minutos por IP).

## 1. Apache

Cambiá los rangos por los reales del Tribunal. Dentro del `VirtualHost` del sistema (por ejemplo `/etc/apache2/sites-available/legajos.conf`):

```apache
<VirtualHost *:443>
    ServerName tsce.legajos
    DocumentRoot /var/www/legajos/public

    <Directory /var/www/legajos/public>
        AllowOverride All
        Options -Indexes

        # solo la red interna (más la propia máquina)
        <RequireAny>
            Require local
            Require ip 10.0.0.0/8
            Require ip 172.16.0.0/12
            Require ip 192.168.0.0/16
        </RequireAny>
    </Directory>

    # lo que está fuera de public no se sirve nunca
    <DirectoryMatch "^/var/www/legajos/(?!public)">
        Require all denied
    </DirectoryMatch>
</VirtualHost>
```

Cargar y probar:

```bash
sudo a2enmod ssl headers rewrite
sudo apache2ctl configtest        # tiene que decir Syntax OK
sudo systemctl reload apache2
```

Si además querés cortar en el firewall (recomendado):

```bash
sudo ufw allow from 10.0.0.0/8 to any port 443 proto tcp
sudo ufw allow from 172.16.0.0/12 to any port 443 proto tcp
sudo ufw allow from 192.168.0.0/16 to any port 443 proto tcp
sudo ufw deny 443/tcp
```
(ufw evalúa en orden: las líneas `allow` tienen que estar antes que `deny`.)

## 2. La aplicación (`.env`)

```
RED_INTERNA_ACTIVA=true
RED_INTERNA_PERMITIDAS=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
```

Después `php artisan config:clear` (o `config:cache` en producción).

### Si hay un proxy o balanceador delante de Apache
Apache y Laravel van a ver la IP del proxy, no la del usuario, y todos parecerían "internos" (o ninguno).
- En Laravel: `TRUSTED_PROXIES=` con la IP del proxy (no uses `*` si el servidor es alcanzable directamente).
- En Apache: `a2enmod remoteip` y en el VirtualHost `RemoteIPHeader X-Forwarded-For` y `RemoteIPInternalProxy <ip del proxy>`, para que `Require ip` mire la IP real.

## 3. Cómo comprobarlo
- Desde una PC de la red interna: el sistema abre normal.
- Desde afuera (datos del celular): 403 de Apache.
- Para probar solo la barrera de Laravel, poné en `RED_INTERNA_PERMITIDAS` una red que NO sea la tuya y entrá: tiene que verse el 403 "solo se puede usar desde la red interna" y aparecer "Acceso denegado" en la auditoría. Si te quedás afuera, entrando al servidor: `RED_INTERNA_ACTIVA=false` y `php artisan config:clear`.
- La propia máquina (`127.0.0.1`) siempre pasa, así que `php artisan` y las pruebas locales no se bloquean.
