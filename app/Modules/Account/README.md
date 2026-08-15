# Módulo 5 — Inventario (Account / Profile)

Gestión del inventario de **cuentas** (`Account`) de servicios de streaming y sus
**perfiles** (`Profile`), líneas anidadas tipo factura/items. Depende del Módulo 4
(Service): cada cuenta pertenece a un servicio y hereda de él cuántos perfiles genera.

Nomenclatura: identificadores, tablas, columnas, rutas y permisos en **inglés**
(`account`/`profile`, `app_accounts`/`app_profiles`); etiquetas de UI en **español**
(Sidebar: **Inventario › Cuentas**).

## Entidades

### Account (cabecera) — tabla `app_accounts`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | uuid v7 | PK |
| `company_id` | uuid | FK → `app_companies` (cascade) |
| `code` | string(12) | secuencial por compañía, prefijo `ACC` (ACC000001…) |
| `service_id` | uuid | FK → `app_services` **ON DELETE RESTRICT** |
| `email` | varchar(255) | account del service |
| `password_encrypted` | text | cifrado con **Laravel Crypt** (cast `encrypted`); nunca se serializa |
| `cost` | decimal(10,2) | CHECK ≥ 0 |
| `purchase_date` | date | |
| `next_renewal` | date | ≥ `purchase_date` |
| `status` | varchar(20) | `active` \| `down` \| `maintenance` \| `cancelled` (default `active`) |
| `notes` | text nullable | |

Únicos: `(company_id, code)` y `(service_id, email)`.

### Profile (línea) — tabla `app_profiles`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | uuid v7 | PK |
| `account_id` | uuid | FK → `app_accounts` **ON DELETE CASCADE** |
| `number` | smallint | CHECK ≥ 1 |
| `pin` | varchar(10) nullable | |
| `status` | varchar(20) | `available` \| `occupied` \| `maintenance` (default `available`) |
| `notes` | text nullable | |

Único: `(account_id, number)`.

## Endpoints (anidados bajo `/{company}`)

| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| GET | `/accounts` | `accounts.index` | Listar (filtros: `code`, `email`, `status`, `service_id`) |
| GET | `/accounts/create` | `accounts.create` | Form de creación |
| POST | `/accounts` | `accounts.store` | Crear |
| GET | `/accounts/{id}` | `accounts.show` | Ver (incluye `profiles` + `profiles_summary`) |
| GET | `/accounts/{id}/edit` | `accounts.edit` | Form de edición |
| GET | `/accounts/{id}/credentials` | `accounts.credentials` | Credenciales descifradas (JSON) |
| PUT | `/accounts/{id}` | `accounts.update` | Actualizar cabecera + profiles (incluye status) |

## Reglas de negocio

1. **Auto-generación de profiles.** Al crear una account se generan automáticamente
   `N` profiles (`1..N`, status `available`), donde `N = service.max_profiles`.
   Todo ocurre dentro de una transacción (`AccountRepository::create`).

2. **PINs precargados (opcional).** `POST /accounts` acepta un array opcional
   `profiles: [{ number, pin }]` para precargar PINs. Si se omite, los profiles
   nacen sin PIN. Cada `number` se valida en el rango `1..max_profiles` del service
   (`CreateAccountRequest`).

3. **Update con profiles en una sola llamada.** `PUT /accounts/{id}` acepta la
   cabecera más `profiles: [{ number, pin?, status?, notes? }]` y actualiza varios
   profiles a la vez. Todo en una transacción (`AccountRepository::update`). Cada
   `number` debe pertenecer a la account.

4. **Transiciones de status de profile** (validadas en `AccountUpdateService`):
   - `available → occupied | maintenance`
   - `occupied → available | maintenance`
   - `maintenance → available | occupied`

5. **Credenciales.** `password_encrypted` no se devuelve en ninguna respuesta por
   defecto (`$hidden` en el modelo + ausente en `AccountResource`). Solo
   `GET /accounts/{id}/credentials` la devuelve descifrada, restringido a
   admin/supervisor (permiso `accounts.credentials`). Cada acceso se registra con
   `Log::info('account.credentials.accessed', …)` como hook para un futuro log de
   auditoría persistente.

6. **Resumen de profiles.** `GET /accounts/{id}` incluye
   `profiles_summary: { total, available, occupied, maintenance }`.

## Autorización

Basada en permisos por rol (`accounts.*`):

- **admin / supervisor** — roles con los permisos `accounts.*` (incluido
  `accounts.credentials`): CRUD completo + ver credentials.
- **agente** — rol sin permisos `accounts.*`: no accede al módulo.

`AccountPolicy` mapea las habilidades (`viewAny`, `view`, `create`, `update`,
`viewCredentials`) a esos permisos y está registrada vía
`Gate::policy` en `AccountServiceProvider`. El status de la account se modifica
desde el formulario de edición (`accounts.update`); no existe un endpoint
dedicado de cambio de status.

Los permisos se siembran en `database/sql/seed_initial_modules.sql` (módulo
`accounts`, etiqueta «Inventario») y el menú en `MenuSeeder` (grupo «Inventario» ›
«Accounts»).

## Frontend (implementado)

El flujo es **tipo factura/items**: una sola pantalla de Account con la **cabecera**
(service, email, password, cost, fechas, status, notes) más una **tabla de
profiles editable inline** (número, PIN, status, notes). **No existe una pantalla
independiente de Profiles** — se crean/actualizan siempre junto con su account.

Páginas en `resources/js/pages/accounts/` (Inertia + React, patrón Context API):

- `index.tsx` + `components/AccountList.tsx` — listado con filtros (email, código,
  status, service).
- `create.tsx` — al elegir el service se generan en el formulario las filas de
  profile `1..max_profiles` para precargar PINs; el backend genera igualmente todos
  los profiles.
- `edit.tsx` — envía la cabecera completa + la tabla de profiles (PIN/status/notes)
  en una sola llamada transaccional. El service no es editable.
- `show.tsx` — cabecera, mini-stats del `profiles_summary`, tabla de profiles y el
  bloque de credentials.
- `components/AccountForm.tsx` — formulario compartido (cabecera + tabla inline),
  consume `AccountFormContext`.
- `components/AccountCredentials.tsx` — botón «Ver credentials» que consume
  `GET /accounts/{id}/credentials` (endpoint JSON; el backend autoriza y registra
  el acceso). Si el usuario no tiene permiso, muestra el aviso de 403.

Permisos: el árbol de permisos de roles es data-driven (lee
`app_modules`/`app_permissions`), por lo que el módulo «Inventario» y sus acciones
`accounts.*` aparecen automáticamente tras sembrar `seed_initial_modules.sql`.
