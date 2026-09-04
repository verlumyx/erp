# ERP

Sistema de gestión modular: catálogo, inventario, logística, compras, ventas y tienda en línea.
Laravel 12 + Inertia 2 + React 19 + PostgreSQL 16, con arquitectura modular por dominio
(`app/Modules/<Módulo>`).

La documentación funcional de cada módulo está en [`docs/`](docs/README.md); las reglas que sigue
el código —convenciones, capas, política de no borrado— están en [`CLAUDE.md`](CLAUDE.md).

---

## Instalar en otra máquina

Todo corre dentro de Docker: **no hace falta instalar PHP, Composer, Node ni PostgreSQL en la
máquina**. Lo único necesario es Docker con Compose v2 (`docker compose`, no `docker-compose`),
git y —opcional pero recomendado— `make`.

Los comandos de abajo usan el `Makefile`; al lado de cada uno está su equivalente en Docker Compose
por si no tienes `make`.

### 1. Clonar y levantar

```bash
git clone https://github.com/verlumyx/erp.git
cd erp
make dev                 # docker compose up -d --build
```

`make dev` construye la imagen y levanta tres contenedores: `erp_app` (PHP-FPM + Node),
`erp_nginx` y `erp_postgres`. La primera vez tarda varios minutos porque compila las extensiones
de PHP e instala las dependencias dentro de la imagen.

Al arrancar, el contenedor de la aplicación **corre las migraciones solo** (ver
`docker/entrypoint.sh`), así que no hay que ejecutar `migrate` a mano en una instalación limpia.

Puertos publicados en la máquina anfitriona:

| Puerto | Qué es                                   |
|--------|------------------------------------------|
| `8080` | La aplicación — http://localhost:8080    |
| `5174` | Servidor de Vite (HMR) para `make watch` |
| `5435` | PostgreSQL, por si quieres conectar un cliente externo |

> **Configuración.** El contenedor lee [`.env.docker`](.env.docker), que **sí está versionado** y
> ya trae `APP_KEY` y los datos de la base. No hay que copiar `.env.example` ni generar la llave:
> ese `.env.example` es solo para una instalación sin Docker (ver el final).

### 2. Compilar el frontend

`public/build` no se versiona, así que hay que generarlo:

```bash
make assets              # docker compose exec app pnpm run build
```

Para desarrollar con recarga en caliente, en vez de `make assets`:

```bash
make watch               # docker compose exec app pnpm run dev
```

`make watch` deja el proceso ocupando la terminal y escribe `public/hot`; cuando termines,
`make assets` lo borra y vuelve a dejar los assets compilados. Si abres la app y ves un error de
Vite (`Unable to locate file in Vite manifest`), es que falta uno de los dos.

### 3. Datos iniciales

Las migraciones dejan la base vacía. El seeder carga los menús del sidebar, las monedas y un
usuario de prueba:

```bash
docker compose exec app php artisan db:seed --force
```

Eso crea `test@example.com` / `password`, pero **ese usuario todavía no puede entrar**: la
aplicación es multiempresa y cada sesión trabaja dentro de una empresa, así que el usuario necesita
una empresa, un rol y su membresía. La forma más corta de dejarlo listo es pegar este bloque en
tinker:

```bash
docker compose exec -T app php artisan tinker <<'PHP'
$user = App\Modules\User\Models\User::where('email', 'test@example.com')->firstOrFail();
$user->update(['is_system_owner' => true]);

$company = App\Modules\Company\Models\Company::create([
    'name' => 'Mi Empresa',
    'status' => 'active',
    'created_by' => $user->id,
]);

$role = App\Modules\Role\Models\Role::create([
    'company_id' => $company->id,
    'name' => 'Administrador',
    'status' => 'active',
    'permission_type' => 'all',
]);

App\Modules\Shared\Models\UserCompany::create([
    'user_id' => $user->id,
    'company_id' => $company->id,
    'role_id' => $role->id,
    'status' => 'active',
    'is_default' => true,
]);
PHP
```

`permission_type = 'all'` le da al rol todos los permisos sin depender del catálogo de módulos y
permisos, que en una instalación limpia está vacío (ver la nota de abajo). `is_system_owner` es lo
que habilita la pantalla de empresas para poder crear las demás desde la interfaz.

