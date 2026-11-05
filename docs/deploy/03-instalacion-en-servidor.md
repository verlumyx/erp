# Paso 3: Instalación del Proyecto en AWS EC2 (`/var/www/erp`)

Esta guía define la instalación y puesta en marcha del ERP en una instancia de **AWS EC2 (Ubuntu 22.04 / 24.04 LTS)** utilizando la ruta estándar web **`/var/www/erp`**, manteniendo la persistencia de datos de PostgreSQL y los respaldos en sus ubicaciones nativas de Linux.

---

## 1. Estructura de Directorios en el Servidor

| Directorio | Propósito | Justificación técnica | Propietario / Permisos |
| :--- | :--- | :--- | :--- |
| **`/var/www/erp`** | **Código y Compose del ERP** | Estándar clásico y ampliamente adoptado en entornos web Linux/Nginx. Agrupa la aplicación web fuera del `home` de usuarios individuales. | `ubuntu:docker` (permisos `775`) |
| **`/var/lib/postgresql/data`** | **Datos físicos de PostgreSQL** | Directorio nativo del sistema para bases de datos relacionales. Montado en el contenedor en `docker-compose.prod.yml`. | `70:70` (UID de Alpine Postgres, `700`) |
| **`/var/backups/erp`** | **Copias de seguridad** | Ubicación central para dumps generados antes de despliegues o mediante cron. | `ubuntu:docker` (permisos `770`) |

---

## 2. Paso a Paso: Comandos en el Servidor AWS EC2

### Paso 2.1: Conectarse al servidor por SSH

Desde tu terminal local:

```bash
ssh -i /ruta/a/tu-llave.pem ubuntu@IP_PUBLICA_EC2
```

---

### Paso 2.2: Crear los directorios y configurar permisos

Crea la estructura del sistema y asigna la propiedad a tu usuario `ubuntu` y al grupo `docker`:

```bash
# 1. Crear directorio del proyecto en /var/www/erp
sudo mkdir -p /var/www/erp
sudo chown -R ubuntu:docker /var/www/erp
sudo chmod 775 /var/www/erp

# 2. Crear directorio de persistencia para PostgreSQL (UID 70 en Alpine)
sudo mkdir -p /var/lib/postgresql/data
sudo chown -R 70:70 /var/lib/postgresql/data
sudo chmod 700 /var/lib/postgresql/data

# 3. Crear directorio para respaldos
sudo mkdir -p /var/backups/erp
sudo chown -R ubuntu:docker /var/backups/erp
sudo chmod 770 /var/backups/erp
```

---

### Paso 2.3: Generar llave SSH en el servidor y vincularla a GitHub

Para clonar y actualizar el repositorio sin ingresar usuario ni contraseña cada vez, genera una llave SSH en el servidor y regístrala como una **Deploy Key** en GitHub (o en tu cuenta):

#### 1. Generar la llave SSH (Ed25519 recomendada) en el servidor:
```bash
ssh-keygen -t ed25519 -C "servidor-aws-erp"
```
*(Presiona `Enter` para aceptar la ruta predeterminada `/home/ubuntu/.ssh/id_ed25519` y `Enter` dos veces más para dejarla sin passphrase)*.

#### 2. Mostrar y copiar la clave pública generada:
```bash
cat ~/.ssh/id_ed25519.pub
```
*(Copia todo el texto que empieza por `ssh-ed25519 AAAAC3...`)*.

#### 3. Registrar la llave en GitHub:
> [!TIP]
> **Mejor práctica de seguridad (Deploy Key):**
> 1. En GitHub, ve a tu repositorio: **Settings** -> **Deploy keys** -> **Add deploy key**.
> 2. En **Title**: pon `AWS EC2 ERP`.
> 3. En **Key**: pega el contenido copiado.
> 4. *(Opcional)* Deja desmarcado "Allow write access" (el servidor solo necesita leer el código para producción).
> 5. Clic en **Add key**.

