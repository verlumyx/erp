# Despliegue en Producción (Docker Compose)

Este documento detalla los pasos para desplegar la aplicación ERP en un servidor de producción utilizando Docker Compose y guardando los datos de PostgreSQL en la ruta física nativa del servidor.

---

## 1. Requisitos Previos en el Servidor

> Para una guía paso a paso completa sobre configuración de Swap, repositorios oficiales de Docker y permisos en Ubuntu/EC2, consulta: **[02-preparacion-servidor.md](02-preparacion-servidor.md)**.

- **Docker Engine** (24.0+ con Compose v2 plugin `docker compose`).
- **Directorio de datos de PostgreSQL en el host**:
  Por defecto, en servidores Linux los datos de PostgreSQL residen en `/var/lib/postgresql/data` (o `/var/lib/postgresql/16/main` si se usa el paquete de Ubuntu/Debian).

Crear la carpeta en el servidor con los permisos para el usuario `postgres` de la imagen (UID 70 en Alpine):

```bash
sudo mkdir -p /var/lib/postgresql/data
sudo chown -R 70:70 /var/lib/postgresql/data
sudo chmod 700 /var/lib/postgresql/data
```

> **Nota:** Si tu servidor ya tiene un PostgreSQL instalado o prefieres otra ruta, puedes definir la variable `POSTGRES_DATA_DIR` en tu archivo `.env` (ej: `POSTGRES_DATA_DIR=/var/lib/postgresql/16/main`).

---

## 2. Configurar Variables de Entorno

Copiar la plantilla base a `.env` y configurar las claves y credenciales:

```bash
cp .env.example .env
```

Editar `.env`:
- Generar una `APP_KEY` segura (`php artisan key:generate --show`).
- Configurar `APP_ENV=production` y `APP_DEBUG=false`.
- Configurar `APP_URL=https://tudominio.com`.
- Configurar `DB_CONNECTION=pgsql`, `DB_HOST=postgres`, `DB_PORT=5432`, `DB_DATABASE=erp_prod`, `DB_USERNAME=erp_user` y `DB_PASSWORD`.
- Ajustar `POSTGRES_DATA_DIR` si deseas usar una ruta distinta a `/var/lib/postgresql/data`.
- Configurar `CLOUDFLARE_TUNNEL_TOKEN` si se utiliza túnel.

### 2.1 Activar Cloudflare Tunnel (Paso previo al subdominio)

Si estás usando Cloudflare Zero Trust, el panel web necesita detectar el conector activo antes de permitir configurar el subdominio:

```bash
# 1. Iniciar el conector de túnel
docker compose -f docker-compose.prod.yml up -d cloudflared

# 2. Revisar conexión
docker compose -f docker-compose.prod.yml logs cloudflared
```

En el dashboard de Cloudflare (Public Hostname):
- **Type:** `HTTP`
- **URL:** `nginx:80`

---

## 3. Construir e Iniciar en Producción

Construir la imagen de producción (utiliza el Stage 3 `production` de Dockerfile con OPcache y assets compilados sin dependencias de desarrollo):

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

---

## 4. Restaurar Base de Datos desde el archivo `.dump`

Para restaurar el respaldo `.dump` generado en desarrollo hacia la base de datos de producción:

```bash
docker compose -f docker-compose.prod.yml exec -T postgres pg_restore \
    -U erp_user \
    -d erp_prod \
    --clean \
    --if-exists \
    < erp_backup.dump
```

---

## 5. Mantenimiento y Comandos Útiles

- **Ver estado de los contenedores:**
  ```bash
  docker compose -f docker-compose.prod.yml ps
  ```

- **Ver logs:**
  ```bash
  docker compose -f docker-compose.prod.yml logs -f app
  ```

- **Generar un nuevo respaldo .dump en producción:**
  ```bash
  docker compose -f docker-compose.prod.yml exec -T postgres pg_dump -U erp_user -Fc erp_prod > backup_$(date +%Y%m%d_%H%M%S).dump
  ```
