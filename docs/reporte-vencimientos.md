# Reporte de Vencimientos y Renovaciones

## Objetivo

Listar las ventas próximas a vencer y las ya vencidas sin renovar, para que el
equipo pueda contactar al cliente **antes** de perderlo. Es el reporte más
accionable del negocio de suscripciones: convierte el `end_date` de cada venta
en una acción comercial concreta (renovar).

## Valor para el negocio

- Reduce la fuga de clientes anticipando los vencimientos.
- Conecta con la definición de **"Por cobrar"** del Dashboard (ventas `expired`).
- Reutiliza el flujo de renovación ya existente (`SaleRenewDialog` +
  `SaleRenewal`), así que desde el reporte se puede renovar directamente.
- Permite medir la **tasa de renovación** (ventas renovadas / ventas vencidas).

## Fuente de datos

Modelo principal: `App\Modules\Sale\Models\Sale` (tabla `app_sales`).

Campos relevantes:

| Campo            | Uso en el reporte                                   |
|------------------|-----------------------------------------------------|
| `end_date`       | Fecha de vencimiento (eje del reporte)              |
| `status`         | `active`, `expired`, `cancelled`                    |
| `price`          | Monto a cobrar en la renovación                     |
| `client_id`      | Cliente a contactar                                 |
| `service_id`     | Servicio vendido                                    |
| `plan_id`        | Plan vendido                                        |
| `agent_id`       | Vendedor responsable del seguimiento               |
| `cancelled_at`   | Para excluir/visualizar canceladas                  |

Relaciones a cargar (eager loading para evitar N+1):
`client`, `service`, `plan`, `agent`, `renewals`.

Scopes ya disponibles en el modelo `Sale`:

- `scopeActive(Builder $query)`
- `scopeExpired(Builder $query)`
- `scopeExpiringSoon(Builder $query, int $days = 7)` — activas que vencen entre
  hoy y hoy+N días.
- `scopeOfAgent`, `scopeOfService`, `scopeOfClient`

Método de instancia útil: `Sale::isInGracePeriod()` y `Sale::canBeRenewed()`
(usa `config('sales.grace_period_days')`).

## Filtros

- **Rango de días por vencer** (`days`): próximos 7 / 15 / 30 días. Por defecto 7.
- **Rango de fechas explícito** (`date_from`, `date_to`) sobre `end_date`
  (opcional, alternativo al rango de días).
- **Estado** (`status`): `expiring` (activas por vencer) | `expired` (vencidas
  sin renovar) | `all`.
- **Servicio** (`service_id`) — opcional.
- **Agente** (`agent_id`) — opcional.
- Paginación: `limit` / `offset` (mismo patrón que el reporte de ingresos).
- `searched` (boolean): el reporte no consulta hasta que el usuario filtra,
  igual que `IncomeExpenseGetController`.

## Métricas / Resumen (cards)

Calculadas en un service dedicado tipo `ExpirationSummaryService`:

- `expiring_count` — nº de ventas por vencer en el rango.
- `expiring_amount` — `SUM(price)` de las ventas por vencer.
- `expired_count` — nº de ventas vencidas sin renovar.
- `expired_amount` — `SUM(price)` de las vencidas (= base de "Por cobrar").
- `renewal_rate` — % de ventas vencidas en el periodo que sí fueron renovadas
  (cruzando con `app_sale_renewals`).

## Salida (tabla)

Una fila por venta, ordenada por `end_date` ascendente (las más urgentes
arriba):

`code` · `client` · `service` / `plan` · `price` · `end_date` ·
`días restantes` (negativo si ya venció) · `status` · `agent` ·
acción **Renovar** (abre `SaleRenewDialog`).

Resaltar visualmente:
- Rojo: ya vencidas (`expired`).
- Ámbar: vencen en ≤ 3 días.
- En periodo de gracia: badge (usa `isInGracePeriod()`).

## Implementación sugerida (arquitectura modular)

Módulo: `app/Modules/Report` (reutilizar el existente).

```
app/Modules/Report/
├── Controllers/
│   └── ExpirationGetController.php       # index(): Inertia::render('reports/expirations/index', ...)
├── Services/
│   └── ExpirationSummaryService.php      # totales y renewal_rate
└── routes.php                            # nueva ruta
```

Para la consulta paginada del listado se puede:
- Añadir un `SearchService` / método de repositorio en el módulo `Sale`
  (preferido, mantiene la query junto a su modelo), o
- Resolver vía los scopes existentes desde un service del módulo Report.

### Controller (patrón a seguir)

Calcado de `IncomeExpenseGetController`:

```php
abort_unless($request->user()?->hasPermission('reports.expirations') ?? false, 403);
```

- Lee filtros con valores por defecto (`days = 7`, `status = 'expiring'`).
- Sólo ejecuta la búsqueda si `searched === true`.
- Devuelve `data` (vía Resource), `summary`, `meta` y `filters`.

### Resource

Reutilizar `App\Modules\Sale\Resources\SaleResource` si ya expone los campos
necesarios; si falta `días restantes`/`isInGracePeriod`, crear un
`ExpirationResource` específico en el módulo Report.

### Ruta

En `app/Modules/Report/routes.php`, dentro del grupo `reports`:

```php
Route::get('/expirations', [ExpirationGetController::class, 'index'])
    ->name('reports.expirations.index');
```

### Permiso

Registrar `reports.expirations` (texto UI: "Ver reporte de vencimientos"):

- `database/sql/seed_initial_modules.sql` (módulo reports, siguiente `order`).
- `database/seeders/MenuSeeder.php` (entrada de menú con ese `permission`).

> Ver memoria *Permisos/menús de un módulo*: el registro es vía SQL manual +
> `MenuSeeder`, no migraciones.

### Frontend

`resources/js/pages/reports/expirations/index.tsx` siguiendo
`reports/income-expenses/index.tsx`: filtros arriba, cards de resumen, tabla.
Usar `route()` / Wayfinder para enlaces y el `SaleRenewDialog` existente para la
acción de renovar. Estado vacío con skeleton mientras no se ha buscado.

## Tests (obligatorio)

`tests/Feature/Report/ExpirationReportTest.php` (Pest):

- Devuelve 403 sin el permiso `reports.expirations`.
- No consulta hasta `searched=true` (resumen en cero, data vacía).
- Filtra correctamente por rango de días (`expiringSoon`).
- Lista vencidas sin renovar cuando `status=expired`.
- `renewal_rate` se calcula bien cruzando con `app_sale_renewals`.
- Respeta el aislamiento por compañía (`company_id`).

Usar factories `SaleFactory` y `SaleRenewalFactory`.