#### 4. Probar la conexión SSH con GitHub desde el servidor:
```bash
ssh -T git@github.com
```
*(Escribe `yes` si te pregunta por el fingerprint del host. La respuesta esperada debe ser: `Hi user/repo! You've successfully authenticated...`)*.

---

### Paso 2.4: Clonar el Repositorio en `/var/www/erp`

Usa la URL en formato **SSH** (no HTTPS):

```bash
cd /var/www/erp
git clone git@github.com:<tu-usuario-u-organizacion>/<tu-repositorio>.git .
```

> [!NOTE]
> El punto final `.` clona los archivos directamente dentro de `/var/www/erp` sin crear una subcarpeta extra.

---

### Paso 2.5: Configurar el archivo `.env` de Producción

Copia la plantilla y edita las variables:

```bash
cp .env.example .env
nano .env
```

Ajusta las variables de entorno principales:

```dotenv
APP_NAME=ERP
APP_ENV=production
APP_KEY=                          # Se generará en el siguiente comando
APP_DEBUG=false
APP_URL=https://erp.tudominio.com # O tu IP pública si aún no tienes dominio

# Base de Datos (enlazada al contenedor postgres de producción)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=erp_prod
DB_USERNAME=erp_user
DB_PASSWORD=TuContrasenaSeguraDeProduccionAqui
POSTGRES_DATA_DIR=/var/lib/postgresql/data

# Cloudflare Tunnel (si usas túnel seguro de Cloudflare)
CLOUDFLARE_TUNNEL_TOKEN=tu_token_aqui

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

> [!IMPORTANT]
> **Notas sobre Resend y Sentry en Producción:**
> - **Resend:** No requiere librerías Composer adicionales. Utiliza el transporte SMTP seguro nativo (`smtps` por el puerto `465`). Recuerda verificar el dominio de envío (`valolabs.site` o tu propio dominio) en **[Resend Domains](https://resend.com/domains)** agregando los registros DNS correspondientes (SPF y DKIM) para prevenir que los servidores receptores reboten los correos.
>   - *Modo Sandbox (Desarrollo / Pruebas sin DNS):* Si necesitas enviar correos antes de validar el dominio, puedes usar `MAIL_FROM_ADDRESS="onboarding@resend.dev"`. Ten en cuenta que en ese modo Resend solo permite enviar mensajes a la dirección de correo con la que abriste tu cuenta en Resend.
> - **Sentry:** Si `SENTRY_LARAVEL_DSN` está configurado, las excepciones no controladas se capturan automáticamente. Al habilitar `SENTRY_ENABLE_LOGS=true` y añadir `sentry_logs` a `LOG_STACK`, los registros de error y advertencia de `Log::*` también se sincronizan con tu panel de Sentry.

Generar una clave de encriptación `APP_KEY` segura para producción:

```bash
docker run --rm -v $(pwd):/app -w /app composer:2 php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```
*(Copia el valor generado y pégalo en la variable `APP_KEY=` de tu `.env`)*.

---

### Paso 2.6: Activar Cloudflare Tunnel y Configurar el Subdominio (Paso Intermedio)

Para que el panel de Cloudflare Zero Trust detecte tu servidor y te permita registrar el subdominio (`Public Hostname`), el conector `cloudflared` debe iniciar sesión con el token previamente configurado en `.env`.

#### 1. Iniciar solo el contenedor del túnel:
```bash
docker compose -f docker-compose.prod.yml up -d cloudflared
```

#### 2. Comprobar que el túnel esté conectado con Cloudflare:
```bash
docker compose -f docker-compose.prod.yml logs cloudflared
```
*(Debe mostrar líneas con `Registered tunnel connection ... Connection ... is active`)*.

#### 3. Configurar el Subdominio en el panel de Cloudflare Zero Trust:
1. En el asistente web de Cloudflare, notarás que el estado del conector cambiará a **ACTIVE / CONNECTED** (punto verde).
2. Haz clic en **Next**.
3. En la pestaña **Public Hostname** (Nombre de host público):
   - **Subdomain:** El subdominio que desees (ej: `erp`).
   - **Domain:** Selecciona tu dominio en el desplegable (ej: `tudominio.com`).
   - **Path:** Déjalo vacío.
   - **Service:**
     - **Type:** `HTTP`
     - **URL:** `nginx:80`
4. Haz clic en **Save hostname** (o **Save Tunnel**).

> [!NOTE]
> Se especifica `nginx:80` porque `cloudflared` y `nginx` comparten la red interna de Docker `erp`. El tráfico viaja directamente al contenedor web sin necesidad de abrir puertos en el Security Group de AWS.

---

### Paso 2.7: Subir el Respaldo de Base de Datos al Servidor

Desde tu **máquina local** (donde tienes el dump `erp_backup_20261104_110520.dump`), transfiere el archivo vía `scp` a la carpeta de respaldos del servidor:

```bash
# Ejecutar en tu máquina local:
scp -i /ruta/a/tu-llave.pem erp_backup_20261104_110520.dump ubuntu@IP_PUBLICA_EC2:/var/backups/erp/erp_backup.dump
```

---

### Paso 2.8: Construir e Iniciar los Contenedores

De regreso en tu sesión SSH en `/var/www/erp`:

```bash
cd /var/www/erp

