# Wiki TDA

Monorepositorio de una API Laravel y un frontend Vue 3, con servicios locales gestionados mediante Docker Compose.

## Componentes

- `apps/plantillawiki`: API Laravel, PHP 8.4 y PostgreSQL.
- `apps/plantillawiki-front`: frontend Vue 3 + Vite.
- `docker-compose.yml`: frontend, PHP-FPM, Nginx, PostgreSQL, MongoDB y Redis.

## Requisitos

- Docker y Docker Compose.
- Node.js y pnpm para ejecutar tareas del frontend desde el host.
- PHP y Composer si se ejecuta o prueba el backend fuera de Docker.

## Inicio con Docker

Desde la raíz del repositorio:

```bash
docker compose up -d --build
```

El contenedor PHP ejecuta las migraciones al iniciar. Las aplicaciones quedan disponibles en:

- Frontend: http://localhost:3006
- API Laravel: http://localhost:8006
- PostgreSQL desde el host: `localhost:5439`

Las credenciales de desarrollo local de PostgreSQL están definidas en `docker-compose.yml`. No reutilices esas credenciales en producción.

## Comandos útiles

```bash
# Ver estado y registros de los servicios
docker compose ps
docker compose logs -f php nginx frontend

# Ejecutar migraciones o pruebas del backend
docker compose exec php php artisan migrate
docker compose exec php php artisan test

# Instalar dependencias JavaScript y construir el frontend
pnpm install
pnpm --filter plantilla-front build

# Ejecutar las pruebas Laravel desde el host
pnpm test
```

Para ejecutar el backend fuera de Docker, copia `apps/plantillawiki/.env.example` a `apps/plantillawiki/.env`, configura las credenciales para tu entorno y consulta los comandos estándar de Laravel/Composer.
