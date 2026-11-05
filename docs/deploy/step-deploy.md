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

Editar `.env` ajustando los siguientes bloques clave:

### 2.1 Aplicación y Base de Datos
- Generar una `APP_KEY` segura (`php artisan key:generate --show`).
- Configurar `APP_ENV=production` y `APP_DEBUG=false`.
- Configurar `APP_URL=https://tudominio.com`.
- Configurar `DB_CONNECTION=pgsql`, `DB_HOST=postgres`, `DB_PORT=5432`, `DB_DATABASE=erp_prod`, `DB_USERNAME=erp_user` y `DB_PASSWORD`.
- Ajustar `POSTGRES_DATA_DIR` si deseas usar una ruta distinta a `/var/lib/postgresql/data`.

### 2.2 Servicio de Correos Electrónicos con Resend (SMTP Seguro)
La aplicación utiliza el transporte nativo de Symfony Mailer con cifrado SSL/TLS directo en el puerto 465 (sin necesidad de paquetes SDK externos). Se utiliza principalmente para correos transaccionales y recuperación de contraseñas (`/forgot-password`).

```dotenv
# ==========================================
# Envío de Correos con Resend (SMTP nativo)
# ==========================================
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=re_tu_api_key_de_resend_aqui
RESEND_API_KEY=re_tu_api_key_de_resend_aqui
MAIL_FROM_ADDRESS="no_replay@valolabs.site"
MAIL_FROM_NAME="${APP_NAME}"

# Destinatarios de notificaciones al solicitar acceso a la Demo (separados por coma)
DEMO_NOTIFICATION_EMAILS="ggomez@valolabs.site,hola@valolabs.site"
```

> [!IMPORTANT]
> **Dominio y remitente en Resend:**
> - **Producción (Dominio propio):** Para enviar correos desde `no_replay@valolabs.site` o tu propio dominio a cualquier destinatario sin rechazos (error `550`), el dominio debe estar previamente verificado en el panel de **[Resend Domains](https://resend.com/domains)** con los registros DNS correspondientes (SPF, DKIM).
> - **Entornos de Desarrollo / Sandbox (`onboarding@resend.dev`):** Si estás probando la aplicación en local o en un entorno de desarrollo antes de validar el dominio en DNS, puedes configurar temporalmente:
>   ```dotenv
>   MAIL_FROM_ADDRESS="onboarding@resend.dev"
>   ```
>   *Nota sobre el sandbox:* En este modo de pruebas, Resend únicamente permite enviar correos hacia **la misma dirección de correo con la que creaste tu cuenta en Resend**. Si intentas enviar a cualquier otro destinatario, Resend responderá con `550`.

### 2.3 Monitoreo de Errores y Logs con Sentry
Sentry captura automáticamente todas las excepciones no controladas de Laravel, transacciones de rendimiento y opcionalmente logs de la aplicación.

```dotenv
# ==========================================
# Monitoreo de Errores y Logs con Sentry
# ==========================================
SENTRY_LARAVEL_DSN=https://tu_dsn_publico@oXXXXXX.ingest.us.sentry.io/tu_proyecto_id
SENTRY_ENABLE_LOGS=true
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_SEND_DEFAULT_PII=false

# Canal de logs de Laravel (agrega sentry_logs al stack)
LOG_CHANNEL=stack
LOG_STACK=single,sentry_logs
```

- **`SENTRY_LARAVEL_DSN`**: Clave DSN de tu proyecto en Sentry. Si se deja vacía, Sentry permanece deshabilitado sin afectar la aplicación.
- **`SENTRY_ENABLE_LOGS=true`**: Habilita el canal `sentry_logs` dentro de Monolog para enviar registros de `Log::error`, `Log::warning`, etc.
- **`SENTRY_TRACES_SAMPLE_RATE`**: Ratio de muestreo de trazas de rendimiento. Se recomienda entre `0.1` y `0.5` en producción para optimizar cuotas.
- **`SENTRY_SEND_DEFAULT_PII=false`**: Si es `true`, asocia la IP y datos del usuario autenticado a los eventos de error.

### 2.4 Activar Cloudflare Tunnel (Paso previo al subdominio)

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

- **Probar conexión SMTP con Resend:**
  ```bash
  docker compose -f docker-compose.prod.yml exec app php artisan tinker --execute="app('mailer')->getSymfonyTransport()->start(); dump('Conexión SMTP exitosa con Resend');"
  ```

- **Probar envío de evento y reporte a Sentry:**
  ```bash
  docker compose -f docker-compose.prod.yml exec app php artisan sentry:test
  ```