### 4. Enlazar el almacenamiento

Las fotos de la tienda y el logo de la empresa se guardan en el disco `public`:

```bash
docker compose exec app php artisan storage:link
```

### 5. Entrar

http://localhost:8080 → `test@example.com` / `password`.

No hay pantalla de registro (Fortify tiene el registro deshabilitado): los usuarios se crean desde
la propia aplicación, o con el bloque de tinker de arriba.

---

## Pasarle el proyecto a otro desarrollador

Los pasos de arriba dejan la aplicación funcionando, pero **vacía**: el catálogo de módulos y
permisos, los artículos, las bodegas y demás son datos, no esquema, y no vienen en el repositorio.
Si quieres que el otro desarrollador arranque con la misma base que tú, pásale un volcado.

En tu máquina:

```bash
make backup-db           # deja backup_AAAAMMDD_HHMMSS.sql en la raíz
```

En la suya, **antes del primer `make dev`** (con el volumen de PostgreSQL todavía sin crear):

```bash
cp backup_AAAAMMDD_HHMMSS.sql docker/postgres/init/
make dev
```

PostgreSQL ejecuta lo que encuentre en `docker/postgres/init/` la primera vez que inicializa su
volumen, así que la base arranca con tus datos y el `migrate` del entrypoint solo aplica lo que
falte. Si ya había levantado el proyecto, primero tiene que borrar el volumen con `make clean`
(destruye la base local) y volver a empezar.

Sobre una base ya creada también sirve `make restore-db file=backup.sql`, pero solo si está vacía:
sobre tablas ya migradas el volcado choca.

---

## Comandos del día a día

| Comando          | Qué hace                                                       |
|------------------|----------------------------------------------------------------|
| `make dev`       | Levanta (y reconstruye) todo el stack                          |
| `make status`    | Estado de los contenedores                                     |
| `make assets`    | Compila el frontend una vez                                    |
| `make watch`     | Recompila el frontend en cada cambio (HMR)                     |
| `make migrate`   | Corre migraciones pendientes                                   |
| `make app-shell` | Abre una shell dentro del contenedor de la aplicación          |
| `make db-shell`  | Abre `psql` contra la base de desarrollo                       |
| `make install`   | Reinstala dependencias de Composer y pnpm dentro del contenedor|
| `make backup-db` | Vuelca la base a un `.sql`                                     |
| `make clean`     | Baja todo y **borra los volúmenes** (se pierde la base local)  |

Calidad de código, siempre dentro del contenedor:

```bash
docker compose exec app vendor/bin/pint --dirty      # formato PHP (obligatorio antes de commitear)
docker compose exec app pnpm run types               # TypeScript
docker compose exec app pnpm run lint                # ESLint
```

### Tests

Los tests corren sobre SQLite en memoria (lo configura `phpunit.xml`). **Pasa siempre una
carpeta**: sin ruta, PHPUnit carga la suite entera y el proceso se queda sin memoria
(`Allowed memory size ... exhausted`), incluso subiendo `memory_limit`.

```bash
make test args="tests/Feature/SalesReturn --compact"
make test args="tests/Feature/SalesReturn --filter=SalesReturnCreate --compact"
make test args="tests/Unit --compact"
```

Un `--filter` suelto, sin la carpeta, tampoco sirve: el filtro se aplica después de cargar todo.

---

## Instalación sin Docker (opcional)

Solo si prefieres correr todo en la máquina. Hacen falta PHP 8.5 con las extensiones
`pdo_pgsql`, `mbstring`, `intl`, `bcmath`, `zip`, `gd` y `pcntl`, Composer, Node con
pnpm 11 y un PostgreSQL 16 accesible.

```bash
cp .env.example .env     # y ajusta DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
pnpm install
composer run dev         # servidor, colas, logs y Vite a la vez
```

Después, el bloque de tinker del paso 3 (sin el `docker compose exec`) para crear la empresa y el
rol.

> `gd` no es opcional si vas a usar el módulo de tienda: es lo que redimensiona las fotos de los
> artículos. Si la imagen de Docker que tienes es vieja y `php -m` no la lista, reconstrúyela con
> `docker compose build app`.
