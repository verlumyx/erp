# Paso 2: Preparación del Servidor (Linux / AWS EC2)

Esta guía documenta la preparación del sistema operativo base (enfocado en **Ubuntu Server 22.04 / 24.04 LTS en AWS EC2**) para ejecutar el ERP en contenedores Docker de forma estable, segura y optimizada.

---

## 1. Configuración de Memoria de Intercambio (SWAP)

> [!IMPORTANT]
> En instancias EC2 de tamaño pequeño o mediano (`t2.micro`, `t3.micro`, `t3.small` con 1 GB a 2 GB de memoria RAM), la compilación de la imagen Docker (compilación de extensiones PHP como `gd`, instalación de paquetes Composer y construcción del bundle frontend de React con Vite) consume más de 1.5 GB de RAM. Sin SWAP, el proceso será abortado abruptamente por el sistema operativo (**OOM Killer**).

### 1.1 Crear el archivo de intercambio (2 GB a 4 GB)

```bash
# Crear un archivo swap de 2 GB (o 4 GB si tu máquina es de 1 GB RAM)
sudo fallocate -l 2G /swapfile

# Asignar permisos estrictos de solo lectura/escritura para root
sudo chmod 600 /swapfile

# Formatear el archivo como espacio de intercambio
sudo mkswap /swapfile

# Habilitar el swap
sudo swapon /swapfile
```

### 1.2 Hacer permanente el SWAP tras reinicios

Agrega la entrada al archivo `/etc/fstab`:

```bash
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### 1.3 Ajustar la agresividad del intercambio (Swappiness)

Para servidores web en producción, se recomienda que el sistema operativo prefiera usar la RAM real y solo recurra al swap cuando sea indispensable:

```bash
# Ajustar swappiness a 10 (por defecto suele venir en 60)
sudo sysctl vm.swappiness=10

# Persistir la configuración en /etc/sysctl.conf
echo 'vm.swappiness=10' | sudo tee -a /etc/sysctl.conf
```

### 1.4 Verificación

```bash
free -h
# o también:
swapon --show
```

---

## 2. Actualización del Sistema Operativo

Actualiza la lista de paquetes y aplica los parches de seguridad más recientes:

```bash
sudo apt update && sudo apt upgrade -y
```

---

## 3. Instalación de Herramientas Base

Instala utilidades indispensables para la administración del servidor y el ciclo de vida del despliegue:

```bash
sudo apt install -y git make curl ca-certificates gnupg htop unzip
```

- **git:** Clonación y actualización del repositorio.
- **make:** Ejecución de comandos abreviados del [`Makefile`](../../Makefile) (`make dev-build`, `make assets`, etc.).
- **curl, ca-certificates, gnupg:** Requeridos para descargar e importar llaves seguras de Docker.
- **htop:** Monitoreo visual de CPU y memoria en tiempo real.

---

## 4. Instalación de Docker Engine Oficial y Docker Compose v2

> [!CAUTION]
> No instales los paquetes obsoletos `docker.io` ni `docker-compose` de los repositorios antiguos de Ubuntu. El proyecto requiere Docker Compose v2 (`docker compose`, no `docker-compose`).

### 4.1 Añadir la llave GPG oficial de Docker

```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
```

### 4.2 Configurar el repositorio oficial

```bash
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

### 4.3 Instalar Docker y el plugin Compose v2

```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

### 4.4 Habilitar el servicio para inicio automático

```bash
sudo systemctl enable --now docker
```

---

## 5. Permisos de Usuario para Docker (Sin `sudo`)

Para evitar tener que anteponer `sudo` en cada instrucción de Docker:

```bash
sudo usermod -aG docker $USER
```

> [!NOTE]
> Cierra la sesión SSH activa (`exit`) y conéctate de nuevo para que los cambios en los grupos del usuario surtan efecto.

---

## 6. Preparación de Rutas de Persistencia en el Host (Producción)

Si vas a utilizar [`docker-compose.prod.yml`](../../docker-compose.prod.yml) con persistencia física en el sistema de archivos del servidor (en lugar de volúmenes gestionados anónimos de Docker), crea la carpeta para PostgreSQL y asigna el propietario correspondiente al contenedor Alpine (UID 70):

```bash
# Crear directorio físico para PostgreSQL
sudo mkdir -p /var/lib/postgresql/data

# UID/GID 70 corresponde al usuario 'postgres' en imágenes Alpine
sudo chown -R 70:70 /var/lib/postgresql/data
sudo chmod 700 /var/lib/postgresql/data
```

---

## 7. Verificación Final de la Preparación

Ejecuta estas comprobaciones para confirmar que el servidor está listo para recibir el código del ERP:

| Comando | Salida esperada |
| :--- | :--- |
| `docker --version` | `Docker version 27.x.x` (o superior) |
| `docker compose version` | `Docker Compose version v2.x.x` |
| `git --version` | Versión instalada de Git |
| `make --version` | GNU Make 4.x |
| `free -h` | Debe mostrar `Swap: ... 2.0Gi` (o más) disponible |
| `docker ps` | Lista de contenedores vacía sin errores de permisos |

Una vez completado este paso, el servidor está listo para continuar con la configuración del túnel (Cloudflare), clonación del repositorio y levantamiento de la aplicación.
