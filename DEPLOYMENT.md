# Despliegue

Esta guia resume los puntos que deben validarse antes de pasar el sistema a produccion.

## Produccion

- PHP 8.2+.
- Laravel 12.
- Node.js LTS para compilar assets.
- MySQL 8+ o MariaDB compatible.
- Servidor web apuntando a `public/`.
- Variables basadas en `.env.production.example`.

## Preparacion

1. Configurar `.env` de produccion con `APP_ENV=production` y `APP_DEBUG=false`.
2. Generar `APP_KEY` con `php artisan key:generate`.
3. Configurar MySQL/MariaDB y credenciales reales.
4. Ejecutar `composer install --no-dev --optimize-autoloader`.
5. Ejecutar `npm ci` y `npm run build`.
6. Ejecutar `php artisan migrate --force`.
7. Ejecutar `php artisan storage:link` si el servidor no tiene el enlace `public/storage`.
8. Ejecutar seeders controlados solo cuando corresponda.
9. Optimizar Laravel con `php artisan optimize`.

## Comandos sugeridos

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan permission:cache-reset
php artisan optimize
```

## Validacion obligatoria

- Migraciones probadas en la misma version de MySQL/MariaDB del servidor.
- Login operativo.
- Roles y permisos cargados.
- Dashboard protegido.
- Escritura en `storage/` y `bootstrap/cache/`.
- PDFs generados correctamente.
- Zona horaria y moneda revisadas.

## Variables importantes

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_URL` con el dominio real.
- `APP_TIMEZONE=America/Lima`.
- `SESSION_ENCRYPT=true`.
- `DB_*` con usuario restringido de base de datos.
- `MAIL_*` con proveedor real si se enviaran correos.
- `ADMIN_EMAIL` y `ADMIN_PASSWORD` antes de ejecutar seeders en produccion.