# 1. Construir la imagen optimizada de producción (PHP 8.5.12 + OPcache + build de Vite React)
docker compose -f docker-compose.prod.yml build

# 2. Levantar el resto de los servicios en segundo plano
docker compose -f docker-compose.prod.yml up -d
```

---

### Paso 2.9: Restaurar los Datos en PostgreSQL

Una vez que el contenedor de base de datos reporte estado saludable (`healthy`):

```bash
# Restaurar la estructura y datos desde el archivo .dump
docker compose -f docker-compose.prod.yml exec -T postgres pg_restore \
    -U erp_user \
    -d erp_prod \
    --clean \
    --if-exists \
    < /var/backups/erp/erp_backup.dump
```

---

### Paso 2.10: Optimización de Laravel y Enlace Simbólico

Ejecuta el enlace simbólico de uploads y la optimización de cachés dentro del contenedor `app`:

```bash
# Crear el enlace simbólico para archivos públicos (uploads, fotos, documentos)
docker compose -f docker-compose.prod.yml exec app php artisan storage:link

# Optimizar rutas, configuración y vistas
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

---

## 3. Comprobación y Verificación

```bash
# 1. Ver estado de contenedores
docker compose -f docker-compose.prod.yml ps

# 2. Revisar logs del contenedor Laravel
docker compose -f docker-compose.prod.yml logs -f app

# 3. Comprobar respuesta HTTP del endpoint de salud
curl -I http://localhost/health

# 4. Probar conexión SMTP con Resend
docker compose -f docker-compose.prod.yml exec app php artisan tinker --execute="app('mailer')->getSymfonyTransport()->start(); dump('Conexión SMTP exitosa con Resend');"

# 5. Probar envío de evento y reporte a Sentry
docker compose -f docker-compose.prod.yml exec app php artisan sentry:test
```

Respuesta esperada:
```http
HTTP/1.1 200 OK
Content-Type: text/plain
...
```

---

## 4. Flujo de Actualización en Despliegues Posteriores

Para actualizar la aplicación cuando subas nuevas funciones a Git:

```bash
cd /var/www/erp

# 1. Respaldo de seguridad previo
docker compose -f docker-compose.prod.yml exec -T postgres pg_dump -U erp_user -Fc erp_prod > /var/backups/erp/backup_pre_deploy_$(date +%Y%m%d_%H%M%S).dump

# 2. Descargar código nuevo
git pull origin main

# 3. Reconstruir contenedor de aplicación con los nuevos assets compilados
docker compose -f docker-compose.prod.yml build app
docker compose -f docker-compose.prod.yml up -d app

# 4. Re-optimizar cachés
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```
