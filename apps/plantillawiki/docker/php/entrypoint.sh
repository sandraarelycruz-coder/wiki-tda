#!/bin/sh
set -e

set_laravel_permissions() {
    mkdir -p storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod 775 {} +
    find storage bootstrap/cache -type f -exec chmod 664 {} +
}

echo "==> Configurando permisos de Laravel..."
set_laravel_permissions

echo "==> Instalando dependencias de Composer..."
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "==> Dependencias de Composer ya instaladas; se omite la instalación."
fi

if [ -z "${APP_KEY:-}" ] && ! grep -qE '^APP_KEY=.+$' .env 2>/dev/null; then
    echo "==> Generando APP_KEY..."
    php artisan key:generate --no-interaction
else
    echo "==> APP_KEY ya configurada; se omite la generación."
fi

echo "==> Iniciando PHP-FPM..."
exec php-fpm